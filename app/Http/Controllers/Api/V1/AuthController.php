<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\GoogleLoginRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\TwoFactorChallengeRequest;
use App\Http\Resources\UserResource;
use App\Models\Level;
use App\Models\User;
use App\Notifications\TwoFactorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * @tags Auth
 */
class AuthController extends Controller
{
    /**
     * Register
     *
     * @unauthenticated
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create(collect($data)->except('device_name')->all());
        $user->forceFill(['level_id' => Level::query()->orderBy('min_total_purchases')->value('id')])->save();

        return $this->issueToken($user, $data['device_name'] ?? null, __('store.messages.registered'), 201);
    }

    /**
     * Login
     *
     * Accepts a username or email in `login`. When the account has two-factor enabled the response is
     * `{ requires_two_factor: true, challenge }` and a code is emailed; finish with POST /auth/two-factor.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $login = $request->validated('login');
        $user = User::query()
            ->where(filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username', $login)
            ->first();

        if (! $user || ! $user->password || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages(['login' => __('auth.failed')]);
        }

        $this->ensureNotBanned($user);

        if ($user->two_factor_enabled) {
            $challenge = Str::random(40);
            $code = (string) random_int(100000, 999999);
            Cache::put("2fa:{$challenge}", ['user_id' => $user->id, 'code' => Hash::make($code), 'attempts' => 0], now()->addMinutes(10));
            $user->notify(new TwoFactorCode($code));

            return response()->json(['data' => ['requires_two_factor' => true, 'challenge' => $challenge]]);
        }

        return $this->issueToken($user, $request->validated('device_name'), __('store.messages.logged_in'));
    }

    /**
     * Two-factor challenge
     *
     * @unauthenticated
     */
    public function twoFactor(TwoFactorChallengeRequest $request): JsonResponse
    {
        $key = '2fa:'.$request->validated('challenge');
        $pending = Cache::get($key);

        if (! $pending || $pending['attempts'] >= 5) {
            Cache::forget($key);
            throw ValidationException::withMessages(['code' => __('auth.failed')]);
        }

        if (! Hash::check($request->validated('code'), $pending['code'])) {
            Cache::put($key, ['attempts' => $pending['attempts'] + 1] + $pending, now()->addMinutes(10));
            throw ValidationException::withMessages(['code' => __('auth.failed')]);
        }

        Cache::forget($key);
        $user = User::findOrFail($pending['user_id']);
        $this->ensureNotBanned($user);

        return $this->issueToken($user, $request->validated('device_name'), __('store.messages.logged_in'));
    }

    /**
     * Login with Google
     *
     * Verifies a Google ID token, then signs in (or creates) the matching account.
     *
     * @unauthenticated
     */
    public function google(GoogleLoginRequest $request): JsonResponse
    {
        $response = Http::timeout(10)->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $request->validated('id_token')]);
        $payload = $response->json() ?? [];

        if (! $response->ok()
            || blank(config('services.google.client_id'))
            || ($payload['aud'] ?? null) !== config('services.google.client_id')
            || ! filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)
            || blank($payload['sub'] ?? null)) {
            throw new BusinessException(__('auth.google_failed'), 401);
        }

        $user = User::query()->where('google_id', $payload['sub'])->first()
            ?? User::query()->where('email', $payload['email'])->first();

        if ($user) {
            $user->forceFill(['google_id' => $payload['sub']])->save();
        } else {
            $user = User::create([
                'first_name' => $payload['given_name'] ?? Str::before($payload['email'], '@'),
                'last_name' => $payload['family_name'] ?? null,
                'username' => $this->uniqueUsername(Str::before($payload['email'], '@')),
                'email' => $payload['email'],
                'google_id' => $payload['sub'],
                'avatar' => $payload['picture'] ?? null,
            ]);
            $user->forceFill([
                'email_verified_at' => now(),
                'level_id' => Level::query()->orderBy('min_total_purchases')->value('id'),
            ])->save();
        }

        $this->ensureNotBanned($user);

        return $this->issueToken($user, $request->validated('device_name'), __('store.messages.logged_in'));
    }

    /**
     * Logout
     *
     * Revokes the current token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['data' => null, 'message' => __('store.messages.logged_out')]);
    }

    /**
     * Forgot password
     *
     * Emails a reset link to the frontend (`FRONTEND_URL/reset-password?token=…&email=…`).
     * Always answers with the same message so emails cannot be enumerated.
     *
     * @unauthenticated
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->validated());

        return response()->json(['data' => null, 'message' => __('passwords.sent')]);
    }

    /**
     * Reset password
     *
     * @unauthenticated
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset($request->validated(), function (User $user, string $password) {
            $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            $user->tokens()->delete();
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return response()->json(['data' => null, 'message' => __($status)]);
    }

    protected function issueToken(User $user, ?string $device, string $message, int $status = 200): JsonResponse
    {
        $user->forceFill(['last_login_at' => now()])->save();
        $token = $user->createToken($device ?: 'web')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => new UserResource($user->load('level')),
            ],
            'message' => $message,
        ], $status);
    }

    protected function ensureNotBanned(User $user): void
    {
        if ($user->is_banned) {
            throw new BusinessException(__('auth.banned'), 403, 'banned');
        }
    }

    protected function uniqueUsername(string $base): string
    {
        $base = Str::limit(Str::slug($base, '_') ?: 'user', 20, '');
        $username = $base;
        while (User::where('username', $username)->exists()) {
            $username = $base.'_'.random_int(100, 99999);
        }

        return $username;
    }
}

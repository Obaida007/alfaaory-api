<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvatarRequest;
use App\Http\Requests\TwoFactorRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @tags Account
 */
class MeController extends Controller
{
    /**
     * Current user
     *
     * Profile, balance, level and progress towards the next level.
     */
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user()->load('level'));
    }

    /**
     * Update profile
     */
    public function update(UpdateProfileRequest $request): UserResource
    {
        $request->user()->update($request->validated());

        return (new UserResource($request->user()->load('level')))->additional(['message' => __('store.messages.profile_updated')]);
    }

    /**
     * Upload avatar
     */
    public function avatar(AvatarRequest $request): UserResource
    {
        $user = $request->user();
        $old = $user->avatar;

        $user->update(['avatar' => $request->file('avatar')->store('avatars', 'public')]);

        if ($old && ! Str::startsWith($old, 'http')) {
            Storage::disk('public')->delete($old);
        }

        return (new UserResource($user->load('level')))->additional(['message' => __('store.messages.avatar_updated')]);
    }

    /**
     * Change password
     *
     * Revokes every other token of the user.
     */
    public function password(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->update(['password' => $request->validated('password')]);
        $user->tokens()->whereKeyNot($user->currentAccessToken()->getKey())->delete();

        return response()->json(['data' => null, 'message' => __('store.messages.password_updated')]);
    }

    /**
     * Toggle two-factor
     *
     * When enabled, logins require a 6-digit code sent by email.
     */
    public function twoFactor(TwoFactorRequest $request): UserResource
    {
        $request->user()->update(['two_factor_enabled' => $request->boolean('enabled')]);

        return (new UserResource($request->user()->load('level')))->additional(['message' => __('store.messages.two_factor_updated')]);
    }

    /**
     * Telegram link
     *
     * Returns a one-time deep link; opening it in Telegram lets the bot bind the chat to this account.
     */
    public function telegramLink(Request $request): JsonResponse
    {
        $token = Str::random(32);
        $request->user()->forceFill(['telegram_link_token' => $token])->save();
        $bot = config('services.telegram.bot_username');

        return response()->json([
            'data' => [
                'token' => $token,
                'url' => $bot ? "https://t.me/{$bot}?start={$token}" : null,
            ],
            'message' => __('store.messages.telegram_link'),
        ]);
    }
}

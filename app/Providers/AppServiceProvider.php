<?php

namespace App\Providers;

use App\Models\Deposit;
use App\Models\Order;
use App\Models\RechargeCode;
use App\Models\User;
use App\Services\GameProviders\FakeGameProvider;
use App\Services\GameProviders\GameProvider;
use App\Support\ApiEnvelopeDocumentTransformer;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(GameProvider::class, fn () => match (config('store.provider')) {
            'fake' => new FakeGameProvider,
            default => throw new InvalidArgumentException('Unknown GAME_PROVIDER ['.config('store.provider').'].'),
        });
    }

    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // "user" must stay first so notifications/tokens store users as "user".
        Relation::enforceMorphMap([
            'user' => User::class,
            'order' => Order::class,
            'deposit' => Deposit::class,
            'recharge_code' => RechargeCode::class,
            'admin_adjustment' => User::class,
        ]);

        // Paginated API resources return { data, meta } — no `links` block.
        ResourceCollection::macro('paginationInformation', fn ($request, array $paginated, array $default) => [
            'meta' => [
                'current_page' => $paginated['current_page'],
                'last_page' => $paginated['last_page'],
                'per_page' => $paginated['per_page'],
                'total' => $paginated['total'],
                'from' => $paginated['from'],
                'to' => $paginated['to'],
            ],
        ]);

        $this->configureRateLimiting();
        $this->configureApiDocs();

        ResetPassword::createUrlUsing(fn (User $user, string $token) => rtrim(config('store.frontend_url'), '/')
            .'/reset-password?token='.$token.'&email='.urlencode($user->email));

    }

    protected function configureRateLimiting(): void
    {
        $limits = config('store.rate_limits');

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute($limits['login'])->by(mb_strtolower((string) $request->input('login', $request->input('email'))).'|'.$request->ip()),
            Limit::perMinute($limits['login'] * 4)->by($request->ip()),
        ]);
        RateLimiter::for('check-name', fn (Request $request) => Limit::perMinute($limits['check_name'])->by($request->user()?->id ?: $request->ip()));
        RateLimiter::for('orders', fn (Request $request) => Limit::perMinute($limits['orders'])->by($request->user()?->id ?: $request->ip()));
    }

    protected function configureApiDocs(): void
    {
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(SecurityScheme::http('bearer'));
            })
            ->withDocumentTransformers(new ApiEnvelopeDocumentTransformer);

        Gate::define('viewApiDocs', fn (?User $user = null) => config('store.api_docs_public') || (bool) $user?->hasAnyRole(User::ADMIN_ROLES));
    }
}

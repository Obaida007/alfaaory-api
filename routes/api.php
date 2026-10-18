<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\DepositController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\InfoController;
use App\Http\Controllers\Api\V1\KycController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:api')->name('api.v1.')->group(function () {
    // Public catalogue & info
    Route::get('home', [CatalogController::class, 'home'])->name('home');
    Route::get('categories', [CatalogController::class, 'categories'])->name('categories.index');
    Route::get('categories/{slug}', [CatalogController::class, 'category'])->name('categories.show');
    Route::get('products/{product}', [CatalogController::class, 'product'])->whereNumber('product')->name('products.show');
    Route::get('search', [CatalogController::class, 'search'])->name('search');
    Route::get('payment-methods', [InfoController::class, 'paymentMethods'])->name('payment-methods');
    Route::get('agents', [InfoController::class, 'agents'])->name('agents');
    Route::get('pages/{page}', [InfoController::class, 'page'])->whereIn('page', ['about', 'privacy'])->name('pages');
    Route::get('settings/public', [InfoController::class, 'settings'])->name('settings');

    // Auth
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:login')->name('register');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login');
        Route::post('two-factor', [AuthController::class, 'twoFactor'])->middleware('throttle:login')->name('two-factor');
        Route::post('google', [AuthController::class, 'google'])->middleware('throttle:login')->name('google');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:login')->name('forgot-password');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:login')->name('reset-password');
        Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum')->name('logout');
    });

    Route::middleware(['auth:sanctum', 'not-banned'])->group(function () {
        Route::get('me', [MeController::class, 'show'])->name('me');
        Route::put('me/profile', [MeController::class, 'update'])->name('me.profile');
        Route::post('me/avatar', [MeController::class, 'avatar'])->name('me.avatar');
        Route::put('me/password', [MeController::class, 'password'])->name('me.password');
        Route::put('me/two-factor', [MeController::class, 'twoFactor'])->name('me.two-factor');
        Route::post('me/telegram/link', [MeController::class, 'telegramLink'])->name('me.telegram');

        Route::post('products/{product}/check-name', [OrderController::class, 'checkName'])
            ->whereNumber('product')->middleware('throttle:check-name')->name('products.check-name');

        Route::post('orders', [OrderController::class, 'store'])->middleware('throttle:orders')->name('orders.store');
        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('orders.show');

        Route::get('wallet', [WalletController::class, 'show'])->name('wallet');
        Route::get('wallet/transactions', [WalletController::class, 'transactions'])->name('wallet.transactions');
        Route::post('recharge-codes/redeem', [WalletController::class, 'redeem'])->middleware('throttle:orders')->name('recharge-codes.redeem');

        Route::post('deposits', [DepositController::class, 'store'])->middleware('throttle:orders')->name('deposits.store');
        Route::get('deposits', [DepositController::class, 'index'])->name('deposits.index');

        Route::post('kyc', [KycController::class, 'store'])->name('kyc.store');
        Route::get('kyc', [KycController::class, 'show'])->name('kyc.show');

        Route::get('favorites', [FavoriteController::class, 'index'])->name('favorites.index');
        Route::post('favorites/{product}', [FavoriteController::class, 'store'])->whereNumber('product')->name('favorites.store');
        Route::delete('favorites/{product}', [FavoriteController::class, 'destroy'])->whereNumber('product')->name('favorites.destroy');

        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    });
});

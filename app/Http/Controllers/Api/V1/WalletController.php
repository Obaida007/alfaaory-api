<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DepositStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\DateRangeRequest;
use App\Http\Requests\RedeemCodeRequest;
use App\Http\Resources\WalletTransactionResource;
use App\Services\RechargeCodeService;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Wallet
 */
class WalletController extends Controller
{
    /**
     * Wallet summary
     *
     * Current balance, total completed purchases, total received (approved deposits + codes + credits) and debit balance.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json(['data' => [
            'balance' => $user->balance,
            'total_purchases' => Money::round($user->totalCompletedPurchases()),
            'total_received' => Money::round($user->walletTransactions()
                ->where('type', TransactionType::Credit)
                ->where(fn ($q) => $q->whereNull('source_type')->orWhere('source_type', '!=', 'order'))
                ->sum('amount')),
            'pending_deposits' => Money::round($user->deposits()->where('status', DepositStatus::Pending)->sum('amount_usd')),
            'debit_balance' => $user->debit_balance,
            'currency' => config('store.currency'),
        ]]);
    }

    /**
     * Wallet transactions
     */
    public function transactions(DateRangeRequest $request): AnonymousResourceCollection
    {
        return WalletTransactionResource::collection(
            $request->applyDateRange($request->user()->walletTransactions())->latest('id')->paginate($request->perPage())
        );
    }

    /**
     * Redeem recharge code
     */
    public function redeem(RedeemCodeRequest $request, RechargeCodeService $codes): JsonResponse
    {
        $tx = $codes->redeem($request->user(), $request->validated('code'));

        return response()->json([
            'data' => [
                'amount' => $tx->amount,
                'balance' => $tx->balance_after,
                'transaction' => new WalletTransactionResource($tx),
            ],
            'message' => __('store.messages.code_redeemed', ['amount' => $tx->amount]),
        ]);
    }
}

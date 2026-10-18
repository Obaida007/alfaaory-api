<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DateRangeRequest;
use App\Http\Requests\StoreDepositRequest;
use App\Http\Resources\DepositResource;
use App\Models\PaymentMethod;
use App\Services\DepositService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Deposits
 */
class DepositController extends Controller
{
    /**
     * Submit deposit
     *
     * multipart/form-data with the receipt image. Rejected with 403 `kyc_required` when the method needs
     * verified identity and the user is not verified.
     */
    public function store(StoreDepositRequest $request, DepositService $deposits): JsonResponse
    {
        $deposit = $deposits->submit(
            $request->user(),
            PaymentMethod::findOrFail($request->validated('payment_method_id')),
            $request->safe()->only(['amount', 'sender_name', 'transaction_ref']),
            $request->file('receipt_image'),
        );

        return (new DepositResource($deposit->load('paymentMethod')))
            ->additional(['message' => __('store.messages.deposit_created')])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * My deposits
     */
    public function index(DateRangeRequest $request): AnonymousResourceCollection
    {
        return DepositResource::collection(
            $request->applyDateRange($request->user()->deposits())->with('paymentMethod')->latest('id')->paginate($request->perPage())
        );
    }
}

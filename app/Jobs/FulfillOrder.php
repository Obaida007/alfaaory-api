<?php

namespace App\Jobs;

use App\Exceptions\BusinessException;
use App\Models\Order;
use App\Services\GameProviders\GameProvider;
use App\Services\OrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sends an auto-delivery order to the game provider, then completes it or rejects it with a refund.
 */
class FulfillOrder implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function handle(GameProvider $provider, OrderService $orders): void
    {
        try {
            $order = $orders->markProcessing($this->order);
        } catch (BusinessException) {
            return; // Already handled (e.g. an admin acted first).
        }

        $result = $provider->placeOrder($order->load('product'));
        $order->update(['provider_response' => $result->response]);

        match ($result->status) {
            'completed' => $orders->complete($order, deliveredCode: $result->code),
            'failed' => $orders->reject($order, note: $result->message ?? __('store.provider_failed')),
            default => null, // Still processing at the provider; an admin or webhook finishes it.
        };
    }
}

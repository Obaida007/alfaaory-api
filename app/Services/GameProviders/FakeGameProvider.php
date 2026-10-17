<?php

namespace App\Services\GameProviders;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Str;

/**
 * Development provider: any numeric player ID of 5+ digits exists, IDs ending in "000" do not.
 */
class FakeGameProvider implements GameProvider
{
    public function checkPlayerName(Product $product, string $playerId): ?string
    {
        if (! preg_match('/^\d{5,}$/', $playerId) || str_ends_with($playerId, '000')) {
            return null;
        }

        return 'Player_'.substr($playerId, -4);
    }

    public function placeOrder(Order $order): ProviderResult
    {
        if ($order->player_id !== null && $this->checkPlayerName($order->product, $order->player_id) === null) {
            return new ProviderResult('failed', ['error' => 'player_not_found'], message: __('store.player_not_found'));
        }

        return new ProviderResult('completed', [
            'provider' => 'fake',
            'reference' => 'FAKE-'.Str::upper(Str::random(10)),
        ]);
    }
}

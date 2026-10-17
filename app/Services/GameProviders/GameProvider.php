<?php

namespace App\Services\GameProviders;

use App\Models\Order;
use App\Models\Product;

/**
 * A top-up supplier (player-name lookup and automatic delivery). Swap the implementation via GAME_PROVIDER.
 */
interface GameProvider
{
    /** Returns the in-game player name, or null when the player ID does not exist. */
    public function checkPlayerName(Product $product, string $playerId): ?string;

    public function placeOrder(Order $order): ProviderResult;
}

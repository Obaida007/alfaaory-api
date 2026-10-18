<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckNameRequest;
use App\Http\Requests\OrderIndexRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Product;
use App\Services\GameProviders\GameProvider;
use App\Services\OrderService;
use Dedoc\Scramble\Attributes\HeaderParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Orders
 */
class OrderController extends Controller
{
    /**
     * Check player name
     *
     * Looks up the in-game name for a player ID before buying.
     */
    public function checkName(CheckNameRequest $request, Product $product, GameProvider $provider): JsonResponse
    {
        if (! $product->supports_name_check) {
            throw new BusinessException(__('store.name_check_unsupported'));
        }

        $name = $provider->checkPlayerName($product, $request->validated('player_id'));

        if ($name === null) {
            throw new BusinessException(__('store.player_not_found'), 404, 'player_not_found');
        }

        return response()->json([
            'data' => ['player_id' => $request->validated('player_id'), 'player_name' => $name],
            'message' => __('store.messages.name_found'),
        ]);
    }

    /**
     * Place order
     *
     * Buys directly from the wallet balance. Requires the `Idempotency-Key` header; repeating a request
     * with the same key returns the original order (HTTP 200) without charging again.
     */
    #[HeaderParameter('Idempotency-Key', 'A unique key per checkout attempt (e.g. a UUID).', required: true, type: 'string', example: '3f0b6c8e-6c5e-4d0e-9a51-9b8f1c2d7e10')]
    public function store(StoreOrderRequest $request, OrderService $orders): JsonResponse
    {
        $key = $request->idempotencyKey() ?? throw new BusinessException(__('store.idempotency_required'), 400, 'idempotency_required');

        [$order, $created] = $orders->place(
            $request->user(),
            Product::with('category')->findOrFail($request->validated('product_id')),
            $request->safe()->only(['player_id', 'player_name', 'quantity', 'extra']),
            $key,
        );

        return (new OrderResource($order->load('product')))
            ->additional(['message' => $created ? __('store.messages.order_created') : __('store.messages.order_replayed')])
            ->response()
            ->setStatusCode($created ? 201 : 200);
    }

    /**
     * My orders
     */
    public function index(OrderIndexRequest $request): AnonymousResourceCollection
    {
        $q = $request->validated('q');

        $orders = $request->applyDateRange($request->user()->orders()->getQuery())
            ->with('product')
            ->when($request->validated('status'), fn (Builder $b, $status) => $b->where('status', $status))
            ->when($q, fn (Builder $b) => $b->where(fn (Builder $w) => $w
                ->where('player_id', 'like', "%{$q}%")
                ->orWhereHas('product', fn (Builder $p) => $p->whereLike('name', "%{$q}%"))
                ->when(ctype_digit(ltrim($q, '#')), fn (Builder $x) => $x->orWhere('id', (int) ltrim($q, '#')))))
            ->latest('id')
            ->paginate($request->perPage());

        return OrderResource::collection($orders);
    }

    /**
     * Order details
     */
    public function show(Request $request, Order $order): OrderResource
    {
        abort_unless($order->user_id === $request->user()->id, 404);

        return new OrderResource($order->load('product'));
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Favorites
 */
class FavoriteController extends Controller
{
    /**
     * My favorites
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        return ProductResource::collection(
            $request->user()->favoriteProducts()->with('category')->latest('favorites.created_at')->paginate(50)
        );
    }

    /**
     * Add to favorites
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $request->user()->favoriteProducts()->syncWithoutDetaching([$product->id]);

        return response()->json(['data' => ['product_id' => $product->id, 'is_favorite' => true], 'message' => __('store.messages.favorite_added')]);
    }

    /**
     * Remove from favorites
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $request->user()->favoriteProducts()->detach($product->id);

        return response()->json(['data' => ['product_id' => $product->id, 'is_favorite' => false], 'message' => __('store.messages.favorite_removed')]);
    }
}

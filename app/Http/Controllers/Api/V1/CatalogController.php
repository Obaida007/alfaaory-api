<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BannerPlacement;
use App\Http\Controllers\Controller;
use App\Http\Requests\SearchRequest;
use App\Http\Resources\BannerResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * @tags Catalog
 */
class CatalogController extends Controller
{
    /**
     * Home page
     *
     * Active banners (hero + popup), the root sections and the most ordered products of the last 30 days.
     *
     * @unauthenticated
     */
    public function home(): JsonResponse
    {
        $banners = Banner::query()->visible()->orderBy('sort')->get();

        $topProductIds = Order::query()
            ->where('created_at', '>=', now()->subDays(30))
            ->select('product_id', DB::raw('count(*) as orders_count'))
            ->groupBy('product_id')
            ->orderByDesc('orders_count')
            ->limit(12)
            ->pluck('product_id');

        $topProducts = Product::query()->active()
            ->whereHas('category', fn (Builder $q) => $q->active())
            ->with('category')
            ->when($topProductIds->isNotEmpty(),
                fn ($q) => $q->whereIn('id', $topProductIds),
                fn ($q) => $q->orderBy('sort')->limit(12))
            ->get()
            ->sortBy(fn (Product $p) => ($i = $topProductIds->search($p->id)) === false ? PHP_INT_MAX : $i)
            ->values();

        return response()->json([
            'data' => [
                'hero_banners' => BannerResource::collection($banners->filter(fn (Banner $b) => $b->placement === BannerPlacement::HomeHero)->values()),
                'popup_banners' => BannerResource::collection($banners->filter(fn (Banner $b) => $b->placement === BannerPlacement::HomePopup)->values()),
                'sections' => CategoryResource::collection(
                    Category::query()->roots()->active()->orderBy('sort')->with('activeChildren')->get()
                ),
                'top_products' => ProductResource::collection($topProducts),
            ],
        ]);
    }

    /**
     * Category tree
     *
     * Sections → games/services → types (three levels), active only.
     *
     * @unauthenticated
     */
    public function categories(): AnonymousResourceCollection
    {
        return CategoryResource::collection(
            Category::query()->roots()->active()->orderBy('sort')
                ->with('activeChildren.activeChildren')
                ->get()
        );
    }

    /**
     * Category by slug
     *
     * The category with its active sub-categories, its products and its parent (for breadcrumbs).
     *
     * @unauthenticated
     */
    public function category(string $slug): CategoryResource
    {
        $category = Category::query()->active()->where('slug', $slug)->firstOrFail();

        return new CategoryResource($category->load([
            'parent',
            'activeChildren',
            'products' => fn ($q) => $q->active(),
        ]));
    }

    /**
     * Product details
     *
     * @unauthenticated
     */
    public function product(Product $product): ProductResource
    {
        abort_unless($product->is_active && $product->category->is_active, 404);

        return new ProductResource($product->load('category.parent'));
    }

    /**
     * Search
     *
     * Matches category and product names.
     *
     * @unauthenticated
     */
    public function search(SearchRequest $request): JsonResponse
    {
        $q = '%'.$request->validated('q').'%';

        return response()->json([
            'data' => [
                'categories' => CategoryResource::collection(
                    Category::query()->active()->whereLike('name', $q)->orderBy('sort')->limit(20)->get()
                ),
                'products' => ProductResource::collection(
                    Product::query()->active()
                        ->whereHas('category', fn (Builder $c) => $c->active())
                        ->where(fn (Builder $w) => $w->whereLike('name', $q)
                            ->orWhereHas('category', fn (Builder $c) => $c->whereLike('name', $q)))
                        ->with('category')
                        ->orderBy('sort')
                        ->limit(30)
                        ->get()
                ),
            ],
        ]);
    }
}

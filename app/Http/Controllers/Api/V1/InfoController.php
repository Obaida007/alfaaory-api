<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentGroup;
use App\Http\Controllers\Controller;
use App\Http\Resources\AgentResource;
use App\Http\Resources\PaymentMethodResource;
use App\Models\Agent;
use App\Models\PaymentMethod;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Info
 */
class InfoController extends Controller
{
    /**
     * Payment methods
     *
     * Active methods grouped by `group` (wallet, crypto, bank, agency_salary, code).
     *
     * @unauthenticated
     */
    public function paymentMethods(): JsonResponse
    {
        $methods = PaymentMethod::query()->active()->orderBy('sort')->get()->groupBy(fn (PaymentMethod $m) => $m->group->value);

        $groups = collect(PaymentGroup::cases())
            ->filter(fn (PaymentGroup $g) => $methods->has($g->value))
            ->map(fn (PaymentGroup $g) => [
                'group' => $g->value,
                'label' => $g->getLabel(),
                'methods' => PaymentMethodResource::collection($methods[$g->value]),
            ])
            ->values();

        return response()->json(['data' => $groups]);
    }

    /**
     * Agents
     *
     * @unauthenticated
     */
    public function agents(): AnonymousResourceCollection
    {
        return AgentResource::collection(Agent::query()->where('is_active', true)->orderBy('sort')->get());
    }

    /**
     * Static page
     *
     * `about` (من نحن) or `privacy` (سياسة الخصوصية).
     *
     * @unauthenticated
     */
    public function page(string $page): JsonResponse
    {
        abort_unless(isset(Setting::PAGE_KEYS[$page]), 404);

        return response()->json(['data' => [
            'page' => $page,
            'content' => Setting::get(Setting::PAGE_KEYS[$page], ''),
        ]]);
    }

    /**
     * Public settings
     *
     * Contact info and social links.
     *
     * @unauthenticated
     */
    public function settings(): JsonResponse
    {
        $all = Setting::allCached();
        $settings = [];
        foreach (Setting::PUBLIC_KEYS as $key) {
            $settings[$key] = $all[$key] ?? null;
        }

        return response()->json(['data' => $settings]);
    }
}

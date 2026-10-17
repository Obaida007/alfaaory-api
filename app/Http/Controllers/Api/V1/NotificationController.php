<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DateRangeRequest;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @tags Notifications
 */
class NotificationController extends Controller
{
    /**
     * My notifications
     *
     * `meta.unread_count` holds the number of unread notifications.
     */
    public function index(DateRangeRequest $request): AnonymousResourceCollection
    {
        $user = $request->user();

        return NotificationResource::collection(
            $request->applyDateRange($user->notifications())->paginate($request->perPage())
        )->additional(['meta' => ['unread_count' => $user->unreadNotifications()->count()]]);
    }

    /**
     * Mark all as read
     */
    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return response()->json(['data' => null, 'message' => __('store.messages.notifications_read')]);
    }
}

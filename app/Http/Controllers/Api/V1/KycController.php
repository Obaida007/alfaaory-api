<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreKycRequest;
use App\Http\Resources\KycRequestResource;
use App\Services\KycService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags KYC
 */
class KycController extends Controller
{
    /**
     * Submit identity verification
     *
     * multipart/form-data. Images are stored privately (not publicly reachable).
     */
    public function store(StoreKycRequest $request, KycService $kyc): JsonResponse
    {
        $record = $kyc->submit(
            $request->user(),
            $request->safe()->only(['full_name', 'national_id', 'details']),
            $request->file('id_image'),
            $request->file('selfie_image'),
        );

        return (new KycRequestResource($record))
            ->additional(['message' => __('store.messages.kyc_submitted')])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Verification status
     *
     * The user's KYC status and their latest request (null if none).
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $latest = $user->kycRequests()->latest('id')->first();

        return response()->json(['data' => [
            'status' => $user->kyc_status,
            'request' => $latest ? new KycRequestResource($latest) : null,
        ]]);
    }
}

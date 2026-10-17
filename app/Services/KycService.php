<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\KycStatus;
use App\Exceptions\BusinessException;
use App\Models\KycRequest;
use App\Models\User;
use App\Notifications\StatusNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class KycService
{
    /**
     * @param  array{full_name: string, national_id: string, details?: ?string}  $data
     */
    public function submit(User $user, array $data, UploadedFile $idImage, UploadedFile $selfie): KycRequest
    {
        if (in_array($user->kyc_status, [KycStatus::Pending, KycStatus::Approved], true)) {
            throw new BusinessException(__('store.kyc_already_submitted'));
        }

        return DB::transaction(function () use ($user, $data, $idImage, $selfie) {
            $request = $user->kycRequests()->create([
                'full_name' => $data['full_name'],
                'national_id' => $data['national_id'],
                'details' => $data['details'] ?? null,
                'id_image' => $idImage->store('kyc', 'local'),
                'selfie_image' => $selfie->store('kyc', 'local'),
                'status' => DepositStatus::Pending,
            ]);
            $user->forceFill(['kyc_status' => KycStatus::Pending])->save();

            return $request;
        });
    }

    public function approve(KycRequest $kyc, User $admin, ?string $note = null): KycRequest
    {
        return $this->review($kyc, $admin, DepositStatus::Approved, KycStatus::Approved, $note);
    }

    public function reject(KycRequest $kyc, User $admin, ?string $note = null): KycRequest
    {
        return $this->review($kyc, $admin, DepositStatus::Rejected, KycStatus::Rejected, $note);
    }

    protected function review(KycRequest $kyc, User $admin, DepositStatus $status, KycStatus $userStatus, ?string $note): KycRequest
    {
        $kyc = DB::transaction(function () use ($kyc, $admin, $status, $userStatus, $note) {
            $locked = KycRequest::query()->whereKey($kyc->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->status !== DepositStatus::Pending) {
                throw new BusinessException(__('store.kyc_already_reviewed'));
            }
            $locked->update([
                'status' => $status,
                'admin_note' => $note,
                'reviewed_by' => $admin->getKey(),
                'reviewed_at' => now(),
            ]);
            $locked->user->forceFill(['kyc_status' => $userStatus])->save();

            return $locked;
        });

        $kyc->user->notify(StatusNotification::forKyc($kyc));

        return $kyc;
    }
}

<?php

namespace App\Policies;

use App\Models\RechargeCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RechargeCodePolicy extends AdminPolicy
{
    public function update(User $user, Model $model): bool
    {
        return false;
    }

    /** Only unused codes can be deleted. */
    public function delete(User $user, Model $model): bool
    {
        /** @var RechargeCode $model */
        return ! $model->isUsed() && parent::delete($user, $model);
    }
}

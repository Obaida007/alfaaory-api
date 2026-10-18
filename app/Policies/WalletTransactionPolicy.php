<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** The wallet ledger is read-only. */
class WalletTransactionPolicy extends AdminPolicy
{
    protected bool $deletable = false;

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $model): bool
    {
        return false;
    }
}

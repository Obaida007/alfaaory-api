<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** Deposits are financial records: they are reviewed, never created or deleted from the panel. */
class DepositPolicy extends AdminPolicy
{
    protected array $viewRoles = ['super_admin', 'admin', 'support'];

    protected array $manageRoles = ['super_admin', 'admin', 'support'];

    protected bool $deletable = false;

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $model): bool
    {
        return false;
    }

    /** Approve / reject. */
    public function process(User $user, Model $model): bool
    {
        return $user->hasAnyRole($this->manageRoles);
    }
}

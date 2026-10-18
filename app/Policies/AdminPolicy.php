<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Role-based access for the admin panel.
 *
 * - super_admin: everything
 * - admin: everything except managing other staff accounts
 * - support: orders and deposits only (view + process)
 */
abstract class AdminPolicy
{
    /** @var array<int, string> */
    protected array $viewRoles = ['super_admin', 'admin'];

    /** @var array<int, string> */
    protected array $manageRoles = ['super_admin', 'admin'];

    protected bool $deletable = true;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole($this->viewRoles);
    }

    public function view(User $user, Model $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole($this->manageRoles);
    }

    public function update(User $user, Model $model): bool
    {
        return $user->hasAnyRole($this->manageRoles);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->deletable && $user->hasAnyRole($this->manageRoles);
    }

    public function deleteAny(User $user): bool
    {
        return $this->deletable && $user->hasAnyRole($this->manageRoles);
    }

    public function reorder(User $user): bool
    {
        return $user->hasAnyRole($this->manageRoles);
    }

    public function restore(User $user, Model $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return false;
    }

    public function replicate(User $user, Model $model): bool
    {
        return false;
    }
}

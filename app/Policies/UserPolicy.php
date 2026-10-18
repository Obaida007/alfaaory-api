<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Admins manage customers; only a super_admin manages staff accounts and roles.
 */
class UserPolicy extends AdminPolicy
{
    public function update(User $user, Model $model): bool
    {
        /** @var User $model */
        return $user->hasRole('super_admin')
            || ($user->hasRole('admin') && ! $model->hasAnyRole(User::ADMIN_ROLES));
    }

    public function delete(User $user, Model $model): bool
    {
        return $user->hasRole('super_admin') && ! $user->is($model);
    }

    public function deleteAny(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function adjustBalance(User $user, User $model): bool
    {
        return $this->update($user, $model);
    }

    public function manageRoles(User $user): bool
    {
        return $user->hasRole('super_admin');
    }
}

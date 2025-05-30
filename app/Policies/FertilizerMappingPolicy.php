<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\RoleType;
use App\Models\FertilizerMapping;
use Illuminate\Auth\Access\Response;

class FertilizerMappingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, FertilizerMapping $fertilizerMapping): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
        ]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FertilizerMapping $fertilizerMapping): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
        ]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FertilizerMapping $fertilizerMapping): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
        ]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, FertilizerMapping $fertilizerMapping): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
        ]);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, FertilizerMapping $fertilizerMapping): bool
    {
        return false;
    }
}

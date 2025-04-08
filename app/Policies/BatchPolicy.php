<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Batch;
use App\Enums\RoleType;

class BatchPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::BODEGUERO,
            RoleType::ASISTENTE,
            RoleType::USUARIO,
        ]);

    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Batch $batch): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::BODEGUERO,
            RoleType::ASISTENTE,
            RoleType::USUARIO,
        ]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === RoleType::ADMIN;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Batch $batch): bool
    {
        return $user->role === RoleType::ADMIN;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user,  Batch $batch): bool
    {
        return $user->role === RoleType::ADMIN;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user,  Batch $batch): bool
    {
        return $user->role === RoleType::ADMIN;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user,  Batch $batch): bool
    {
        return $user->role === RoleType::ADMIN;
    }
}

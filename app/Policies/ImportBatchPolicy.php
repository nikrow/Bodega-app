<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\RoleType;
use App\Models\ImportBatch;
use Illuminate\Auth\Access\Response;

class ImportBatchPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::ASISTENTE,
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ImportBatch $importBatch): bool
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
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ImportBatch $importBatch): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
        ]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ImportBatch $importBatch): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ImportBatch $importBatch): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ImportBatch $importBatch): bool
    {
        return false;
    }
}

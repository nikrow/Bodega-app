<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\RoleType;

class ProviderPolicy
{
    /**
     * Create a new policy instance.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::BODEGUERO,
            RoleType::ASISTENTE,
            RoleType::USUARIO,
            RoleType::SUPERUSER,
        ]);
    }
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::BODEGUERO,
            RoleType::ASISTENTE,
            RoleType::USUARIO,
            RoleType::SUPERUSER,
        ]);
    }
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::BODEGUERO,
            RoleType::ASISTENTE,
            RoleType::SUPERUSER,
        ]);
    }
    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::BODEGUERO,
            RoleType::ASISTENTE,
            RoleType::SUPERUSER,
        ]);
    }
    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user): bool
    {
        return $user->role === RoleType::ADMIN;  

    }
    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user): bool
    {
        return $user->role === RoleType::ADMIN;  
    }
    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user): bool
    {
        return $user->role === RoleType::ADMIN;  
    }
}

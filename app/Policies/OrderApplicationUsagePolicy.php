<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\RoleType;
use App\Models\Role;

class OrderApplicationUsagePolicy
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
    

}

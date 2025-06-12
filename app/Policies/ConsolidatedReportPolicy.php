<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\RoleType;

class ConsolidatedReportPolicy
{
    /**
     * Create a new policy instance.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::USUARIOMAQ,
            RoleType::ASISTENTE,
            RoleType::MAQUINARIA
        ]);
    }

    public function view(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::USUARIOMAQ,
            RoleType::ASISTENTE,
            RoleType::MAQUINARIA
        ]);
    }
    
}

<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\RoleType;

class TractorPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::USUARIOMAQ,
            RoleType::MAQUINARIA,
            RoleType::SUPERUSER
        ]);
    }

    public function view(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::USUARIOMAQ,
            RoleType::SUPERUSER
        ]);
    }
    public function create(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::USUARIOMAQ,
            RoleType::SUPERUSER
        ]);
    }
    public function update(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::USUARIOMAQ,
            RoleType::SUPERUSER
        ]);
    }
    public function delete(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::SUPERUSER
        ]);
    }
    public function restore(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::SUPERUSER
        ]);
    }
    public function forceDelete(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            
        ]);
    }   
}

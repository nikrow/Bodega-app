<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\RoleType;

class OperatorAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::USUARIOMAQ,
            RoleType::MAQUINARIA,
        ]);
    }

    public function view(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::USUARIOMAQ,
        ]);
    }
    public function create(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::USUARIOMAQ,
        ]);
    }
    public function update(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::USUARIOMAQ,
        ]);
    }
    public function delete(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            
        ]);
    }
    public function restore(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            
        ]);
    }
    public function forceDelete(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            
        ]);
    }   
}

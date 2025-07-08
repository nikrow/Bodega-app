<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\Applicator;
use App\Models\User;

class ApplicatorPolicy
{
    /**
     * Determinar si el usuario puede acceder al recurso (ver la lista).
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
     * Determinar si el usuario puede ver un registro específico.
     */
    public function view(User $user, Applicator $applicator): bool
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
     * Determinar si el usuario puede crear un nuevo registro.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::ASISTENTE,
            RoleType::SUPERUSER,
        ]);
    }

    /**
     * Determinar si el usuario puede actualizar un registro.
     */
    public function update(User $user, Applicator $applicator): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::ASISTENTE,
            RoleType::SUPERUSER,
        ]);
    }

    /**
     * Determinar si el usuario puede eliminar un registro.
     */
    public function delete(User $user, Applicator $applicator): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::SUPERUSER,
        ]);
    }

    /**
     * Determinar si el usuario puede restaurar un registro.
     */
    public function restore(User $user, Applicator $applicator): bool
    {
        return $user->role === RoleType::ADMIN;
    }

    /**
     * Determinar si el usuario puede eliminar permanentemente un registro.
     */
    public function forceDelete(User $user, Applicator $applicator): bool
    {
        return $user->role === RoleType::ADMIN;
    }

    /**
     * Helper function to check if the user has the required roles.
     */
    private function hasAccess(User $user, array $roles): bool
    {
        return in_array($user->role, $roles);
    }
    public function restoreAudit(User $user, Applicator $applicator): bool
    {
        return $user->role === RoleType::ADMIN;
    }
    public function audit(User $user,Applicator $applicator): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::SUPERUSER,
        ]);
    }
}

<?php

namespace App\Policies;

use App\Models\Movimiento;
use App\Models\User;
use App\Enums\RoleType;
use Illuminate\Auth\Access\Response;

class MovimientoPolicy
{
    /**
     * Determinar si el usuario puede acceder al recurso (ver la lista).
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN->value,
            RoleType::AGRONOMO->value,
            RoleType::BODEGUERO->value,
            RoleType::ESTANQUERO->value,
            RoleType::USUARIO->value,
        ]);
    }

    /**
     * Determinar si el usuario puede ver un registro específico.
     */
    public function view(User $user, Movimiento $movimiento): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN->value,
            RoleType::AGRONOMO->value,
            RoleType::BODEGUERO->value,
            RoleType::ESTANQUERO->value,
            RoleType::USUARIO->value,
        ]);
    }

    /**
     * Determinar si el usuario puede crear un nuevo registro.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN->value,
            RoleType::BODEGUERO->value,
            RoleType::ESTANQUERO->value,
        ]);
    }

    /**
     * Determinar si el usuario puede actualizar un registro.
     */
    public function update(User $user, Movimiento $movimiento): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN->value,
            RoleType::BODEGUERO->value,
        ]);
    }

    /**
     * Determinar si el usuario puede eliminar un registro.
     */
    public function delete(User $user, Movimiento $movimiento): bool
    {
        return $user->role === RoleType::ADMIN->value;
    }

    /**
     * Determinar si el usuario puede restaurar un registro.
     */
    public function restore(User $user, Movimiento $movimiento): bool
    {
        return $user->role === RoleType::ADMIN->value;
    }

    /**
     * Determinar si el usuario puede forzar la eliminación de un registro.
     */
    public function forceDelete(User $user, Movimiento $movimiento): bool
    {
        return $user->role === RoleType::ADMIN->value;
    }

    /**
     * Helper function to check if the user has the required roles.
     */
    private function hasAccess(User $user, array $roles): bool
    {
        return in_array($user->role, $roles);
    }
    private function audit(User $user, Movimiento $movimiento): bool
    {
        return $user->isAdmin();

    }
    private function restoreAudit(User $user, Movimiento $movimiento): bool
    {
        return $user->isAdmin();
    }
}

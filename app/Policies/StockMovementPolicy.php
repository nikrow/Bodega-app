<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StockMovementPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::BODEGUERO,
            RoleType::ESTANQUERO,
            RoleType::USUARIO,
            RoleType::SUPERUSER,
        ]);
    }

    /**
     * Determinar si el usuario puede ver un registro específico.
     */
    public function view(User $user, StockMovement $StockMovement): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::BODEGUERO,
            RoleType::ESTANQUERO,
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
            RoleType::BODEGUERO,
            RoleType::ESTANQUERO,
            RoleType::SUPERUSER,
        ]);
    }

    /**
     * Determinar si el usuario puede actualizar un registro.
     */
    public function update(User $user, StockMovement $StockMovement): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::BODEGUERO,
            RoleType::SUPERUSER
        ]);
    }

    /**
     * Determinar si el usuario puede eliminar un registro.
     */
    public function delete(User $user, StockMovement $StockMovement): bool
    {
        return $user->role === RoleType::ADMIN;
    }

    /**
     * Determinar si el usuario puede restaurar un registro.
     */
    public function restore(User $user, StockMovement $StockMovement): bool
    {
        return $user->role === RoleType::ADMIN;
    }

    /**
     * Determinar si el usuario puede forzar la eliminación de un registro.
     */
    public function forceDelete(User $user, StockMovement $StockMovement): bool
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
}

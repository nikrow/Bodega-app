<?php

namespace App\Policies;

use App\Enums\MovementType;
use App\Enums\RoleType;
use App\Models\Movimiento;
use App\Models\User;

class MovimientoPolicy
{
    /**
     * Determinar si el usuario puede acceder al recurso (ver la lista).
     */
    public function viewAny(User $user): bool
    {
        return $user->warehouses()->exists();
    }

    /**
     * Determinar si el usuario puede ver un registro específico.
     */
    public function view(User $user, Movimiento $movimiento): bool
    {
        // Verificar si el movimiento está relacionado con alguna de las bodegas del usuario
        $userWarehouseIds = $user->warehouses->pluck('id')->toArray();

        return in_array($movimiento->bodega_origen_id, $userWarehouseIds) ||
            in_array($movimiento->bodega_destino_id, $userWarehouseIds);
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
            RoleType::AGRONOMO->value,
            RoleType::BODEGUERO->value,
            RoleType::ESTANQUERO->value,
            RoleType::ASISTENTE->value,
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
    public function canUseMovementType(User $user, MovementType $type): bool
    {
        if ($user->role === RoleType::ESTANQUERO->value) {
            return in_array($type, [MovementType::PREPARACION, MovementType::TRASLADO]);
        }

        // Puedes definir reglas adicionales para otros roles si es necesario
        return true;
    }
    public function before(User $user, $ability)
    {
        if ($user->isAdmin()) {
            return true;
        }
    }

    public function complete(User $user, Movimiento $movimiento): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN->value,
            RoleType::AGRONOMO->value,
            RoleType::BODEGUERO->value,
            RoleType::ASISTENTE->value,
        ]);
    }
}

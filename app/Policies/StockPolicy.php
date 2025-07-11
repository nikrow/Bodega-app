<?php

namespace App\Policies;

use App\Models\Stock;
use App\Models\User;
use App\Enums\RoleType;

class StockPolicy
{
    /**
     * Determinar si el usuario puede acceder al recurso (ver la lista).
     */
    public function viewAny(User $user): bool
    {
        // Todos los roles especificados pueden ver la lista de stocks
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::BODEGUERO,
            RoleType::ASISTENTE,
            RoleType::ESTANQUERO,
            RoleType::USUARIO,
            RoleType::SUPERUSER,
        ]);
    }

    /**
     * Determinar si el usuario puede ver un registro específico.
     */
    public function view(User $user, Stock $stock): bool
    {
        // Los estanqueros solo pueden ver stocks de sus bodegas asignadas
        if ($user->role === RoleType::ESTANQUERO) {
            return $user->warehouses()->pluck('warehouses.id')->contains($stock->warehouse_id);
        }

        // Otros roles pueden ver cualquier stock
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
        return $user->role === RoleType::ADMIN;
    }

    /**
     * Determinar si el usuario puede actualizar un registro.
     */
    public function update(User $user, Stock $stock): bool
    {
        return $user->role === RoleType::ADMIN;
    }

    /**
     * Determinar si el usuario puede eliminar un registro.
     */
    public function delete(User $user, Stock $stock): bool
    {
        return $user->role === RoleType::ADMIN;
    }

    /**
     * Determinar si el usuario puede restaurar un registro.
     */
    public function restore(User $user, Stock $stock): bool
    {
        return $user->role === RoleType::ADMIN;
    }

    /**
     * Determinar si el usuario puede eliminar permanentemente un registro.
     */
    public function forceDelete(User $user, Stock $stock): bool
    {
        return $user->role === RoleType::ADMIN;
    }
}
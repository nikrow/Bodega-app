<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Enums\RoleType;

class OrderPolicy
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
            RoleType::ASISTENTE->value,
        ]);
    }

    /**
     * Determinar si el usuario puede ver un registro específico.
     */
    public function view(User $user, Order $order): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN->value,
            RoleType::AGRONOMO->value,
            RoleType::BODEGUERO->value,
            RoleType::ASISTENTE->value,
        ]);
    }

    /**
     * Determinar si el usuario puede crear un nuevo registro.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN->value,
            RoleType::AGRONOMO->value,
        ]);
    }

    /**
     * Determinar si el usuario puede actualizar un registro.
     */
    public function update(User $user, Order $order): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN->value,
            RoleType::AGRONOMO->value,
        ]);
    }

    /**
     * Determinar si el usuario puede eliminar un registro.
     */
    public function delete(User $user, Order $order): bool
    {
        return $user->role === RoleType::ADMIN->value;
    }

    /**
     * Determinar si el usuario puede restaurar un registro.
     */
    public function restore(User $user, Order $order): bool
    {
        return $user->role === RoleType::ADMIN->value;
    }

    /**
     * Determinar si el usuario puede eliminar permanentemente un registro.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        return $user->role === RoleType::ADMIN->value;
    }
}
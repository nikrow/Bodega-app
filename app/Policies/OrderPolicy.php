<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\Order;
use App\Models\User;

class OrderPolicy
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
    public function view(User $user, Order $order): bool
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
            RoleType::SUPERUSER
        ]);
    }

    /**
     * Determinar si el usuario puede actualizar un registro.
     */
    public function update(User $user, Order $order): bool
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
    public function delete(User $user, Order $order): bool
    {
        return $user->role === RoleType::ADMIN;
    }

    /**
     * Determinar si el usuario puede restaurar un registro.
     */
    public function restore(User $user, Order $order): bool
    {
        return $user->role === RoleType::ADMIN;
    }

    /**
     * Determinar si el usuario puede eliminar permanentemente un registro.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        return $user->role === RoleType::ADMIN;
    }

    public function complete(User $user, Order $order): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::SUPERUSER
        ]);
    }
    public function createApplication(User $user, Order $order): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::ASISTENTE,
            RoleType::SUPERUSER
        ]);
    }
    public function audit(User $user): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN,
            RoleType::AGRONOMO,
            RoleType::SUPERUSER
        ]);
    }
}

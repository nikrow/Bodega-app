<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Enums\RoleType;

class ProductPolicy
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
            RoleType::ESTANQUERO->value,
            RoleType::USUARIO->value,
        ]);
    }

    /**
     * Determinar si el usuario puede ver un registro específico.
     */
    public function view(User $user, Product $product): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN->value,
            RoleType::AGRONOMO->value,
            RoleType::BODEGUERO->value,
            RoleType::ASISTENTE->value,
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
            RoleType::AGRONOMO->value,
        ]);
    }

    /**
     * Determinar si el usuario puede actualizar un registro.
     */
    public function update(User $user, Product $product): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN->value,
            RoleType::AGRONOMO->value,
        ]);
    }

    /**
     * Determinar si el usuario puede eliminar un registro.
     */
    public function delete(User $user, Product $product): bool
    {
        return $user->role === RoleType::ADMIN->value;
    }

    /**
     * Determinar si el usuario puede restaurar un registro.
     */
    public function restore(User $user, Product $product): bool
    {
        return $user->role === RoleType::ADMIN->value;
    }

    /**
     * Determinar si el usuario puede eliminar permanentemente un registro.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        return $user->role === RoleType::ADMIN->value;
    }

    public function restoreAudit(User $user, Product $product): bool
    {
        return $user->role === RoleType::ADMIN->value;
    }
    public function audit(User $user, Product $product): bool
    {
        return in_array($user->role, [
            RoleType::ADMIN->value,
            RoleType::AGRONOMO->value,
        ]);
    }
    
}


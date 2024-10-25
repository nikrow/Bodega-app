<?php

namespace App\Policies;

use App\Enums\RoleType;
use App\Models\User;

class ActivityLogPolicy
{
    /**
     * Determina si el usuario puede ver cualquier registro de actividad.
     */
    public function viewAny(User $user): bool
    {
        return $user->role === RoleType::ADMIN->value;
    }

    /**
     * Determina si el usuario puede ver un registro de actividad específico.
     */
    public function view(User $user, User $model): bool
    {
        return $user->role === RoleType::ADMIN->value;
    }

    /**
     * Impide la creación de nuevos registros de actividad.
     */
    public function create(User $user): bool
    {
        return false; // No se permite crear registros
    }

    /**
     * Impide la actualización de registros de actividad.
     */


    /**
     * Impide la eliminación de registros de actividad.
     */
    public function delete(User $user, User $model): bool
    {
        return false; // No se permite eliminar registros
    }

    /**
     * Impide la restauración de registros de actividad.
     */
    public function restore(User $user, User $model): bool
    {
        return false; // No se permite restaurar registros
    }

    /**
     * Impide la eliminación permanente de registros de actividad.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return false; // No se permite eliminar registros de forma permanente
    }
}

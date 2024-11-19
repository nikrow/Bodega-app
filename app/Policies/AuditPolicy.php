<?php

namespace App\Policies;

use App\Enums\RoleType;
use Illuminate\Auth\Access\HandlesAuthorization;
use OwenIt\Auditing\Models\Audit;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AuditPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any audits.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the audit.
     */
    public function view(User $user, Audit $audit): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the audit.
     * (Opcional: Puedes ajustar según tus necesidades)
     */
    public function delete(User $user, Audit $audit): bool
    {
        return false; // Por ejemplo, nadie puede eliminar auditorías
    }
}

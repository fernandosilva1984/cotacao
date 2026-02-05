<?php

namespace App\Policies;

use App\Models\Cotacao;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CotacaoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('Ver cotação');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Cotacao $cotacao): bool
    {
        return $user->hasPermissionTo('Ver cotação');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('Criar cotação');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Cotacao $cotacao): bool
    {
        return $user->hasPermissionTo('Editar cotação');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Cotacao $cotacao): bool
    {
        return $user->hasPermissionTo('Deletar cotação');
    }

   
}
<?php

namespace App\Policies;

use App\Models\Fornecedor;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class FornecedorPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('Ver fornecedor');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Fornecedor $fornecedor): bool
    {
        return $user->hasPermissionTo('Ver fornecedor');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('Criar fornecedor');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Fornecedor $fornecedor): bool
    {
        return $user->hasPermissionTo('Editar fornecedor');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Fornecedor $fornecedor): bool
    {
        return $user->hasPermissionTo('Deletar fornecedor');
    }

    
}
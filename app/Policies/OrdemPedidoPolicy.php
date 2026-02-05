<?php

namespace App\Policies;

use App\Models\OrdemPedido;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrdemPedidoPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('Ver ordem de pedido');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OrdemPedido $ordemPedido): bool
    {
        return $user->hasPermissionTo('Ver ordem de pedido');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('Criar ordem de pedido');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OrdemPedido $ordemPedido): bool
    {
        return $user->hasPermissionTo('Editar ordem de pedido');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OrdemPedido $ordemPedido): bool
    {
        return $user->hasPermissionTo('Deletar ordem de pedido');
    }

    
}
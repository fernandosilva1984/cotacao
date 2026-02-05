<?php

namespace App\Filament\Admin\Resources\Roles\Pages;

use App\Filament\Admin\Resources\Roles\RoleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageRoles extends ManageRecords
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Adicionar Perfil')
                ->icon('heroicon-o-plus')
                ->modalHeading('Adicionar Perfil')
                ->modalSubmitActionLabel('Salvar')
                ->successNotificationTitle('Perfil cadastrado com sucesso!')
                ->modalCancelActionLabel('Cancelar'),
        ];
    }
}
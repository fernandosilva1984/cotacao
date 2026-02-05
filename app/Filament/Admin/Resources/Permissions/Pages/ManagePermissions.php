<?php

namespace App\Filament\Admin\Resources\Permissions\Pages;

use App\Filament\Admin\Resources\Permissions\PermissionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePermissions extends ManageRecords
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Adicionar Permissão')
                ->icon('heroicon-o-plus')
                ->modalHeading('Adicionar Permissão')
                ->modalSubmitActionLabel('Salvar')
                ->successNotificationTitle('Permissão cadastrada com sucesso!')
                ->modalCancelActionLabel('Cancelar'),
        ];
    }
}
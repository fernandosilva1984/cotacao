<?php

namespace App\Filament\Admin\Resources\Empresas\Pages;

use App\Filament\Admin\Resources\Empresas\EmpresaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEmpresas extends ManageRecords
{
    protected static string $resource = EmpresaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
            ->label('Adicionar Empresa')
                ->icon('heroicon-o-plus')
                ->modalHeading('Adicionar Empresa')
                ->modalSubmitActionLabel('Salvar')
                ->successNotificationTitle('Empresa cadastrada com sucesso!')
                ->modalCancelActionLabel('Cancelar')
        ];
    }
}
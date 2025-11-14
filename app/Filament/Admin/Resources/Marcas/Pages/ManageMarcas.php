<?php

namespace App\Filament\Admin\Resources\Marcas\Pages;

use App\Filament\Admin\Resources\Marcas\MarcaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Imports\MarcaImporter;
use Filament\Actions\ImportAction;

class ManageMarcas extends ManageRecords
{
    protected static string $resource = MarcaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Adicionar Marca')
                ->icon('heroicon-o-plus')
                ->modalHeading('Adicionar Marca')
                ->modalSubmitActionLabel('Salvar')
                ->successNotificationTitle('Marca cadastrada com sucesso!')
                ->modalCancelActionLabel('Cancelar'),
            ImportAction::make()
                ->importer(MarcaImporter::class)
                ->modalHeading('Importar Marca(s)')
                ->tooltip('Importar Marcas via CSV')
                ->icon('heroicon-o-inbox-arrow-down')
                ->label('Importar Marca(s)')
                ->color('success'),
        ];
    }
}
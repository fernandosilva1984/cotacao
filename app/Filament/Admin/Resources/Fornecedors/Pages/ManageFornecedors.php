<?php

namespace App\Filament\Admin\Resources\Fornecedors\Pages;

use App\Filament\Admin\Resources\Fornecedors\FornecedorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Imports\FornecedorImporter;
use Filament\Actions\ImportAction;

class ManageFornecedors extends ManageRecords
{
    protected static string $resource = FornecedorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Adicionar Fornecedor')
                ->icon('heroicon-o-plus')
                ->modalHeading('Adicionar Fornecedor')
                ->modalSubmitActionLabel('Salvar')
                ->successNotificationTitle('Fornecedor cadastrado com sucesso!')
                ->modalCancelActionLabel('Cancelar'),
            ImportAction::make()
                ->importer(FornecedorImporter::class)
                ->modalHeading('Importar Fornecedor(es)')
                ->tooltip('Importar Fornecedores via CSV')
                ->icon('heroicon-o-inbox-arrow-down')
                ->label('Importar Fornecedor(es)')
                ->color('success'),
        ];
    }
}
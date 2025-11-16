<?php

namespace App\Filament\Admin\Resources\Produtos\Pages;

use App\Filament\Admin\Resources\Produtos\ProdutoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Imports\ProdutoImporter;
use Filament\Actions\ImportAction;

class ManageProdutos extends ManageRecords
{
    protected static string $resource = ProdutoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Adicionar Produto')
                ->icon('heroicon-o-plus')
                ->modalHeading('Adicionar Produto')
                ->modalSubmitActionLabel('Salvar')
                ->successNotificationTitle('Produto cadastrado com sucesso!')
                ->modalCancelActionLabel('Cancelar'),
            ImportAction::make()
                ->importer(ProdutoImporter::class)
                ->modalHeading('Importar Produto(s)')
                ->tooltip('Importar Produtos via CSV')
                ->icon('heroicon-o-inbox-arrow-down')
                ->label('Importar Produto(s)')
                ->color('success'),
        ];
    }
}

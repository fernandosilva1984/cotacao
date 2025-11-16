<?php

namespace App\Filament\Admin\Resources\Cotacaos\Pages;

use App\Filament\Admin\Resources\Cotacaos\CotacaoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCotacaos extends ManageRecords
{
    protected static string $resource = CotacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Adicionar Cotação')
                ->icon('heroicon-o-plus')
                ->modalHeading('Adicionar Cotação')
                ->modalSubmitActionLabel('Salvar')
                ->successNotificationTitle('Cotação cadastrada com sucesso!')
                ->modalCancelActionLabel('Cancelar'),
        ];
    }
}

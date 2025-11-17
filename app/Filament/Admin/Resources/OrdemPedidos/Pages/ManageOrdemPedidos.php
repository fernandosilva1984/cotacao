<?php

namespace App\Filament\Admin\Resources\OrdemPedidos\Pages;

use App\Filament\Admin\Resources\OrdemPedidos\OrdemPedidoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageOrdemPedidos extends ManageRecords
{
    protected static string $resource = OrdemPedidoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

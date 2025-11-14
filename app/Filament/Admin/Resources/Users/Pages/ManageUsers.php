<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;
    //protected ?string $heading = 'Novo Requerimento';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Adicionar Usuário')
                ->icon('heroicon-o-plus')
                ->modalHeading('Adicionar Usuário')
                ->modalSubmitActionLabel('Salvar')
                ->successNotificationTitle('Usuário cadastrado com sucesso!')
                ->modalCancelActionLabel('Cancelar')
               // ->createAnother(false) // Se quiser desativar o "Salvar e Criar outro",
        ];
    }
    
}
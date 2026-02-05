<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Imports\UserImporter;
use Filament\Actions\ImportAction;
use Filament\Support\Enums\IconPosition;

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
                //->iconPosition(IconPosition::After)
                ->successNotificationTitle('Usuário cadastrado com sucesso!')
                ->modalCancelActionLabel('Cancelar'),
               // ->createAnother(false) // Se quiser desativar o "Salvar e Criar outro",
               ImportAction::make()
                ->importer(UserImporter::class)
                ->modalHeading('Importar Usuário(s)')
                ->tooltip('Importar usuários via CSV')
                ->icon('heroicon-o-inbox-arrow-down')
                //->iconPosition(IconPosition::After)
                ->label('Importar Usuário(s)')
                ->color('success')
                ->visible(fn (): bool => auth()->user()->hasRole('Administrador')),
        ];
    }
    
}
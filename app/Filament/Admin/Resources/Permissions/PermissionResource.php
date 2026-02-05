<?php

namespace App\Filament\Admin\Resources\Permissions;

use App\Filament\Admin\Resources\Permissions\Pages\ManagePermissions;
use App\Models\Permission;
use BackedEnum;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    //protected static string|BackedEnum|null $navigationIcon = Heroicon::shield_exclamation;

    protected static ?string $recordTitleAttribute = 'Permissões';

    protected static string|BackedEnum|null $navigationIcon ='heroicon-o-shield-exclamation';
    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'permissoes';

    protected static ?string $navigationLabel = 'Permissões';

    protected static string| UnitEnum | null $navigationGroup = 'Administração';

    protected static ?string $pluralModelLabel = 'Permissões';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome da Permissão')
                    ->required(),
                Select::make('roles')
                            ->label('Perfis')
                            ->relationship('roles', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable(),
                
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('Nome da Permissão'),

                TextEntry::make('roles.name')
                    ->label('Perfil(s)')
                    ->getStateUsing(function ($record) {
                        if ($record->roles->isEmpty()) {
                            return 'Nenhum perfil atribuído';
                        }
                        return $record->roles->pluck('name');
                         })
                    ->limitList(10) // Limita a exibição inicial
                    ->badge() // Opcional: para um visual melhor
                    ->color('success'),
                TextEntry::make('created_at')
                    ->label('Criado em')
                    ->dateTime(format: 'd/m/Y H:i'),
                TextEntry::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime(format: 'd/m/Y H:i'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Permissões')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome da Permissão')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->label('Perfis')
                    ->badge()
                    ->searchable()
                    ->separator(', ')
                    ->color('success')
                    ->limitList(10), // Limita a exibição inicial
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime(format: 'd/m/Y H:i')
                    ->sortable(),
                
                   
                
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->hiddenlabel()
                    ->tooltip('Ver Detalhes')
                    ->modalHeading('Visualizar Permissão'),
                EditAction::make()
                    ->hiddenlabel()
                    ->tooltip('Editar Permissão')
                    ->color('success')
                    ->modalHeading('Editar Permissão'),
                DeleteAction::make()
                    ->hiddenlabel()
                    ->tooltip('Excluir Permissão')
                    ->modalHeading('Deseja Excluir essa permissão?')
                    ->modalDescription('Essa ação não pode ser desfeita.')
                    ->modalButton('Excluir')
                    ->modalWidth('md') // ✅ Correção: Usando o enum corretamente
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePermissions::route('/'),
        ];
    }
}
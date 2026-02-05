<?php

namespace App\Filament\Admin\Resources\Roles;

use App\Filament\Admin\Resources\Roles\Pages\ManageRoles;
use App\Models\Role;
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

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

   // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Perfil';
    protected static string|BackedEnum|null $navigationIcon ='heroicon-o-identification';

    //protected static ?string $recordTitleAttribute = 'Empresas';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'perfis';

    protected static ?string $navigationLabel = 'Perfis';

    protected static string| UnitEnum | null $navigationGroup = 'Administração';

    protected static ?string $pluralModelLabel = 'Perfis';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nome do Perfil')
                    ->required(),
                Select::make('permissions')
                            ->label('Permissões')
                            ->relationship('permissions', 'name')
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
                    ->label('Nome do Perfil'),
                TextEntry::make('permissions.name')
                    ->listWithLineBreaks()
                    //->relationship('permissions', 'name')
                    ->label('Permissões')
                    ->getStateUsing(function ($record) {
                        if ($record->permissions->isEmpty()) { 
                            return 'Nenhuma permissão atribuída';
                        }
                            return $record->permissions->pluck('name');
                        })
                    ->badge() // Opcional: para um visual melhor
                    ->color('success')
                    ->limitList(10), // Limita a exibição inicial
                TextEntry::make('created_at')
                    ->label('Criado em')
                    ->dateTime(format: 'd/m/Y H:i'  ),
                TextEntry::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime(format: 'd/m/Y H:i'  ),
                    
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Perfil')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome do Perfil')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('permissions.name')
                    ->label('Permissões')
                    ->badge()
                    ->searchable()
                    ->separator(', ')
                    ->color('success')
                    ->limitList(5), 
               
                TextColumn::make('created_at')

                    ->dateTime(format: 'd/m/Y H:i')
                    ->sortable()
                    ->label('Criado em'),
                
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make()
                    ->hiddenlabel()
                    ->tooltip('Ver Detalhes')
                    ->modalHeading('Visualizar Perfil'),
                EditAction::make()
                    ->hiddenlabel()
                    ->tooltip('Editar Perfil')
                    ->color('success')
                    ->modalHeading('Editar Perfil'),
                DeleteAction::make()
                    ->hiddenlabel()
                    ->tooltip('Excluir Perfil')
                    ->modalHeading('Deseja Excluir esse perfil?')
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
            'index' => ManageRoles::route('/'),
        ];
    }
}
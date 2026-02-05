<?php

namespace App\Filament\Admin\Resources\Fornecedors;

use App\Filament\Admin\Resources\Fornecedors\Pages\ManageFornecedors;
use App\Models\Fornecedor;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;
use Filament\Schemas\Components\Section;

class FornecedorResource extends Resource
{
    protected static ?string $model = Fornecedor::class;

    protected static string|BackedEnum|null $navigationIcon ='heroicon-o-truck';

    protected static ?string $recordTitleAttribute = 'Fornecedores';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'fornecedores';

    protected static ?string $navigationLabel = 'Fornecedores';

    protected static string | UnitEnum | null $navigationGroup = 'Cadastros';

    protected static ?string $pluralModelLabel = 'Fornecedores';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados do Fornecedor')
                    ->schema([
                        Hidden::make('id_empresa')
                            ->default(fn () => auth()->user()->id_empresa),
                        TextInput::make('nome')
                            ->required(),
                        TextInput::make('endereco')
                            ->label('Endereço'),
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email(),
                        TextInput::make('razao_social')
                            ->label('Razão Social'),
                        TextInput::make('nome_fantasia')
                            ->label('Nome Fantasia'),
                        Toggle::make('status')
                            ->inline(false)
                            ->required()
                            ->default(true),
                    ])
                    ->columns(2),
            ])
            ->columns(1);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
               Section::make('Dados do Fornecedor')
                    ->schema([
                        TextEntry::make('nome'),
                        TextEntry::make('endereco')
                            ->label('Endereço'),
                        TextEntry::make('email')
                            ->label('E-mail'),
                        TextEntry::make('razao_social')
                            ->label('Razão Social')
                            ,
                        TextEntry::make('nome_fantasia')
                            ->label('Nome Fantasia'),
                        IconEntry::make('status')
                            ->boolean(),
                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime(format: 'd/m/Y H:i:s'),
                        TextEntry::make('empresa.nome_fantasia')
                            ->label('Empresa')
                            ->visible(fn () => auth()->user()->is_master),
                        
                    ])
                    ->columns(2),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Fornecedores')
            ->columns([
                
                TextColumn::make('nome')
                    ->searchable(),
                TextColumn::make('endereco')
                    ->label('Endereço')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
               
                IconColumn::make('status')
                    ->alignCenter()
                    ->boolean(),
                TextColumn::make('empresa.nome_fantasia')
                    //->relationship('empresa', 'nome_fantasia')
                    ->label('Empresa')
                    ->visible(fn () => auth()->user()->is_master)
                    ->searchable()
                    ,
                
                
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
               ViewAction::make()
                    ->label('')
                    ->tooltip('Ver Detalhes')
                    ->modalHeading('Visualizar Fornecedor'),
                EditAction::make()
                    ->label('')
                    ->tooltip('Editar Fornecedor')
                    ->color('success')
                    ->modalHeading('Editar Fornecedor'),
                DeleteAction::make()
                    ->label('')
                    ->tooltip('Excluir Fornecedor')
                    ->modalHeading('Deseja Excluir esse fornecedor?')
                    ->modalDescription('Essa ação não pode ser desfeita.')
                    ->modalButton('Excluir')
                    ->modalWidth('md') // ✅ Correção: Usando o enum corretamente
                    ->requiresConfirmation(),
            ])
            ->toolbarActions([
                
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFornecedors::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (!auth()->user()->hasrole('Administrador')) {
            return $query->where('id_empresa', auth()->user()->id_empresa);
        }

        return $query;
}
}
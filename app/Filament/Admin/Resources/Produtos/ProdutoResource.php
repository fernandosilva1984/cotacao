<?php

namespace App\Filament\Admin\Resources\Produtos;

use App\Filament\Admin\Resources\Produtos\Pages\ManageProdutos;
use App\Models\Produto;
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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
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
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;

class ProdutoResource extends Resource
{
    protected static ?string $model = Produto::class;

     protected static string|BackedEnum|null $navigationIcon ='heroicon-o-cube';

    protected static ?string $recordTitleAttribute = 'Produtos';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'produtos';

    protected static ?string $navigationLabel = 'Produtos';

    protected static string | UnitEnum | null $navigationGroup = 'Cadastros';

    protected static ?string $pluralModelLabel = 'Produtos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([ 
                Section::make('Dados do Produto')
                    ->schema([
                        Hidden::make('id_empresa')
                            ->default(fn () => auth()->user()->id_empresa),
                        Select::make('id_marca')
                            ->relationship(name: 'marca',
                                titleAttribute: 'nome',
                                modifyQueryUsing: fn (Builder $query) => $query->where('id_empresa', auth()->user()->id_empresa)
                                    ->where('status', true)
                                )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                Hidden::make('id_empresa')
                                    ->default(fn () => auth()->user()->id_empresa),
                                TextInput::make('nome')
                                    ->required()
                                    ->maxLength(255),
                                Toggle::make('status')
                                    ->default(true),
                            ]),
                        TextInput::make('descricao')
                            ->label('Descrição do Produto')
                            ->required(),
                        Textarea::make('observacao')
                            ->label('Observação')
                            ->default(null)
                            ->columnSpanFull(),
                        TextInput::make('codigo_barras')
                            ->label('Código de Barras')
                            ->default(null),
                        Select::make('unidade_medida')
                            ->options([
                                'UN' => 'Unidade',
                                'PC' => 'Peça',
                                'KG' => 'Quilograma',
                                'GR' => 'Grama',
                                'LT' => 'Litro',
                                'ML' => 'Mililitro',
                                'M' => 'Metro',
                                'CM' => 'Centímetro',
                            ])
                            ->default('UN')
                            ->required(),
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
                Section::make('Dados do Produto')
                ->schema([
                    TextEntry::make('descricao')
                        ->label('Descrição do Produto'),
                    TextEntry::make('marca.nome')
                        ->label('Marca'),
                    TextEntry::make('unidade_medida')
                        ->label('Unidade de Medida'),
                    TextEntry::make('codigo_barras')
                        ->label('Código de Barras'),
                    TextEntry::make('observacao')
                        ->label('Observação'),
                    TextEntry::make('created_at')
                        ->label('Criado em')
                        ->dateTime(format: 'd/m/Y H:i:s'),
                    IconEntry::make('status')
                        ->boolean(),
                    TextEntry::make('empresa.nome_fantasia')
                        ->label('Empresa')
                        ->visible(fn () => auth()->user()->hasrole('Administrador')),
                ])
                ->columns(2),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Produtos')
            ->modifyQueryUsing(fn (Builder $query) => 
                auth()->user()->hasrole('Administrador') 
                    ? $query->whereIn('id_empresa', auth()->user()->empresa->pluck('id')) // Ajuste conforme sua relação de empresas
                    : $query->where('id_empresa', auth()->user()->id_empresa)
            )
            ->columns([
                TextColumn::make('descricao')
                    ->label('Descrição do Produto')               
                    ->searchable(),
                TextColumn::make('marca.nome')
                    ->label('Marca')
                    ->sortable(),
                TextColumn::make('unidade_medida')
                    ->label('Unidade de Medida')
                    ->searchable()
                    ->alignCenter(true),
                IconColumn::make('status')
                    ->boolean()
                    ->alignCenter(true),
                TextColumn::make('empresa.nome_fantasia')
                    //->relationship('empresa', 'nome_fantasia')
                    ->label('Empresa')
                    ->visible(fn () => auth()->user()->hasrole('Administrador'))
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
                    ->modalHeading('Visualizar Produto'),
                EditAction::make()
                    ->label('')
                    ->tooltip('Editar Produto')
                    ->color('success')
                    ->modalHeading('Editar Produto'),
                DeleteAction::make()
                    ->label('')
                    ->tooltip('Excluir Produto')
                    ->modalHeading('Deseja Excluir esse produto?')
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
            'index' => ManageProdutos::route('/'),
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
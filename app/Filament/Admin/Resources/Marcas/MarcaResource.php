<?php

namespace App\Filament\Admin\Resources\Marcas;

use App\Filament\Admin\Resources\Marcas\Pages\ManageMarcas;
use App\Models\Marca;
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

class MarcaResource extends Resource
{
    protected static ?string $model = Marca::class;

    protected static string|BackedEnum|null $navigationIcon ='heroicon-o-tag';

    protected static ?string $recordTitleAttribute = 'Marcas';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'marcas';

    protected static ?string $navigationLabel = 'Marcas';

    protected static string | UnitEnum | null $navigationGroup = 'Cadastros';

    protected static ?string $pluralModelLabel = 'Marcas';


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
                Toggle::make('status')
                    ->inline(false)
                    ->default(true)
                    ->required(),
                    ])
                    ->columns(2),
            ])
            ->columns(1);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados da Marca')
                    ->schema([
                        TextEntry::make('nome'),
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
            ->recordTitleAttribute('Marcas')
            ->columns([
                TextColumn::make('nome')
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
                    ->modalHeading('Visualizar Marca'),
                EditAction::make()
                    ->label('')
                    ->tooltip('Editar Marca')
                    ->color('success')
                    ->modalHeading('Editar Marca'),
                DeleteAction::make()
                    ->label('')
                    ->tooltip('Excluir Marca')
                    ->modalHeading('Deseja Excluir essa marca?')
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
            'index' => ManageMarcas::route('/'),
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
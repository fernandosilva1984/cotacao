<?php

namespace App\Filament\Admin\Resources\Empresas;

use App\Filament\Admin\Resources\Empresas\Pages\ManageEmpresas;
use App\Models\Empresa;
use BackedEnum;
use UnitEnum;
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
use Filament\Schemas\Components\Section;

class EmpresaResource extends Resource
{
    protected static ?string $model = Empresa::class;

    protected static string|BackedEnum|null $navigationIcon ='heroicon-o-building-office';

    protected static ?string $recordTitleAttribute = 'Empresas';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'empresas';

    protected static ?string $navigationLabel = 'Empresas';

    protected static string | UnitEnum | null $navigationGroup = 'Administração';

    protected static ?string $pluralModelLabel = 'Empresas';
    
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações do Usuário')
                    ->schema([
                        TextInput::make('razao_social')
                            ->label('Razão Social')
                            ->required(),
                        TextInput::make('nome_fantasia')
                            ->label('Nome Fantasia')
                            ->required(),
                        TextInput::make('endereco')
                            ->label('Endereço')
                            ->required(),
                        TextInput::make('bairro')
                            ->label('Bairro')
                            ->required(),
                        TextInput::make('cidade')
                            ->label('Cidade')
                            ->required(),
                        TextInput::make('cnpj')
                            ->label('CNPJ')
                            ->maxLength(18)
                            ->mask('99.999.999/9999-99')
                            ->unique(ignoreRecord: true)
                            ->required(),
                        TextInput::make('contato')
                            ->required(),
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required(),
                    /* TextInput::make('email_host')
                            ->email(),
                        TextInput::make('email_port')
                            ->email(),
                        TextInput::make('email_username')
                            ->email(),
                        TextInput::make('email_password')
                            ->email()
                            ->password(),*/
                        Toggle::make('status')
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
                Section::make('Informações do Usuário')
                    ->schema([
                        TextEntry::make('razao_social')
                            ->label('Razão Social'),
                        TextEntry::make('nome_fantasia')
                            ->label('Nome Fantasia'),
                        TextEntry::make('endereco'),
                        TextEntry::make('bairro'),
                        TextEntry::make('cidade'),
                        TextEntry::make('cnpj')
                            ->label('CNPJ'),
                        TextEntry::make('contato'),
                        TextEntry::make('email')
                            ->label('E-mail'),
                    
                        IconEntry::make('status')
                            ->boolean(),
                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime(format: 'd/m/Y H:i:s'),
                    ])
                    ->columns(2),
                
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Empresas')
            ->columns([
                TextColumn::make('razao_social')
                    ->label('Razão Social')
                    ->searchable(),
                TextColumn::make('nome_fantasia')
                    ->label('Nome Fantasia')
                    ->searchable(),
                TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->searchable(),               
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                IconColumn::make('status')
                    ->boolean()
                    ->alignCenter(),
                
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                 ViewAction::make()
                    ->label('')
                    ->tooltip('Ver Detalhes')
                    ->modalHeading('Visualizar Empresa'),
                EditAction::make()
                    ->label('')
                    ->tooltip('Editar Empresa')
                    ->modalHeading('Editar Empresa'),
                DeleteAction::make()
                    ->label('')
                    ->tooltip('Excluir Empresa')
                    ->modalHeading('Deseja Excluir essa empresa?')
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
            'index' => ManageEmpresas::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Users\Pages\ManageUsers;
use App\Models\User;
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
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
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
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon ='heroicon-o-users';

    protected static ?string $recordTitleAttribute = 'Usuários';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'usuarios';

    protected static ?string $navigationLabel = 'Usuários';

    protected static string | UnitEnum | null $navigationGroup = 'Administração';

    protected static ?string $pluralModelLabel = 'Usuários';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações do Usuário')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nome completo')
                            ->required(),
                        TextInput::make('email')
                            ->label('E-mail')
                            ->email()
                            ->required(),   
                        //DateTimePicker::make('email_verified_at'),
                        TextInput::make('password')
                            ->label('Senha')
                            ->password()
                            ->revealable()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                           // ->rule(Password::default())
                            ->maxLength(255)
                            ->same('password_confirmation') // Valida que é igual ao campo de confirmação
                            ->live(onBlur: true), // Atualiza validação em tempo real
                        TextInput::make('password_confirmation')
                            ->label('Confirmar Senha')
                            ->password()
                            ->revealable()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->dehydrated(false)
                            ->rule('required_with:password'),
                    ])
                    ->columns(2),
                Section::make('Configurações')
                    ->schema([
                        Select::make('id_empresa')
                            ->relationship('empresa', 'nome_fantasia')
                            ->searchable()
                            ->preload()
                            ,
                        Toggle::make('status')
                            ->required()
                            ->default(true)
                            ->inline(false)
                            ->onColor('success'),
                        Toggle::make('is_master')
                            ->label('Administrador Master')
                            ->required()
                            ->default(false)
                            ->inline(false)
                            ->onColor('success'),
                    ])
                    ->columns(3),
            ])
            ->columns(1);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informações do Usuário')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nome completo'),
                        TextEntry::make('email')
                            ->label('E-mail'),
                   ])
                ->columns(2),
                Section::make('Configurações')
                    ->schema([
                        TextEntry::make('empresa.nome_fantasia')
                            ->label('Empresa'),
                        IconEntry::make('status')
                            ->boolean(),
                        IconEntry::make('is_master')
                            ->label('Administrador Master')
                            ->boolean()
                            ->alignCenter(),
                    ])
                    ->columns(3),
            ])
            ;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Usuários')
            ->columns([
                TextColumn::make('name')
                    ->label('Nome completo')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),
                TextColumn::make('empresa.nome_fantasia')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('status')
                    ->boolean()
                    ->alignCenter(),
                IconColumn::make('is_master')
                ->label('Administrador Master')
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
                    ->modalHeading('Visualizar Usuário'),
                EditAction::make()
                    ->label('')
                    ->tooltip('Editar Usuário')
                    ->modalHeading('Editar Usuário'),
                DeleteAction::make()
                    ->label('')
                    ->tooltip('Excluir Usuário')
                    ->modalHeading('Deseja Excluir esse usuário?')
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
            'index' => ManageUsers::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
     public static function canViewAny(): bool
    {
        return auth()->user()->is_master;
    }
}
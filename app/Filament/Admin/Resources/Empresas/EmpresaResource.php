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
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

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
                            ->required()
                            ->inline(false),
                    ])
                    ->columns(2),
                    Section::make('Assinatura')
                ->schema([
                    DatePicker::make('data_ativacao')
                    ->label('Data de Ativação')
                    ->default(now()),
                    DatePicker::make('data_validade')
                        ->label('Data de Validade')
                        ->required()
                        ->default(now()->addDays(30)->format('Y-m-d')),
                    
                    Select::make('plano')
                        ->options([
                            'mensal' => 'Mensal (30 dias)',
                            'trimestral' => 'Trimestral (90 dias)',
                            'semestral' => 'Semestral (180 dias)',
                            'anual' => 'Anual (365 dias)',
                        ])
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, $set) {
                            $dias = match($state) {
                                'trial' => 15,
                                'mensal' => 30,
                                'trimestral' => 90,
                                'semestral' => 180,
                                'anual' => 365,
                                default => 30,
                            };
                            // Calcula a data
                            $dataValidade = now()->addDays($dias);
                            $set('data_validade', now()->addDays($dias)->format('Y-m-d'));
                            $set('valor_plano', match($state) {
                                'trial' => 0.00,
                                'mensal' => 49.90,
                                'trimestral' => 129.90,
                                'semestral' => 239.90,
                                'anual' => 360.00,
                                default => 0,
                            });
                        }),
                    
                    TextInput::make('valor_plano')
                        ->label('Valor do Plano (R$)')
                        ->numeric()
                        ->prefix('R$')
                        ->disabled(),
                    
                    TextInput::make('codigo_assinatura')
                        ->label('Código da Assinatura')
                        ->maxLength(255),
                    
                    Textarea::make('observacoes_assinatura')
                        ->label('Observações')
                        ->maxLength(65535)
                        ->columnSpanFull(),
                        ])
                        ->columns(2),
                   // ])   
                    
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
                TextColumn::make('data_validade')
                    ->label('Validade')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn ($state) => $state < now() ? 'danger' : 'success'),
                TextColumn::make('plano')
                    ->label('Plano')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'mensal' => 'primary',
                        'trimestral' => 'info',
                        'semestral' => 'warning',
                        'anual' => 'success',
                        default => 'gray',
                    }),
                IconColumn::make('status')
                    ->boolean()
                    ->alignCenter(),
                
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('renovar')
                    ->tooltip('Renovar Assinatura')
                    ->hiddenlabel()
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->form([
                        Select::make('plano')
                            ->options([
                                'mensal' => 'Mensal (30 dias)',
                                'trimestral' => 'Trimestral (90 dias)',
                                'semestral' => 'Semestral (180 dias)',
                                'anual' => 'Anual (365 dias)',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $dias = match($state) {
                                    'trial' => 15,
                                    'mensal' => 30,
                                    'trimestral' => 90,
                                    'semestral' => 180,
                                    'anual' => 365,
                                    default => 15,
                                };
                               // $set('data_validade', now()->addDays($dias)->format('Y-m-d'));
                                $set('valor_plano', match($state) {
                                    'trial' => 0.00,
                                    'mensal' => 49.90,
                                    'trimestral' => 129.90,
                                    'semestral' => 239.90,
                                    'anual' => 360.00,
                                    default => 0,
                                });
                                $set('dias', $dias);
                            }),
                        Hidden::make('valor_plano'),
                        Hidden::make('data_validade'),
                        Hidden::make('dias'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->renovar($data['dias'], $data['plano'], $data['valor_plano']);
                        
                        Notification::make()
                            ->success()
                            ->title('Assinatura renovada!')
                            ->body("Nova validade: {$record->data_validade->format('d/m/Y')}")
                            ->send();
                    }),
                  //  ->visible(fn ($record) => auth()->user()->hasPermissionTo('Renovar Assinatura')),
                 ViewAction::make()
                    ->label('')
                    ->tooltip('Ver Detalhes')
                    ->modalHeading('Visualizar Empresa'),
                EditAction::make()
                    ->label('')
                    ->tooltip('Editar Empresa')
                    ->color('success')
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
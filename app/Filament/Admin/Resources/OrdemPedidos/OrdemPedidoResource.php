<?php

namespace App\Filament\Admin\Resources\OrdemPedidos;

use App\Filament\Admin\Resources\OrdemPedidos\Pages\ManageOrdemPedidos;
use App\Models\OrdemPedido;
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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrdemPedidoResource extends Resource
{
    protected static ?string $model = OrdemPedido::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Ordem de Pedidos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id_empresa')
                    ->required()
                    ->numeric(),
                TextInput::make('id_usuario')
                    ->required()
                    ->numeric(),
                TextInput::make('id_fornecedor')
                    ->required()
                    ->numeric(),
                TextInput::make('id_cotacao')
                    ->numeric(),
                DatePicker::make('data')
                    ->required(),
                TextInput::make('numero')
                    ->required(),
                Textarea::make('observacao')
                    ->columnSpanFull(),
                TextInput::make('valor_total')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Select::make('status')
                    ->options([
            'pendente' => 'Pendente',
            'aprovada' => 'Aprovada',
            'entregue' => 'Entregue',
            'cancelada' => 'Cancelada',
        ])
                    ->default('pendente')
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id_empresa')
                    ->numeric(),
                TextEntry::make('id_usuario')
                    ->numeric(),
                TextEntry::make('id_fornecedor')
                    ->numeric(),
                TextEntry::make('id_cotacao')
                    ->numeric(),
                TextEntry::make('data')
                    ->date(),
                TextEntry::make('numero'),
                TextEntry::make('valor_total')
                    ->numeric(),
                TextEntry::make('status'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
                TextEntry::make('deleted_at')
                    ->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Ordem de Pedidos')
            ->columns([
                TextColumn::make('id_empresa')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('id_usuario')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('id_fornecedor')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('id_cotacao')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('data')
                    ->date()
                    ->sortable(),
                TextColumn::make('numero')
                    ->searchable(),
                TextColumn::make('valor_total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOrdemPedidos::route('/'),
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

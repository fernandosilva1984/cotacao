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
use Filament\Actions\Action;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use App\Models\OrdemPedidoItem;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Card;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\PlaceHolder;
use Illuminate\Support\Str;
use UnitEnum;
use Filament\Schemas\Components\Grid;
use App\Models\Cotacao;
use Filament\Infolists\Components\RepeatableEntry;

class OrdemPedidoResource extends Resource
{
    protected static ?string $model = OrdemPedido::class;

    protected static string|BackedEnum|null $navigationIcon ='heroicon-o-shopping-cart';

    protected static ?string $recordTitleAttribute = 'Ordens de Pedido';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'ordens';

    protected static ?string $navigationLabel = 'Ordens de Pedido';

    protected static string | UnitEnum | null $navigationGroup = 'Operacional';

    protected static ?string $pluralModelLabel = 'Ordens de Pedido';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Seleção da Cotação')
                    ->schema([
                        Select::make('id_cotacao')
                            ->label('Cotação')
                            ->relationship('cotacao', 'numero')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $set('ordens_por_fornecedor', []);
                                }
                            }),
                        DatePicker::make('data')
                            ->required()
                            ->default(now()),
                        Textarea::make('observacao_geral')
                            ->label('Observações Gerais (aplicam-se a todas as ordens)')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        Hidden::make('id_empresa')
                            ->default(fn () => auth()->user()->id_empresa),
                        Hidden::make('id_usuario')
                            ->default(fn () => auth()->user()->id),
                    ])->columns(2),
                Section::make('Seleção de Itens por Fornecedor')
                    ->schema([
                        Placeholder::make('info_itens')
                            ->label('Dados da Cotação')
                            ->content(function (Get $get) {
                                $idCotacao = $get('id_cotacao');
                                if (!$idCotacao) {
                                    return 'Selecione uma cotação para visualizar as respostas dos fornecedores.';
                                }
                                $cotacao = Cotacao::find($idCotacao);
                                return $cotacao ? "Cotação {$cotacao->numero} - Selecione os itens para gerar as ordens de pedido" : 'Cotação não encontrada';
                            }),
                       Placeholder::make('info_multiplas_ordens')
                            ->label('Observacões Importantes')
                            ->content('💡 **Atenção**: Itens de fornecedores diferentes serão agrupados em ordens de pedido separadas automaticamente.')
                            ->columnSpanFull(),
                        // Componente dinâmico para cada fornecedor
                        Grid::make()
                            ->schema(function (Get $get) {
                                $idCotacao = $get('id_cotacao');
                                if (!$idCotacao) {
                                    return [Placeholder::make('no_cotacao')->content('Nenhuma cotação selecionada')];
                                }
                                try {
                                    $cotacao = Cotacao::with(['fornecedores', 'items.produto', 'items.marca'])->find($idCotacao);
                                    if (!$cotacao) {
                                        return [Placeholder::make('cotacao_nao_encontrada')->content('Cotação não encontrada')];
                                    }
                                    $schemas = [];
                                    foreach ($cotacao->fornecedores as $fornecedor) {
                                        if ($fornecedor->pivot->status === 'respondida') {
                                            $valores = $cotacao->parsearRespostaFornecedor($fornecedor->pivot->resposta_fornecedor);
                                            if (!is_array($valores)) {
                                                continue;
                                            }
                                            $itemSchemas = [];
                                            $itemIndex = 0;
                                            $totalFornecedor = 0;
                                            foreach ($cotacao->items as $cotacaoItem) {
                                                if (isset($valores[$itemIndex])) {
                                                    $valorUnitario = $valores[$itemIndex];
                                                    $valorTotal = $cotacaoItem->quantidade * $valorUnitario;
                                                    $totalFornecedor += $valorTotal;
                                                    // Criar uma linha em grid para cada item (simulando tabela)
                                                    $itemSchemas[] = Grid::make(7)
                                                        ->schema([
                                                            Checkbox::make("item_{$fornecedor->id}_{$cotacaoItem->id}")
                                                                ->hiddenLabel()
                                                                ->reactive()
                                                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                                    self::atualizarOrdensPorFornecedor($set, $get);
                                                                }),
                                                            Placeholder::make("desc_{$fornecedor->id}_{$cotacaoItem->id}")
                                                                ->hiddenLabel()
                                                                ->content($cotacaoItem->descricao_produto)
                                                                ->extraAttributes(['class' => 'font-bold'])
                                                                ->columnSpan(2),
                                                            Placeholder::make("marca_{$fornecedor->id}_{$cotacaoItem->id}")
                                                                ->hiddenLabel()
                                                                ->content($cotacaoItem->descricao_marca ?? 'N/A')
                                                                ->extraAttributes(['class' => 'font-bold']),
                                                            Placeholder::make("qtd_{$fornecedor->id}_{$cotacaoItem->id}")
                                                                ->hiddenLabel()
                                                                ->content(number_format($cotacaoItem->quantidade, 0, ',', '.'))
                                                                ->extraAttributes(['class' => 'text-center']),
                                                            Placeholder::make("unit_{$fornecedor->id}_{$cotacaoItem->id}")
                                                                ->hiddenLabel()
                                                                ->content('R$ ' . number_format($valorUnitario, 2, ',', '.'))
                                                                ->extraAttributes(['class' => 'text-right']),
                                                            Placeholder::make("total_{$fornecedor->id}_{$cotacaoItem->id}")
                                                                ->hiddenLabel()
                                                                ->content('R$ ' . number_format($valorTotal, 2, ',', '.'))
                                                                ->extraAttributes(['class' => 'text-right font-bold']),
                                                        ])
                                                        ->columns(7);
                                                }
                                                $itemIndex++;
                                            }
                                            if (!empty($itemSchemas)) {
                                                // Adicionar cabeçalho da "tabela"
                                                array_unshift($itemSchemas, 
                                                    Grid::make(7)
                                                        ->schema([
                                                            Placeholder::make('header_sel')
                                                                ->content('#')
                                                                ->hiddenLabel()
                                                                ->extraAttributes(['class' => 'font-bold uppercase text-sm']),
                                                            Placeholder::make('header_desc')
                                                                ->hiddenLabel()
                                                                ->content('PRODUTO')
                                                                ->extraAttributes(['class' => 'font-bold uppercase text-sm'])
                                                                ->columnSpan(2),
                                                            Placeholder::make('header_marca')
                                                                ->hiddenLabel()
                                                                ->content('MARCA')
                                                                ->extraAttributes(['class' => 'font-bold uppercase text-sm'])
                                                                ->columnSpan(1),
                                                            Placeholder::make('header_qtd')
                                                                ->hiddenLabel()
                                                                ->content('QTD')
                                                                ->extraAttributes(['class' => 'text-center font-bold uppercase text-sm']),
                                                            Placeholder::make('header_unit')
                                                                ->hiddenLabel()
                                                                ->content('UNITÁRIO')
                                                                ->extraAttributes(['class' => 'text-right font-bold uppercase text-sm']),
                                                            Placeholder::make('header_total')
                                                                ->hiddenLabel()
                                                                ->content('TOTAL')
                                                                ->extraAttributes(['class' => 'text-right font-bold uppercase text-sm']),
                                                        ])
                                                        ->columns(7)
                                                );
                                                $schemas[] = Section::make("{$fornecedor->nome}")
                                                    ->description(function () use ($totalFornecedor) {
                                                        return 'Total potencial: R$ ' . number_format($totalFornecedor, 2, ',', '.');
                                                    })
                                                    ->schema($itemSchemas)
                                                    ->collapsible();
                                            }
                                        }
                                    }
                                    if (empty($schemas)) {
                                        return [Placeholder::make('sem_respostas')->content('Nenhum fornecedor respondeu esta cotação ainda.')];
                                    }
                                    return $schemas;
                                } catch (\Exception $e) {
                                    return [Placeholder::make('erro')->content('Erro ao carregar dados da cotação')];
                                }
                            })
                            ->columns(1),
                    ])
                    ->visible(fn (Get $get): bool => !is_null($get('id_cotacao'))),
                Section::make('Pré-visualização das Ordens de Pedido')
                    ->schema([
                        Placeholder::make('info_preview')
                            ->content(function (Get $get) {
                                $ordens = $get('ordens_por_fornecedor') ?? [];
                                $totalOrdens = count($ordens);
                                if ($totalOrdens === 0) {
                                    return 'Selecione itens acima para visualizar as ordens de pedido que serão criadas.';
                                }
                                return "Serão criadas {$totalOrdens} ordem(ns) de pedido:";
                            }),
                        Grid::make()
                            ->schema(function (Get $get) {
                                $ordens = $get('ordens_por_fornecedor') ?? [];
                                $schemas = [];
                                foreach ($ordens as $fornecedorId => $ordemData) {
                                    $fornecedorNome = $ordemData['fornecedor_nome'];
                                    $totalOrdem = $ordemData['total'];
                                    $quantidadeItens = count($ordemData['itens']);
                                    $schemas[] = Grid::make()
                                        ->schema([
                                            Placeholder::make("fornecedor_{$fornecedorId}")
                                                ->label("Ordem para: {$fornecedorNome}")
                                                ->content(
                                                    "{$quantidadeItens} item(s) | " .
                                                    "Total: R$ " . number_format($totalOrdem, 2, ',', '.')
                                                ),
                                            Textarea::make("observacao_fornecedor_{$fornecedorId}")
                                                ->label("Observações específicas para {$fornecedorNome}")
                                                ->placeholder("Observações específicas para esta ordem...")
                                                ->maxLength(65535),
                                        ]);
                                }
                                return $schemas;
                            })
                            ->columns(1),
                    ])
                    ->visible(fn (Get $get): bool => !empty($get('ordens_por_fornecedor'))),
                // Campo hidden para armazenar a estrutura das ordens
                Hidden::make('ordens_por_fornecedor')
                    ->reactive()
                    ->default([]),
            ])
            ->columns(1);
    }
    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados da Ordem de Pedido')
                    ->schema([
                        TextEntry::make('empresa.nome_fantasia')
                            ->label('Empresa'),
                        TextEntry::make('numero')
                            ->label('Número'),
                        // 🔹 TODOS OS FORNECEDORES
                        TextEntry::make('fornecedor.nome')
                            ->label('Fornecedor')
                            ->badge(),
                        TextEntry::make('created_at')
                            ->label('Criado em')
                            ->dateTime(format: 'd/m/Y H:i:s'),
                        TextEntry::make('items_count')
                            ->label('Qtd Itens')
                            ->counts('items'),
                        TextEntry::make('valor_total')
                            ->numeric()
                            ->money('BRL'),
                        TextEntry::make('status')
                            ->color(fn (string $state): string => match ($state) {
                                'pendente' => 'gray',
                                'aprovada' => 'warning',
                                'entregue' => 'success',
                                'cancelada' => 'danger',
                            }),
                        TextEntry::make('observacao')
                            ->label('Observação'),
                    ])
                    ->columns(2),
                     // =======================
            // 🔁 ITENS DA COTAÇÃO
            // =======================
            Section::make('Itens do pedido')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->schema([
                            TextEntry::make('descricao_produto')
                                ->label('Produto')
                                ->columnSpan(3),
                            TextEntry::make('descricao_marca')
                                ->label('Marca')
                                ->columnSpan(2),
                            TextEntry::make('quantidade')
                                ->label('Quant.')
                                ->columnSpan(1),
                            TextEntry::make('valor_unitario')
                                ->label('P. Unitário')
                                ->columnSpan(2)
                                ->money('BRL'),
                            TextEntry::make('valor_total_prod')
                                ->label('P Total')
                                ->columnSpan(2)
                                ->money('BRL'),
                            TextEntry::make('observacao')
                                ->label('Observação')
                                ->columnSpan(4),
                        ])
                        ->columns(10),
                ])
                ->columnSpanFull(),
                    
            ]);
                
    }
    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Ordem de Pedidos')
            ->columns([
               TextColumn::make('numero')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('fornecedor.nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('cotacao.numero')
                    ->searchable()
                    ->sortable()
                    ->label('Cotação'),
                TextColumn::make('data')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('items_count')
                    ->label('Qtd Itens')
                    ->counts('items')
                    ->sortable()
                    ->alignCenter(true),
                TextColumn::make('valor_total')
                    ->money('BRL')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->alignCenter()
                    ->color(fn (string $state): string => match ($state) {
                        'pendente' => 'gray',
                        'aprovada' => 'warning',
                        'entregue' => 'success',
                        'cancelada' => 'danger',
                    }),
                TextColumn::make('empresa.nome_fantasia')
                    ->label('Empresa')
                    ->visible(fn () => auth()->user()->is_master)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('usuario.name')
                    ->label('Criado por')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('aprovar')
                    ->label('')
                    ->tooltip('Aprovar Ordem de Pedido')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (OrdemPedido $record) {
                        $record->status = 'aprovada';
                        $record->save();
                    })
                    ->visible(fn (OrdemPedido $record) => $record->status === 'pendente'),
                 Action::make('entregue')
                    ->label('')
                    ->tooltip('Confirmar Entrega')
                    ->icon('heroicon-o-truck')
                    ->color('primary')
                    ->action(function (OrdemPedido $record) {
                        $record->status = 'entregue';
                        $record->save();
                    })
                    ->visible(fn (OrdemPedido $record) => $record->status === 'aprovada'),
                ViewAction::make()
                    ->label('')
                    ->tooltip('Ver Detalhes')
                    ->modalHeading('Visualizar Ordem de Pedido'),
                EditAction::make()
                    ->label('')
                    ->tooltip('Editar Ordem de Pedido')
                    ->modalHeading('Editar Ordem de Pedido'),
                DeleteAction::make()
                    ->label('')
                    ->tooltip('Excluir Ordem de Pedido')
                    ->modalHeading('Deseja Excluir essa ordem de pedido?')
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
    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        
        if ($user->is_master) {
            return parent::getEloquentQuery();
        }
        return parent::getEloquentQuery()->where('id_empresa', $user->id_empresa);
    }
    // Método para agrupar itens por fornecedor (mantido igual)
    private static function atualizarOrdensPorFornecedor(Set $set, Get $get)
    {
        $idCotacao = $get('id_cotacao');
        if (!$idCotacao) {
            $set('ordens_por_fornecedor', []);
            return;
        }
        try {
            $cotacao = Cotacao::with(['fornecedores', 'items.produto', 'items.marca'])->find($idCotacao);
            if (!$cotacao) {
                $set('ordens_por_fornecedor', []);
                return;
            }
            $ordensPorFornecedor = [];
            // Agrupar itens selecionados por fornecedor
            foreach ($cotacao->fornecedores as $fornecedor) {
                if ($fornecedor->pivot->status === 'respondida') {
                    $valores = $cotacao->parsearRespostaFornecedor($fornecedor->pivot->resposta_fornecedor);
                    if (!is_array($valores)) {
                        continue;
                    }
                    $itensFornecedor = [];
                    $totalFornecedor = 0;
                    $itemIndex = 0;
                    foreach ($cotacao->items as $cotacaoItem) {
                        if (isset($valores[$itemIndex])) {
                            $checkboxName = "item_{$fornecedor->id}_{$cotacaoItem->id}";
                            $isSelecionado = $get($checkboxName) ?? false;
                            if ($isSelecionado) {
                                $valorUnitario = $valores[$itemIndex];
                                $valorTotal = $cotacaoItem->quantidade * $valorUnitario;
                                $itensFornecedor[] = [
                                    'id_cotacao_item' => $cotacaoItem->id,
                                    'id_produto' => $cotacaoItem->id_produto,
                                    'descricao_produto' => $cotacaoItem->descricao_produto,
                                    'id_marca' => $cotacaoItem->id_marca,
                                    'quantidade' => $cotacaoItem->quantidade,
                                    'valor_unitario' => $valorUnitario,
                                    'valor_total_item' => $valorTotal,
                                    'observacao' => $cotacaoItem->observacao ?? '',
                                ];
                                $totalFornecedor += $valorTotal;
                            }
                        }
                        $itemIndex++;
                    }
                    if (!empty($itensFornecedor)) {
                        $ordensPorFornecedor[$fornecedor->id] = [
                            'fornecedor_nome' => $fornecedor->nome,
                            'fornecedor_id' => $fornecedor->id,
                            'itens' => $itensFornecedor,
                            'total' => $totalFornecedor,
                        ];
                    }
                }
            }
            $set('ordens_por_fornecedor', $ordensPorFornecedor);
        } catch (\Exception $e) {
            $set('ordens_por_fornecedor', []);
        }
    }
}
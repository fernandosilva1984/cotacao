<?php

namespace App\Filament\Admin\Resources\OrdemPedidos\Pages;

use App\Filament\Admin\Resources\OrdemPedidos\OrdemPedidoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use App\Models\OrdemPedido;
use App\Models\OrdemPedidoItem;
use App\Models\Cotacao;
use Illuminate\Support\Str;

class ManageOrdemPedidos extends ManageRecords
{
    protected static string $resource = OrdemPedidoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Adicionar Ordem de Pedido')
                ->icon('heroicon-o-plus')
                ->modalHeading('Adicionar Ordem de Pedido')
                ->modalSubmitActionLabel('Salvar')
                ->successNotificationTitle('Ordem de Pedido cadastrada com sucesso!')
                ->modalCancelActionLabel('Cancelar')
                ->action(function (array $data) {
                        self::createMultipleOrders($data);
                    }),
        ];
    }

    // Método para gerar números únicos em lote
    private static function gerarNumerosOrdemPedido(int $quantidade): array
    {
        $ano = date('Y');
        $ultimaOrdem = OrdemPedido::where('numero', 'like', "OP{$ano}%")
            ->orderBy('numero', 'desc')
            ->first();

        $ultimoNumero = $ultimaOrdem ? (int) Str::after($ultimaOrdem->numero, "OP{$ano}") : 0;
        
        $numeros = [];
        for ($i = 1; $i <= $quantidade; $i++) {
            $novoNumero = $ultimoNumero + $i;
            $numeros[] = "OP{$ano}" . str_pad($novoNumero, 5, '0', STR_PAD_LEFT);
        }
        
        return $numeros;
    }
    /*
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
                                    'descricao_produto' => $cotacaoItem->descricao_marca,
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
        */
    // CORREÇÃO: Método createMultipleOrders corrigido
    public static function createMultipleOrders(array $data): void
    {
        $cotacao = Cotacao::find($data['id_cotacao']);
        $observacaoGeral = $data['observacao_geral'] ?? '';
        $quantidadeOrdens = count($data['ordens_por_fornecedor']);
        
        // Gerar todos os números de uma vez
        $numerosOrdem = self::gerarNumerosOrdemPedido($quantidadeOrdens);
        $indiceNumero = 0;
        
        foreach ($data['ordens_por_fornecedor'] as $fornecedorId => $ordemData) {
            // Usar o próximo número disponível
            $numeroOrdem = $numerosOrdem[$indiceNumero];
            $indiceNumero++;
            
            // CORREÇÃO: Obter o observacao_fornecedor correto
            $observacaoFornecedor = $data["observacao_fornecedor_{$fornecedorId}"] ?? '';
            
            // Criar ordem de pedido para cada fornecedor
            $ordemPedido = OrdemPedido::create([
                'numero' => $numeroOrdem,
                'id_cotacao' => $data['id_cotacao'],
                'id_fornecedor' => $fornecedorId, // CORREÇÃO: Agora está passando o id_fornecedor
                'data' => $data['data'],
                'observacao' => trim($observacaoGeral . "\n\n" . $observacaoFornecedor),
                'valor_total' => $ordemData['total'],
                'status' => 'pendente',
                'id_empresa' => $data['id_empresa'],
                'id_usuario' => $data['id_usuario'],
            ]);
            
            // Criar itens da ordem de pedido
            foreach ($ordemData['itens'] as $itemData) {
                OrdemPedidoItem::create([
                    'id_ordem_pedido' => $ordemPedido->id,
                    'id_produto' => $itemData['id_produto'],
                    'descricao_produto' => $itemData['descricao_produto'],
                    'id_marca' => $itemData['id_marca'],
                    'quantidade' => $itemData['quantidade'],
                    'valor_unitario' => $itemData['valor_unitario'],
                    'valor_total' => $itemData['valor_total_item'],
                    'observacao' => $itemData['observacao'],
                ]);
            }
        }
    }
}
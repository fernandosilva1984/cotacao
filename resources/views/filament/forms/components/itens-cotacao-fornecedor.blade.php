<div x-data="{
    itensSelecionados: $wire.entangle('{{ $getStatePath() }}').live,
    itensPorFornecedor: @js($this->getItensAgrupadosPorFornecedor($cotacaoId)),
    
    toggleItem(itemId, fornecedorId, produto, marca, quantidade, valorUnitario, observacao) {
        const itemIndex = this.itensSelecionados.findIndex(item => 
            item.id_cotacao_item === itemId && item.id_fornecedor === fornecedorId
        );
        
        const valorTotalItem = quantidade * valorUnitario;
        
        if (itemIndex === -1) {
            // Adiciona item
            this.itensSelecionados.push({
                id_cotacao_item: itemId,
                id_fornecedor: fornecedorId,
                id_produto: produto,
                descricao_produto: this.getDescricaoProduto(produto),
                id_marca: marca,
                quantidade: quantidade,
                valor_unitario: valorUnitario,
                valor_total_item: valorTotalItem,
                observacao: observacao || ''
            });
        } else {
            // Remove item
            this.itensSelecionados.splice(itemIndex, 1);
        }
    },
    
    isItemSelecionado(itemId, fornecedorId) {
        return this.itensSelecionados.some(item => 
            item.id_cotacao_item === itemId && item.id_fornecedor === fornecedorId
        );
    },
    
    getDescricaoProduto(produtoId) {
        // Esta função precisaria ser implementada para buscar a descrição do produto
        // Por enquanto retorna uma string vazia
        return '';
    },
    
    getTotalFornecedor(fornecedorId) {
        return this.itensSelecionados
            .filter(item => item.id_fornecedor === fornecedorId)
            .reduce((total, item) => total + item.valor_total_item, 0);
    },
    
    getTotalGeral() {
        return this.itensSelecionados.reduce((total, item) => total + item.valor_total_item, 0);
    }
}">
    <template x-if="!itensPorFornecedor || Object.keys(itensPorFornecedor).length === 0">
        <div class="p-4 text-center text-gray-500">
            Nenhum item encontrado para esta cotação.
        </div>
    </template>

    <template x-for="(fornecedorId, fornecedorNome) in itensPorFornecedor" :key="fornecedorId">
        <div class="mb-6 border rounded-lg bg-gray-50">
            <!-- Header do Fornecedor -->
            <div class="px-4 py-3 bg-gray-200 border-b">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-700" x-text="fornecedorNome"></h3>
                    <div class="text-sm text-gray-600">
                        Total selecionado: R$ <span x-text="getTotalFornecedor(fornecedorId).toFixed(2)"></span>
                    </div>
                </div>
            </div>

            <!-- Itens do Fornecedor -->
            <div class="divide-y">
                <template x-for="item in itensPorFornecedor[fornecedorId]" :key="item.id">
                    <div class="px-4 py-3 hover:bg-gray-100">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4 flex-1">
                                <!-- Checkbox -->
                                <input type="checkbox" x-model="isItemSelecionado(item.id, fornecedorId)"
                                    @change="toggleItem(item.id, fornecedorId, item.id_produto, item.id_marca, item.quantidade, item.valor_unitario, item.observacao)"
                                    class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">

                                <!-- Informações do Produto -->
                                <div class="flex-1 grid grid-cols-4 gap-4">
                                    <div>
                                        <span class="block text-sm font-medium text-gray-700"
                                            x-text="item.descricao_produto"></span>
                                        <span class="block text-xs text-gray-500" x-text="item.nome_marca"></span>
                                    </div>

                                    <div class="text-center">
                                        <span class="block text-sm font-medium text-gray-700"
                                            x-text="item.quantidade"></span>
                                        <span class="block text-xs text-gray-500">Quantidade</span>
                                    </div>

                                    <div class="text-center">
                                        <span class="block text-sm font-medium text-gray-700"
                                            x-text="'R$ ' + item.valor_unitario.toFixed(2)"></span>
                                        <span class="block text-xs text-gray-500">Valor Unitário</span>
                                    </div>

                                    <div class="text-center">
                                        <span class="block text-sm font-medium text-green-600"
                                            x-text="'R$ ' + (item.quantidade * item.valor_unitario).toFixed(2)"></span>
                                        <span class="block text-xs text-gray-500">Valor Total</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Observações -->
                        <template x-if="item.observacao">
                            <div class="mt-2 pl-8">
                                <span class="text-xs text-gray-500" x-text="'Obs: ' + item.observacao"></span>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </template>

    <!-- Total Geral -->
    <div x-show="itensSelecionados.length > 0" class="mt-4 p-4 bg-primary-50 border border-primary-200 rounded-lg">
        <div class="flex justify-between items-center">
            <span class="font-semibold text-primary-700">Total Geral do Pedido:</span>
            <span class="text-lg font-bold text-primary-700" x-text="'R$ ' + getTotalGeral().toFixed(2)"></span>
        </div>
        <div class="text-sm text-primary-600 mt-1" x-text="itensSelecionados.length + ' item(s) selecionado(s)'"></div>
    </div>
</div>
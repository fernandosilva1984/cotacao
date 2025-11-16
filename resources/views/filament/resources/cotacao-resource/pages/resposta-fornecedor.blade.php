{{-- resources/views/filament/resources/cotacao-resource/pages/respostas-fornecedores.blade.php --}}
<div class="p-6">
    <h2 class="text-2xl font-bold mb-4">Respostas dos Fornecedores - Cotação {{ $cotacao->numero }}</h2>

    @foreach($cotacao->fornecedores as $fornecedor)
    @if($fornecedor->pivot->status === 'respondida')
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold mb-3 text-blue-600">
            {{ $fornecedor->nome }}
            <span class="text-sm font-normal text-green-600 ml-2">(Respondido)</span>
        </h3>

        <div class="mb-4">
            <h4 class="font-medium mb-2">Resposta do Fornecedor:</h4>
            <div class="bg-gray-50 p-4 rounded border">
                <pre class="whitespace-pre-wrap">{{ $fornecedor->pivot->resposta_fornecedor }}</pre>
            </div>
        </div>

        <h4 class="font-medium mb-3">Itens com Valores Propostos:</h4>
        <table class="w-full border-collapse border border-gray-300 mb-4">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border border-gray-300 px-4 py-2">Item</th>
                    <th class="border border-gray-300 px-4 py-2">Descrição</th>
                    <th class="border border-gray-300 px-4 py-2">Quantidade</th>
                    <th class="border border-gray-300 px-4 py-2">Valor Proposto</th>
                    <th class="border border-gray-300 px-4 py-2">Total Proposto</th>
                </tr>
            </thead>
            <tbody>
                @php
                $valores = $cotacao->parsearRespostaFornecedor($fornecedor->pivot->resposta_fornecedor);
                @endphp
                @foreach($cotacao->items as $index => $item)
                <tr>
                    <td class="border border-gray-300 px-4 py-2 text-center">{{ $index + 1 }}</td>
                    <td class="border border-gray-300 px-4 py-2">{{ $item->descricao_produto }}</td>
                    <td class="border border-gray-300 px-4 py-2 text-center">
                        {{ number_format($item->quantidade, 2, ',', '.') }}
                    </td>
                    <td class="border border-gray-300 px-4 py-2 text-right">
                        @if(isset($valores[$index]))
                        R$ {{ number_format($valores[$index], 2, ',', '.') }}
                        @else
                        <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="border border-gray-300 px-4 py-2 text-right">
                        @if(isset($valores[$index]))
                        R$ {{ number_format($item->quantidade * $valores[$index], 2, ',', '.') }}
                        @else
                        <span class="text-gray-400">-</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold mb-3 text-gray-600">
            {{ $fornecedor->nome }}
            <span class="text-sm font-normal text-orange-600 ml-2">
                ({{ $fornecedor->pivot->status === 'enviada' ? 'Aguardando resposta' : 'Não enviada' }})
            </span>
        </h3>
        <p class="text-gray-500">Aguardando resposta do fornecedor.</p>
    </div>
    @endif
    @endforeach
</div>
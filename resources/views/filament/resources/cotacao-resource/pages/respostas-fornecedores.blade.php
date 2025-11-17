{{-- resources/views/filament/resources/cotacao-resource/pages/respostas-fornecedores.blade.php --}}
<div class="p-6">
    <h2 class="text-2xl font-bold mb-4">Respostas dos Fornecedores - Cotação {{ $cotacao->numero }}</h2>

    @php
    // Calcular os menores preços e identificar os fornecedores para cada item
    $menoresPrecos = [];
    $fornecedoresMenorPreco = [];

    foreach($cotacao->fornecedores as $fornecedor) {
    if($fornecedor->pivot->status === 'respondida') {
    $valores = $cotacao->parsearRespostaFornecedor($fornecedor->pivot->resposta_fornecedor);
    foreach($cotacao->items as $index => $item) {
    if(isset($valores[$index])) {
    if(!isset($menoresPrecos[$index]) || $valores[$index] < $menoresPrecos[$index]) {
        $menoresPrecos[$index]=$valores[$index]; $fornecedoresMenorPreco[$index]=$fornecedor->nome;
        }
        }
        }
        }
        }
        @endphp

        @foreach($cotacao->fornecedores as $fornecedor)
        @if($fornecedor->pivot->status === 'respondida')
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-semibold mb-3 text-blue-600">
                {{ $fornecedor->nome }}
                <span class="text-sm font-normal text-green-600 ml-2">(Respondido)</span>
            </h3>

            <div class="mb-4">
                <h4 class="font-medium mb-2">Resposta do Fornecedor:</h4>
                <!--  <div class="bg-gray-50 p-4 rounded border">
                <pre class="whitespace-pre-wrap">{{ $fornecedor->pivot->resposta_fornecedor }}</pre>
            </div> -->
            </div>
            <br>
            <h4 class="font-medium mb-3 text-center">Itens com Valores Propostos:</h4>
            <br>
            <table class="w-full border-collapse border border-gray-300 mb-4 text-sm">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border border-gray-300 px-3 py-2 w-16 text-center"
                            style="width: 50px; border: 1px solid #000000;">Item</th>
                        <th class="border border-gray-300 px-3 py-2 w-2/5"
                            style="width: 250px; border: 1px solid #000000;">Descrição</th>
                        <th class="border border-gray-300 px-3 py-2 w-24 text-center"
                            style="width: 90px; border: 1px solid #000000;">Quantidade
                        </th>
                        <th class="border border-gray-300 px-3 py-2 w-32 text-center"
                            style="width: 110px; border: 1px solid #000000;">Valor
                            Proposto</th>
                        <th class="border border-gray-300 px-3 py-2 w-32 text-center"
                            style="width: 110px; border: 1px solid #000000;">Total
                            Proposto</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $valores = $cotacao->parsearRespostaFornecedor($fornecedor->pivot->resposta_fornecedor);
                    @endphp
                    @foreach($cotacao->items as $index => $item)
                    @php
                    $isMenorPreco = isset($menoresPrecos[$index]) &&
                    isset($valores[$index]) &&
                    $valores[$index] == $menoresPrecos[$index] &&
                    $fornecedoresMenorPreco[$index] == $fornecedor->nome;
                    @endphp
                    <tr>
                        <td class="border border-gray-300 px-4 py-2 text-center"
                            style="width: 50px; border: 1px solid #000000; text-align: center;">{{ $index + 1 }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2 w-2/5"
                            style="width: 250px; border: 1px solid #000000;">
                            {{ $item->descricao_produto }}</td>
                        <td class="border border-gray-300 px-4 py-2 text-center"
                            style="width: 90px; border: 1px solid #000000; text-align: center;">
                            {{ number_format($item->quantidade, 2, ',', '.') }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-right" @if($isMenorPreco)
                            style="background-color: #dcfce7; color: #166534; font-weight: bold; border: 2px solid #22c55e; text-align: end;"
                            @endif>
                            @if(isset($valores[$index]))
                            R$ {{ number_format($valores[$index], 2, ',', '.') }}
                            @if($isMenorPreco)
                            <br><span class="text-xs text-green-600">(Melhor preço)</span>
                            @endif
                            @else
                            <span class="text-gray-400" style="width: 100px; border: 1px solid #000000;">-</span>
                            @endif
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-right {{ $isMenorPreco ? 'bg-green-100 text-green-800 font-bold' : '' }}"
                            style="width: 100px; border: 1px solid #000000; text-align: end;">
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

        {{-- Mapa de Preços - Menores Preços --}}
        <br>
        <div class="bg-white rounded-lg shadow p-6 mt-8">
            <h3 class="text-xl font-bold mb-4 text-green-700">📊 Mapa de Preços - Menores Valores por Item</h3>
            <br>
            <table class="w-full border-collapse border border-gray-800">
                <thead>
                    <tr class="bg-green-50">
                        <th class="border border-gray-300 px-4 py-2" style="width: 50px; border: 1px solid #000000;">
                            Item</th>
                        <th class="border border-gray-300 px-4 py-2" style="width: 250px; border: 1px solid #000000;">
                            Descrição</th>
                        <th class="border border-gray-300 px-4 py-2" style="width: 100px; border: 1px solid #000000;">
                            Fornecedor</th>
                        <th class="border border-gray-300 px-4 py-2" style="width: 60px; border: 1px solid #000000;">
                            Quantidade</th>
                        <th class="border border-gray-300 px-4 py-2" style="width: 130px; border: 1px solid #000000;">
                            Menor Valor Unitário</th>
                        <th class="border border-gray-300 px-4 py-2" style="width: 130px; border: 1px solid #000000;">
                            Total com Menor Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cotacao->items as $index => $item)
                    <tr>
                        <td class="border border-gray-300 px-4 py-2 text-center"
                            style="width: 50px; border: 1px solid #000000; text-align: center;">{{ $index + 1 }}</td>
                        <td class="border border-gray-300 px-4 py-2" style="width: 250px; border: 1px solid #000000;">
                            {{ $item->descricao_produto }}</td>
                        <td class="border border-gray-300 px-4 py-2 font-semibold"
                            style="width: 100px; border: 1px solid #000000;">
                            @if(isset($fornecedoresMenorPreco[$index]))
                            {{ $fornecedoresMenorPreco[$index] }}
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-center"
                            style="width: 50px; border: 1px solid #000000; text-align: center; ">
                            {{ number_format($item->quantidade, 2, ',', '.') }}
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-right bg-green-100 font-bold"
                            style="width: 130px; border: 1px solid #000000; text-align: end;">
                            @if(isset($menoresPrecos[$index]))
                            R$ {{ number_format($menoresPrecos[$index], 2, ',', '.') }}
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="border border-gray-300 px-4 py-2 text-right bg-green-100 font-bold"
                            style="width: 130px; border: 1px solid #000000; text-align: end;">
                            @if(isset($menoresPrecos[$index]))
                            R$ {{ number_format($item->quantidade * $menoresPrecos[$index], 2, ',', '.') }}
                            @else
                            <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
</div>
{{-- resources/views/emails/cotacao.blade.php --}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Cotacao #{{ $cotacao->numero }}</title>
    <style>
    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
        color: #333;
    }

    .container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .header {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
    }

    .table th,
    .table td {
        border: 1px solid #ddd;
        padding: 12px;
        text-align: left;
    }

    .table th {
        background-color: #f8f9fa;
    }

    .total {
        font-weight: bold;
        font-size: 1.1em;
    }

    .footer {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
        font-size: 0.9em;
        color: #666;
    }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>Cotacao #{{ $cotacao->numero }}</h1>
            <p><strong>Empresa:</strong> {{ $empresa->nome_fantasia }}</p>
            <p><strong>Data:</strong> {{ $cotacao->data->format('d/m/Y') }}</p>
            <p><strong>Fornecedor:</strong> {{ $fornecedor->nome }}</p>
        </div>

        @if($cotacao->observacao)
        <div class="observacao">
            <h3>Observações:</h3>
            <p>{{ $cotacao->observacao }}</p>
        </div>
        @endif

        <table class="table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Descrição</th>
                    <th>Marca</th>
                    <th>Quantidade</th>
                    <!-- <th>Valor Unitário</th>
                    <th>Valor Total</th>-->
                </tr>
            </thead>
            <tbody>
                @foreach($itens as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->descricao_produto }}</td>
                    <td>{{ $item->descricao_marca }}</td>
                    <td>{{ number_format($item->quantidade, 2, ',', '.') }} {{ $item->produto->unidade_medida ?? 'UN' }}
                    </td>
                    <!-- <td>R$ {{ number_format($item->valor_unitario, 2, ',', '.') }}</td>
                    <td>R$ {{ number_format($item->valor_total_prod, 2, ',', '.') }}</td> -->
                </tr>
                @endforeach
            </tbody>
            <!--  <tfoot>
                <tr class="total">
                    <td colspan="5" style="text-align: right;">Total:</td>
                    <td>R$ {{ number_format($cotacao->valor_total, 2, ',', '.') }}</td>
                </tr>
            </tfoot>-->
        </table>

        <div class="instrucoes">
            <h3>Instruções para Resposta:</h3>
            <p>Por favor, responda este email informando os valores unitários para cada item listado acima.</p>
            <p>Utilize o seguinte formato para sua resposta:</p>
            <pre>
Item 1: R$ [valor]
Item 2: R$ [valor]
...
Observações: [suas observações]
            </pre>
        </div>

        <div class="footer">
            <p>Atenciosamente,<br>
                {{ $empresa->nome_fantasia }}<br>
                {{ $empresa->email }}<br>
                {{ $empresa->contato }}</p>
        </div>
    </div>
</body>

</html>
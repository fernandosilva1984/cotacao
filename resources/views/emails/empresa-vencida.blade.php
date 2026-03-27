{{-- resources/views/emails/empresa-vencida.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assinatura Expirada</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 10px 0 0;
            opacity: 0.9;
        }
        .content {
            padding: 30px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .info-box h3 {
            margin: 0 0 10px;
            color: #dc3545;
        }
        .detalhes {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #dc3545;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
            font-weight: bold;
        }
        .button:hover {
            background: #c82333;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #dee2e6;
        }
        .contact-info {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Assinatura Expirada</h1>
            <p>Sua conta foi desativada</p>
        </div>
        
        <div class="content">
            <h2>Olá, {{ $empresa->nome_fantasia }}!</h2>
            
            <div class="info-box">
                <h3>Sua assinatura expirou!</h3>
                <p><strong>Data de vencimento:</strong> {{ $empresa->data_validade->format('d/m/Y') }}</p>
                <p>Por isso, sua conta foi temporariamente desativada.</p>
            </div>
            
            <div class="detalhes">
                <p><strong>Plano contratado:</strong> {{ ucfirst($empresa->plano) }}</p>
                <p><strong>Valor do plano:</strong> R$ {{ number_format($empresa->valor_plano, 2, ',', '.') }}</p>
            </div>
            
            <p>Para continuar utilizando o sistema, você precisa renovar sua assinatura.</p>
            
            <div style="text-align: center;">
                <a href="{{ url('https://cotacao.meusinvestimentos.online') }}" class="button">
                    Renovar Assinatura
                </a>
            </div>
            
            <div class="contact-info">
                <p>Em caso de dúvidas, entre em contato com nosso suporte:</p>
                <p>📧 {{ config('mail.from.address') }}<br>
                📞 (87) 99934-6266</p>
            </div>
        </div>
        
        <div class="footer">
            <p>Este é um email automático. Por favor, não responda.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }} - Todos os direitos reservados</p>
        </div>
    </div>
</body>
</html>
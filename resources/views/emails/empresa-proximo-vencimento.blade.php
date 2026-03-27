{{-- resources/views/emails/empresa-proximo-vencimento.blade.php --}}
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renove sua Assinatura</title>
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
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            color: #856404;
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
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .info-box h3 {
            margin: 0 0 10px;
            color: #856404;
        }
        .dias-restantes {
            font-size: 36px;
            font-weight: bold;
            color: #ffc107;
            text-align: center;
            margin: 20px 0;
        }
        .planos {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .plano-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }
        .plano-item:last-child {
            border-bottom: none;
        }
        .plano-nome {
            font-weight: bold;
        }
        .plano-valor {
            color: #28a745;
            font-weight: bold;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #ffc107;
            color: #856404;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
            font-weight: bold;
        }
        .button:hover {
            background: #e0a800;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #dee2e6;
        }
        .alert {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📅 Atenção!</h1>
            <p>Sua assinatura está próxima do vencimento</p>
        </div>
        
        <div class="content">
            <h2>Olá, {{ $empresa->nome_fantasia }}!</h2>
            
            <div class="info-box">
                <h3>Sua assinatura vencerá em breve!</h3>
                <div class="dias-restantes">{{ $diasRestantes }} dias</div>
                <p><strong>Data de vencimento:</strong> {{ $empresa->data_validade->format('d/m/Y') }}</p>
                <p>Renove agora para não perder o acesso ao sistema.</p>
            </div>
            
            <div class="alert">
                ⚡ <strong>Importante:</strong> Após o vencimento, sua conta será desativada automaticamente.
            </div>
            
            <div class="planos">
                <h3 style="margin-top: 0;">Nossos Planos</h3>
                <div class="plano-item">
                    <span class="plano-nome">Mensal </span>
                    <span class="plano-valor"> R$ 49,90 / mês</span>
                </div>
                <div class="plano-item">
                    <span class="plano-nome">Trimestral </span>
                    <span class="plano-valor"> R$ 129,90 (R$ 43,30/mês)</span>
                </div>
                <div class="plano-item">
                    <span class="plano-nome">Semestral </span>
                    <span class="plano-valor"> R$ 239,90 (R$ 39,98/mês)</span>
                </div>
                <div class="plano-item">
                    <span class="plano-nome">Anual </span>
                    <span class="plano-valor"> R$ 360,00 (R$ 30,00/mês)</span>
                </div>
            </div>
            
            <div style="text-align: center;">
                <a href="{{ url('https://cotacao.meusinvestimentos.online') }}" class="button">
                    Renovar Agora
                </a>
            </div>
            
            <div class="contact-info" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #dee2e6;">
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
<?php
// app/Services/EmailService.php

namespace App\Services;

use App\Models\Cotacao;
use App\Models\Empresa;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Storage;
use PhpImap\Mailbox;
use PhpImap\IncomingMail;
use PhpImap\IncomingMailAttachment;

class EmailService
{
    public function enviarCotacaoParaFornecedor(Cotacao $cotacao, $fornecedorId): array
    {
        try {
            $fornecedor = $cotacao->fornecedores()->where('fornecedores.id', $fornecedorId)->first();
            
            if (!$fornecedor) {
                return ['success' => false, 'message' => 'Fornecedor não encontrado'];
            }

            if (!$fornecedor->email) {
                return ['success' => false, 'message' => 'Fornecedor não possui email cadastrado'];
            }

            $empresa = $cotacao->empresa;
            
            Log::info("Tentando enviar email para: {$fornecedor->email}");
            Log::info("Empresa: {$empresa->nome_fantasia}");
            Log::info("Cotação: {$cotacao->numero}");

            // Usar a configuração padrão do .env
            $result = Mail::send('emails.cotacao', [
                'cotacao' => $cotacao,
                'empresa' => $empresa,
                'fornecedor' => $fornecedor,
                'itens' => $cotacao->items,
            ], function ($message) use ($cotacao, $fornecedor, $empresa) {
                $message->to($fornecedor->email)
                        ->subject("Cotação #{$cotacao->numero} - {$empresa->nome_fantasia}")
                        ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME', 'Sistema de Cotações'));
            });

            // Verificar se o email foi enviado com sucesso
            if ($result) {
                $cotacao->marcarComoEnviadaParaFornecedor($fornecedorId);
                Log::info("Email enviado com sucesso para: {$fornecedor->email}");
                return ['success' => true, 'message' => 'Email enviado com sucesso'];
            } else {
                Log::error("Falha no envio para: {$fornecedor->email}");
                return ['success' => false, 'message' => 'Falha no envio do email'];
            }

        } catch (\Exception $e) {
            Log::error('Erro ao enviar cotação para fornecedor: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    public function testarConfiguracaoEmail($emailDestino): array
    {
        try {
            $result = Mail::raw('Este é um email de teste do Sistema de Cotações.', function ($message) use ($emailDestino) {
                $message->to($emailDestino)
                        ->subject('Teste de Configuração - Sistema Cotações')
                        ->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME', 'Sistema de Cotações'));
            });

            if ($result) {
                return ['success' => true, 'message' => 'Email de teste enviado com sucesso'];
            } else {
                return ['success' => false, 'message' => 'Falha no envio do email de teste'];
            }

        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erro: ' . $e->getMessage()];
        }
    }

    public function enviarCotacaoParaTodosFornecedores(Cotacao $cotacao): array
    {
        $resultados = [];
        
        foreach ($cotacao->fornecedores as $fornecedor) {
            $resultado = $this->enviarCotacaoParaFornecedor($cotacao, $fornecedor->id);
            $resultados[$fornecedor->id] = $resultado;
        }

        // Verificar se pelo menos um email foi enviado
        $enviadosComSucesso = count(array_filter($resultados, fn($r) => $r['success']));
        
        if ($enviadosComSucesso > 0) {
            $cotacao->update(['status' => 'enviada']);
        }

        return $resultados;
    }
    /**
     * Processar emails de resposta automaticamente
     */
    public function processarRespostasFornecedores(): array
    {
        $resultados = [];
        
        try {
            if (!class_exists('PhpImap\Mailbox')) {
                throw new \Exception('Biblioteca PHP IMAP não instalada');
            }

            Log::info('=== INICIANDO PROCESSAMENTO DE RESPOSTAS ===');

            // Configuração do email do sistema (do .env)
            $host = env('IMAP_HOST');
            $port = env('IMAP_PORT', 993);
            $username = env('MAIL_USERNAME');
            $password = env('MAIL_PASSWORD');

            if (!$host || !$username || !$password) {
                throw new \Exception('Configuração de email incompleta no .env');
            }

            $mailbox = $this->conectarMailboxSistema($host, $port, $username, $password);
            $mailsIds = $mailbox->searchMailbox('UNSEEN');

            Log::info("Emails não lidos encontrados: " . count($mailsIds));

            foreach ($mailsIds as $mailId) {
                try {
                    $mail = $mailbox->getMail($mailId);
                    $resultado = $this->processarEmailResposta($mail);
                    $resultados[] = $resultado;

                    // Marcar como lido
                    $mailbox->markMailAsRead($mailId);

                    Log::info("Email {$mailId} processado: " . ($resultado['success'] ? 'SUCESSO' : 'FALHA'));

                } catch (\Exception $e) {
                    Log::error("Erro ao processar email {$mailId}: " . $e->getMessage());
                    $resultados[] = ['success' => false, 'message' => "Email {$mailId}: " . $e->getMessage()];
                }
            }

            $mailbox->disconnect();
            Log::info('=== PROCESSAMENTO DE RESPOSTAS CONCLUÍDO ===');

        } catch (\Exception $e) {
            Log::error('Erro no processamento de respostas: ' . $e->getMessage());
            $resultados[] = ['success' => false, 'message' => $e->getMessage()];
        }

        return $resultados;
    }

    /**
     * Conectar ao mailbox do sistema
     */
    private function conectarMailboxSistema($host, $port, $username, $password): Mailbox
{
    // Para Titan Email, usar esta string específica
    $mailboxString = "{{$host}:{$port}/imap/ssl/novalidate-cert}INBOX";
    
    Log::info("Conectando ao Titan Email: {$mailboxString}");
    
    // Criar diretório para anexos
    $attachmentPath = storage_path('app/email_attachments');
    $this->criarDiretorioAnexos($attachmentPath);

    return new Mailbox(
        $mailboxString,
        $username,
        $password,
        $attachmentPath,
        'UTF-8'
    );
}

    /**
     * Processar um email de resposta individual
     */
    private function processarEmailResposta(IncomingMail $mail): array
    {
        try {
            Log::info("Processando email: {$mail->subject}");
            Log::info("De: {$mail->fromAddress}");
            Log::info("Data: {$mail->date}");

            // Extrair número da cotação do assunto
            $numeroCotacao = $this->extrairNumeroCotacao($mail->subject);
            
            if (!$numeroCotacao) {
                return ['success' => false, 'message' => 'Número da cotação não encontrado no assunto'];
            }

            // Buscar cotação
            $cotacao = Cotacao::where('numero', $numeroCotacao)->first();
            
            if (!$cotacao) {
                return ['success' => false, 'message' => "Cotação {$numeroCotacao} não encontrada"];
            }

            // Identificar fornecedor pelo email
            $fornecedor = $this->identificarFornecedorPorEmail($mail->fromAddress, $cotacao);
            
            if (!$fornecedor) {
                return ['success' => false, 'message' => "Fornecedor não identificado para o email: {$mail->fromAddress}"];
            }

            // Processar resposta
            $textoResposta = $mail->textPlain ?: strip_tags($mail->textHtml);
            $dadosResposta = $this->parsearRespostaFornecedor($textoResposta, $cotacao);

            // Atualizar cotação
            $cotacao->processarRespostaFornecedor($fornecedor->id, $dadosResposta['texto_resposta']);

            // Atualizar valores dos itens se encontrados
            if (!empty($dadosResposta['valores'])) {
                $this->atualizarValoresItens($cotacao, $fornecedor->id, $dadosResposta['valores']);
            }

            Log::info("✅ Resposta processada - Cotação: {$numeroCotacao}, Fornecedor: {$fornecedor->nome}");

            return [
                'success' => true,
                'message' => "Resposta processada - {$fornecedor->nome}",
                'cotacao' => $numeroCotacao,
                'fornecedor' => $fornecedor->nome,
                'valores_encontrados' => count($dadosResposta['valores'] ?? [])
            ];

        } catch (\Exception $e) {
            Log::error("Erro ao processar resposta: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Identificar fornecedor pelo email do remetente
     */
    private function identificarFornecedorPorEmail(string $email, Cotacao $cotacao)
    {
        // Buscar fornecedor pelo email exato
        $fornecedor = $cotacao->fornecedores()
            ->where('email', $email)
            ->first();

        if ($fornecedor) {
            return $fornecedor;
        }

        // Se não encontrou, tentar buscar por similaridade
        $fornecedores = $cotacao->fornecedores;
        foreach ($fornecedores as $f) {
            if ($f->email && strpos($email, $f->email) !== false) {
                return $f;
            }
        }

        return null;
    }

    /**
     * Extrair número da cotação do assunto
     */
    private function extrairNumeroCotacao(string $assunto): ?string
    {
        // Padrões comuns
        $padroes = [
            '/COT\d+/',                         // COT2024000001
            '/cotação\s*#?\s*(\w+)/i',         // cotação #COT2024000001
            '/resposta\s+.*?(\w+)/i',          // resposta cotação COT2024000001
        ];

        foreach ($padroes as $padrao) {
            if (preg_match($padrao, $assunto, $matches)) {
                return $matches[0];
            }
        }

        return null;
    }

    /**
     * Parsear resposta do fornecedor
     */
    private function parsearRespostaFornecedor(string $texto, Cotacao $cotacao): array
    {
        $valores = [];
        $linhas = explode("\n", $texto);
        
        foreach ($linhas as $linha) {
            $linha = trim($linha);
            
            // Padrões para valores
            $padroes = [
                '/item\s*(\d+).*?R\$\s*([\d\.,]+)/i',
                '/item\s*(\d+).*?([\d\.,]+)\s*reais/i',
                '/item\s*(\d+).*?valor.*?([\d\.,]+)/i',
                '/(\d+).*?R\$\s*([\d\.,]+)/',
            ];

            foreach ($padroes as $padrao) {
                if (preg_match($padrao, $linha, $matches)) {
                    $itemIndex = intval($matches[1]) - 1;
                    $valor = floatval(str_replace(['.', ','], ['', '.'], $matches[2]));
                    
                    if ($itemIndex >= 0 && $itemIndex < $cotacao->items->count()) {
                        $valores[$itemIndex] = $valor;
                        break;
                    }
                }
            }
        }

        return [
            'texto_resposta' => $texto,
            'valores' => $valores,
        ];
    }

    /**
     * Atualizar valores dos itens na cotação
     */
    private function atualizarValoresItens(Cotacao $cotacao, $fornecedorId, array $valores): void
    {
        foreach ($valores as $itemIndex => $valorUnitario) {
            if (isset($cotacao->items[$itemIndex])) {
                $item = $cotacao->items[$itemIndex];
                $item->update([
                    'valor_unitario_resposta' => $valorUnitario,
                    'valor_total_resposta' => $item->quantidade * $valorUnitario,
                ]);
            }
        }
    }
    /**
     * Criar diretório para anexos com verificação
     */
    private function criarDiretorioAnexos(string $path): void
    {
        try {
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
                Log::info("Diretório criado: {$path}");
            }
            
            // Verificar se o diretório é gravável
            if (!File::isWritable($path)) {
                throw new \Exception("Diretório não é gravável: {$path}");
            }
            
            Log::info("Diretório de anexos pronto: {$path}");
            
        } catch (\Exception $e) {
            Log::error("Erro ao criar diretório de anexos: " . $e->getMessage());
            throw new \Exception("Não foi possível criar o diretório de anexos: " . $e->getMessage());
        }
    }
}
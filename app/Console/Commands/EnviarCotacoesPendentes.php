<?php
// app/Console/Commands/EnviarCotacoesPendentes.php

namespace App\Console\Commands;

use App\Models\Cotacao;
use App\Services\EmailService;
use Illuminate\Console\Command;

class EnviarCotacoesPendentes extends Command
{
    protected $signature = 'cotacao:enviar-pendentes';
    protected $description = 'Envia cotações pendentes por email';

    public function handle(EmailService $emailService)
    {
        $this->info('Buscando cotações pendentes...');
        
        $cotacoes = Cotacao::where('status', 'pendente')
            ->whereHas('fornecedor', function ($query) {
                $query->whereNotNull('email')->where('email', '!=', '');
            })
            ->get();

        $this->info("Encontradas {$cotacoes->count()} cotações pendentes.");

        $enviadas = 0;
        $erros = 0;

        foreach ($cotacoes as $cotacao) {
            try {
                if ($emailService->enviarCotacao($cotacao)) {
                    $enviadas++;
                    $this->info("Cotação {$cotacao->numero} enviada para {$cotacao->fornecedor->email}");
                } else {
                    $erros++;
                    $this->error("Falha ao enviar cotação {$cotacao->numero}");
                }
            } catch (\Exception $e) {
                $erros++;
                $this->error("Erro na cotação {$cotacao->numero}: " . $e->getMessage());
            }
        }

        $this->info("Processamento concluído: {$enviadas} enviadas, {$erros} erros.");
        
        return 0;
    }
}
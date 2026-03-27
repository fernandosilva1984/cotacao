<?php
// app/Console/Commands/ProcessarEmailsResposta.php

namespace App\Console\Commands;

use App\Services\EmailService;
use Illuminate\Console\Command;

class ProcessarEmailsResposta extends Command
{
    protected $signature = 'cotacao:processar-emails';
    protected $description = 'Processa emails de resposta das cotações';

    public function handle(EmailService $emailService)
    {
        $this->info('Iniciando processamento de emails...');
        
        try {
            $emailService->processarEmailsResposta();
            $this->info('Processamento de emails concluído com sucesso.');
        } catch (\Exception $e) {
            $this->error('Erro ao processar emails: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
<?php
// app/Console/Commands/ProcessarRespostasCotacoes.php

namespace App\Console\Commands;

use App\Services\EmailService;
use Illuminate\Console\Command;

class ProcessarRespostasCotacoes extends Command
{
    protected $signature = 'cotacao:processar-respostas';
    protected $description = 'Processar respostas de cotações dos fornecedores';

    public function handle(EmailService $emailService)
    {
        $this->info('🔄 Iniciando processamento de respostas...');
        
        try {
            $resultados = $emailService->processarRespostasFornecedores();
            
            $this->info('✅ Processamento concluído!');
            $this->line('');
            
            $sucessos = 0;
            $falhas = 0;
            
            foreach ($resultados as $resultado) {
                if ($resultado['success']) {
                    $this->info("✅ {$resultado['message']}");
                    $sucessos++;
                } else {
                    $this->error("❌ {$resultado['message']}");
                    $falhas++;
                }
            }
            
            $this->line('');
            $this->info("📊 Resumo: {$sucessos} sucesso(s), {$falhas} falha(s)");
            
        } catch (\Exception $e) {
            $this->error('❌ Erro no processamento: ' . $e->getMessage());
        }
    }
}
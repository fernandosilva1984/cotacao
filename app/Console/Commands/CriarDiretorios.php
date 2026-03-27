<?php
// app/Console/Commands/CriarDiretorios.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CriarDiretorios extends Command
{
    protected $signature = 'app:criar-dirs';
    protected $description = 'Criar diretórios necessários para o sistema';

    public function handle()
    {
        $this->info('Criando diretórios necessários...');

        $diretorios = [
            storage_path('app/email_attachments'),
            storage_path('app/public/cotacoes'),
            storage_path('app/templates'),
            storage_path('logs/email'),
        ];

        foreach ($diretorios as $dir) {
            if (!File::exists($dir)) {
                if (File::makeDirectory($dir, 0755, true)) {
                    $this->info("✅ Criado: {$dir}");
                } else {
                    $this->error("❌ Falha ao criar: {$dir}");
                }
            } else {
                $this->info("✅ Já existe: {$dir}");
            }

            // Verificar permissões
            if (File::isWritable($dir)) {
                $this->info("   Permissões: Gravável");
            } else {
                $this->error("   Permissões: Não gravável");
            }
        }

        $this->info('');
        $this->info('Diretórios verificados com sucesso!');
    }
}
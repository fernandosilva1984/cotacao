<?php
// app/Console/Commands/VerificarEmpresasVencidas.php

namespace App\Console\Commands;

use App\Models\Empresa;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use App\Mail\EmpresaVencidaMail;
use App\Mail\EmpresaProximoVencimentoMail;

class VerificarEmpresasVencidas extends Command
{
    protected $signature = 'empresas:verificar-vencidas';
    protected $description = 'Verifica empresas com assinatura vencida ou próximo do vencimento';

    public function handle()
    {
        // Empresas que venceram
        $vencidas = Empresa::where('status', true)
            ->where('data_validade', '<', Carbon::now())
            ->get();
        
        foreach ($vencidas as $empresa) {
            $empresa->status = false;
            $empresa->save();
            
            $this->info("Empresa {$empresa->nome_fantasia} desativada (assinatura vencida)");
            
            // Envia email de aviso
            if ($empresa->email) {
                try {
                    Mail::to($empresa->email)->send(new EmpresaVencidaMail($empresa));
                    $this->info("Email enviado para {$empresa->email}");
                } catch (\Exception $e) {
                    $this->error("Erro ao enviar email para {$empresa->email}: {$e->getMessage()}");
                }
            }
        }
        
        // Empresas que vencem em até 30 dias
        $proximasVencer = Empresa::where('status', true)
            ->where('data_validade', '>', Carbon::now())
            ->where('data_validade', '<=', Carbon::now()->addDays(30))
            ->get();
        
        foreach ($proximasVencer as $empresa) {
            $diasRestantes = Carbon::now()->diffInDays($empresa->data_validade);
            $this->info("Empresa {$empresa->nome_fantasia} vence em {$diasRestantes} dias");
            
            // Envia email de lembrete
            if ($empresa->email) {
                try {
                    Mail::to($empresa->email)->send(new EmpresaProximoVencimentoMail($empresa));
                    $this->info("Email de lembrete enviado para {$empresa->email}");
                } catch (\Exception $e) {
                    $this->error("Erro ao enviar email para {$empresa->email}: {$e->getMessage()}");
                }
            }
        }
        
        $this->info("Verificação concluída: {$vencidas->count()} empresas desativadas, {$proximasVencer->count()} próximas ao vencimento");
        
        return 0;
    }
}
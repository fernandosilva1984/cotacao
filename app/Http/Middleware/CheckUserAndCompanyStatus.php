<?php
// app/Http/Middleware/CheckUserAndCompanyStatus.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class CheckUserAndCompanyStatus
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            // Verifica status do usuário
            if (!$user->status) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                Session::flash('filament.notifications', [
                    [
                        'type' => 'danger',
                        'title' => 'Usuário inativo',
                        'body' => 'Entre em contato com o administrador.'
                    ]
                ]);
                return redirect('/admin/login');
            }
            
            // Verifica status da empresa do usuário
            if ($user->empresa && !$user->empresa->status) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                Session::flash('filament.notifications', [
                    [
                        'type' => 'danger',
                        'title' => 'Empresa inativa',
                        'body' => 'Entre em contato com o administrador.'
                    ]
                ]);
                return redirect('/admin/login');
            }
            
            // VERIFICAÇÃO DE VALIDADE DA ASSINATURA
            // Se a empresa não for admin (id_empresa != 1) e tiver data de validade
            if ($user->id_empresa != 1 && $user->empresa && $user->empresa->data_validade) {
                
                // Verifica se a assinatura expirou
                if (Carbon::now()->gt($user->empresa->data_validade)) {
                    Auth::logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    
                    // Desativa a empresa automaticamente
                    $user->empresa->status = false;
                    $user->empresa->save();
                    
                    Session::flash('filament.notifications', [
                        [
                            'type' => 'danger',
                            'title' => 'Assinatura Expirada',
                            'body' => 'A assinatura da sua empresa expirou em ' . 
                                      $user->empresa->data_validade->format('d/m/Y') . 
                                      '. Entre em contato para renovar.'
                        ]
                    ]);
                    return redirect('/admin/login');
                }
                
                // Verifica se está próximo do vencimento (30 dias) e avisa
                $diasRestantes = Carbon::now()->diffInDays($user->empresa->data_validade, false);
                
                if ($diasRestantes <= 30 && $diasRestantes > 0) {
                    // Aviso que aparece no dashboard
                    session()->flash('aviso_vencimento', [
                        'dias' => $diasRestantes,
                        'data_validade' => $user->empresa->data_validade->format('d/m/Y')
                    ]);
                }
            }
        }
        
        return $next($request);
    }
}
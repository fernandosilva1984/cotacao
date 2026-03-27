<?php
// app/Http/Middleware/CheckUserAndCompanyStatus.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Filament\Notifications\Notification;

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
                // Usa Notification do Filament
                // Formato CORRETO para notificação do Filament
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
        }
        
        return $next($request);
    }
}
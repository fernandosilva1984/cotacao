<?php
// app/Filament/Pages/Auth/Login.php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;

class Login extends BaseLogin
{
    // Personalizar o título da página
    public function getTitle(): string|Htmlable
    {
        return 'Login - Sistema de Cotações';
    }

    // Personalizar o cabeçalho
    public function getHeading(): string|Htmlable
    {
        return 'Sistema de Cotações';
    }

    // Personalizar o subcabeçalho
    public function getSubheading(): string|Htmlable
    {
        return 'Faça login para acessar o sistema';
    }

    // Personalizar o formulário
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getRememberFormComponent(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    // Personalizar a logo
    public function getBrandLogo(): string|Htmlable|null
    {
        return asset('images/logo.png');
    }

    // Personalizar altura da logo
    public function getBrandLogoHeight(): ?string
    {
        return '3rem';
    }
}
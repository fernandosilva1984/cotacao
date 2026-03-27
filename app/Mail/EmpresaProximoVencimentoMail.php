<?php
// app/Mail/EmpresaProximoVencimentoMail.php

namespace App\Mail;

use App\Models\Empresa;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class EmpresaProximoVencimentoMail extends Mailable
{
    use Queueable, SerializesModels;

    public Empresa $empresa;
    public int $diasRestantes;

    public function __construct(Empresa $empresa)
    {
        $this->empresa = $empresa;
        $this->diasRestantes = Carbon::now()->diffInDays($empresa->data_validade);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "📅 Renove sua assinatura - Vence em {$this->diasRestantes} dias",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.empresa-proximo-vencimento',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
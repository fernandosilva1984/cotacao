<?php
// app/Mail/EmpresaVencidaMail.php

namespace App\Mail;

use App\Models\Empresa;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmpresaVencidaMail extends Mailable
{
    use Queueable, SerializesModels;

    public Empresa $empresa;

    public function __construct(Empresa $empresa)
    {
        $this->empresa = $empresa;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ Assinatura Expirada - ' . $this->empresa->nome_fantasia,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.empresa-vencida',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
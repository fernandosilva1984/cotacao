<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Carbon\Carbon;

class Empresa extends Model
{
    use HasFactory, Notifiable, SoftDeletes, LogsActivity;
    
protected $fillable = [
        'razao_social',
        'nome_fantasia',
        'endereco',
        'bairro',
        'cidade',
        'cnpj',
        'contato',
        'email',
        'email_host',
        'email_port',
        'email_username',
        'email_password',
        'status',
        'data_ativacao',
        'data_validade',
        'plano',
        'valor_plano',
        'codigo_assinatura',
        'observacoes_assinatura'
    ];

    protected $casts = [
        'status' => 'boolean',
        'data_ativacao' => 'date',
        'data_validade' => 'date',
        'valor_plano' => 'decimal:2'
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    public function users()
    {
        return $this->hasMany(User::class, 'id_empresa');
    }

    public function fornecedores()
    {
        return $this->hasMany(Fornecedor::class, 'id_empresa');
    }

    public function marcas()
    {
        return $this->hasMany(Marca::class, 'id_empresa');
    }

    public function produtos()
    {
        return $this->hasMany(Produto::class, 'id_empresa');
    }

    public function cotacoes()
    {
        return $this->hasMany(Cotacao::class, 'id_empresa');
    }

    public function ordensPedido()
    {
        return $this->hasMany(OrdemPedido::class, 'id_empresa');
    }
     // Verifica se a empresa está ativa e com assinatura válida
    public function isAtiva(): bool
    {
        if (!$this->status) {
            return false;
        }
        
        if (!$this->data_validade) {
            return false;
        }
        
        return Carbon::now()->lte($this->data_validade);
    }

    // Verifica se está perto do vencimento (ex: 30 dias)
    public function isProximoVencimento(int $dias = 30): bool
    {
        if (!$this->data_validade) {
            return false;
        }
        
        $diasRestantes = Carbon::now()->diffInDays($this->data_validade, false);
        return $diasRestantes > 0 && $diasRestantes <= $dias;
    }

    // Verifica se está vencida
    public function isVencida(): bool
    {
        if (!$this->data_validade) {
            return true;
        }
        
        return Carbon::now()->gt($this->data_validade);
    }

    // Obtém dias restantes de assinatura
    public function getDiasRestantes(): int
    {
        if (!$this->data_validade) {
            return 0;
        }
        
        $dias = Carbon::now()->diffInDays($this->data_validade, false);
        return max(0, $dias);
    }

    // Ativa a empresa por um período
    public function ativarPorPeriodo(int $dias, string $plano, float $valor): void
    {
        $this->data_ativacao = Carbon::now();
        $this->data_validade = Carbon::now()->addDays($dias);
        $this->plano = $plano;
        $this->valor_plano = $valor;
        $this->status = true;
        $this->save();
    }

    // Renova a assinatura
    public function renovar(int $dias, string $plano, float $valor): void
    {
        $novaData = Carbon::now()->addDays($dias);
        
        // Se já tiver data de validade e ela for futura, soma
        if ($this->data_validade && $this->data_validade->gt(Carbon::now())) {
            $novaData = $this->data_validade->addDays($dias);
        }
        
        $this->data_validade = $novaData;
        $this->plano = $plano;
        $this->valor_plano = $valor;
        $this->status = true;
        $this->save();
    }

    // Scopes úteis
    public function scopeAtivas($query)
    {
        return $query->where('status', true)
            ->where('data_validade', '>=', Carbon::now());
    }

    public function scopeVencidas($query)
    {
        return $query->where(function($q) {
            $q->where('status', false)
              ->orWhere('data_validade', '<', Carbon::now());
        });
    }

    public function scopeVencemEmBreve($query, int $dias = 30)
    {
        return $query->where('status', true)
            ->where('data_validade', '>=', Carbon::now())
            ->where('data_validade', '<=', Carbon::now()->addDays($dias));
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class OrdemPedido extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $table = 'ordens_pedido';
        //protected $table = 'proventos_ativos';
    protected $primaryKey = 'id'; // Defina a chave primária, se necessário

    protected $fillable = [
        'id_empresa',
        'id_usuario',
        'id_fornecedor',
        'id_cotacao',
        'data',
        'numero',
        'observacao',
        'valor_total',
        'status',
    ];

    protected $casts = [
        'data' => 'date',
        'valor_total' => 'decimal:2',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class, 'id_fornecedor');
    }

    public function cotacao(): BelongsTo
    {
        return $this->belongsTo(Cotacao::class, 'id_cotacao');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrdemPedidoItem::class, 'id_ordem_pedido');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ordem) {
            $ordem->numero = static::gerarNumero();
        });
    }

    private static function gerarNumero(): string
    {
        $ano = date('Y');
        $ultimaOrdem = self::where('numero', 'like', "OP{$ano}%")
            ->orderBy('numero', 'desc')
            ->first();

        if ($ultimaOrdem) {
            $ultimoNumero = (int) Str::after($ultimaOrdem->numero, "OP{$ano}");
            $novoNumero = $ultimoNumero + 1;
        } else {
            $novoNumero = 1;
        }

        return "OP{$ano}" . str_pad($novoNumero, 5, '0', STR_PAD_LEFT);
    }

    public function calcularTotal(): void
    {
        $this->valor_total = $this->items->sum('valor_total_prod');
        $this->save();
    }
}
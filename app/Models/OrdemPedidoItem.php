<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class OrdemPedidoItem extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
     protected $table = 'ordem_pedido_items';
        //protected $table = 'proventos_ativos';
    protected $primaryKey = 'id'; // Defina a chave primária, se necessário

    protected $fillable = [
        'id_ordem_pedido',
        'id_produto',
        'id_marca',
        'id_cotacao_item',
        'descricao_produto',
        'descricao_marca',
        'quantidade',
        'valor_unitario',
        'valor_total_prod',
        'observacao',
    ];

    protected $casts = [
        'quantidade' => 'decimal:2',
        'valor_unitario' => 'decimal:2',
        'valor_total_prod' => 'decimal:2',
    ];

    public function ordemPedido(): BelongsTo
    {
        return $this->belongsTo(OrdemPedido::class, 'id_ordem_pedido');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'id_produto');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'id_marca');
    }

    public function cotacaoItem(): BelongsTo
    {
        return $this->belongsTo(CotacaoItem::class, 'id_cotacao_item');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->valor_total_prod = $item->quantidade * $item->valor_unitario;
        });
    }
}
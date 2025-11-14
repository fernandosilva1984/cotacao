<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Produto extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $fillable = [
        'id_empresa',
        'id_marca',
        'descricao',
        'observacao',
        'codigo_barras',
        'unidade_medida',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'id_marca');
    }

    public function cotacaoItems(): HasMany
    {
        return $this->hasMany(CotacaoItem::class, 'id_produto');
    }

    public function ordemPedidoItems(): HasMany
    {
        return $this->hasMany(OrdemPedidoItem::class, 'id_produto');
    }
}
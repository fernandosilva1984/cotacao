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

class CotacaoItem extends Model
{
    use HasFactory, Notifiable, SoftDeletes, LogsActivity;
protected $table = 'cotacao_items';
        //protected $table = 'proventos_ativos';
    protected $primaryKey = 'id'; // Defina a chave primária, se necessário

    protected $fillable = [
        'id_cotacao',
        'id_produto',
        'id_marca',
        'descricao_produto',
        'descricao_marca',
        'quantidade',
        'valor_unitario',
        'valor_total_prod',
        'valor_unitario_resposta',
        'valor_total_resposta',
        'observacao',
        'observacao_resposta',
        'selecionado',
    ];

    protected $casts = [
        'quantidade' => 'decimal:2',
        'valor_unitario' => 'decimal:2',
        'valor_total_prod' => 'decimal:2',
        'valor_unitario_resposta' => 'decimal:2',
        'valor_total_resposta' => 'decimal:2',
        'selecionado' => 'boolean',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    public function cotacao(): BelongsTo
    {
        return $this->belongsTo(Cotacao::class, 'id_cotacao');
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'id_produto');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class, 'id_marca');
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->valor_total_prod = $item->quantidade * $item->valor_unitario;
            
            if ($item->valor_unitario_resposta) {
                $item->valor_total_resposta = $item->quantidade * $item->valor_unitario_resposta;
            }
        });
    }
}
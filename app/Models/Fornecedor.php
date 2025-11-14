<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Fornecedor extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    protected $table = 'fornecedores';
        //protected $table = 'proventos_ativos';
    protected $primaryKey = 'id'; // Defina a chave primária, se necessário

    protected $fillable = [
         'id_empresa',
        'nome',
        'endereco',
        'email',
        'razao_social',
        'nome_fantasia',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function cotacoes()
    {
        return $this->belongsToMany(Cotacao::class, 'cotacao_fornecedor', 'id_fornecedor', 'id_cotacao')
            ->withPivot('status', 'resposta_fornecedor', 'data_envio', 'data_resposta')
            ->withTimestamps();
    }

    public function ordensPedido(): HasMany
    {
        return $this->hasMany(OrdemPedido::class, 'id_fornecedor');
    }
}
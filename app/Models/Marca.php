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

class Marca extends Model
{
   use HasFactory, Notifiable, SoftDeletes, LogsActivity;
    protected $fillable = [
        'id_empresa',
        'nome',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class, 'id_marca');
    }

    public function cotacaoItems(): HasMany
    {
        return $this->hasMany(CotacaoItem::class, 'id_marca');
    }

    public function ordemPedidoItems(): HasMany
    {
        return $this->hasMany(OrdemPedidoItem::class, 'id_marca');
    }
}
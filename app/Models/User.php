<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
//use Filament\Models\Contracts\FilamentUser;
//use Filament\Panel;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;

class User extends Authenticatable 
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, LogsActivity, HasRoles;
    protected $guard_name = 'web';

     protected $fillable = [
        'name',
        'email',
        'password',
        'id_empresa',
        'status',
        'is_master',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'status' => 'boolean',
        'is_master' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }
   /* public function canAccessFilament(): bool
    {
        return $this->hasRole(['Administrador','Usuário']) || $this->is_master;
    }*/

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function cotacoes()
    {
        return $this->hasMany(Cotacao::class, 'id_usuario');
    }

    public function ordensPedido()
    {
        return $this->hasMany(OrdemPedido::class, 'id_usuario');
    }

    public function isMaster(): bool
    {
        return $this->is_master;
    }
}
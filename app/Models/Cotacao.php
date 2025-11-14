<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class Cotacao extends Model
{
    use HasFactory, Notifiable, SoftDeletes;
    
    protected $table = 'cotacoes';
        //protected $table = 'proventos_ativos';
    protected $primaryKey = 'id'; // Defina a chave primária, se necessário

    protected $fillable = [
        'id_empresa',
        'id_usuario',
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

    // Relação com Fornecedor
    public function fornecedor(): BelongsTo
    {
        return $this->belongsTo(Fornecedor::class, 'id_fornecedor');
    }
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    public function fornecedores()
    {
        return $this->belongsToMany(Fornecedor::class, 'cotacao_fornecedor', 'id_cotacao', 'id_fornecedor')
            ->withPivot('status', 'resposta_fornecedor', 'data_envio', 'data_resposta')
            ->withTimestamps();
    }

    public function items(): HasMany
    {
        return $this->hasMany(CotacaoItem::class, 'id_cotacao');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cotacao) {
            $cotacao->numero = static::gerarNumero();
        });
    }

    public static function gerarNumero(): string
    {
        $year = date('Y');
        $last = static::whereYear('created_at', $year)->latest()->first();
        $sequence = $last ? (int)substr($last->numero, -6) + 1 : 1;
        
        return 'COT' . $year . str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }

    public function calcularTotal(): void
    {
        $this->valor_total = $this->items->sum('valor_total_prod');
        $this->save();
    }

    /**
     * Marcar cotação como enviada para um fornecedor específico
     */
    public function marcarComoEnviadaParaFornecedor($fornecedorId): bool
    {
        try {
            $result = $this->fornecedores()->updateExistingPivot($fornecedorId, [
                'status' => 'enviada',
                'data_envio' => now(),
                'updated_at' => now(),
            ]);

            // Verificar se todos os fornecedores foram enviados
            $this->verificarStatusGeral();

            return true;
        } catch (\Exception $e) {
            \Log::error("Erro ao marcar como enviada: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar e atualizar status geral da cotação
     */
    private function verificarStatusGeral(): void
    {
        $pendentes = $this->fornecedores()->wherePivot('status', 'pendente')->count();
        $enviadas = $this->fornecedores()->wherePivot('status', 'enviada')->count();
        $respondidas = $this->fornecedores()->wherePivot('status', 'respondida')->count();
        
        // Se não há pendentes e pelo menos uma foi enviada, marca como enviada
        if ($pendentes === 0 && $enviadas > 0 && $this->status === 'pendente') {
            $this->update(['status' => 'enviada']);
        }
        
        // Se todas foram respondidas, marca como respondida
        $totalFornecedores = $this->fornecedores()->count();
        if ($respondidas === $totalFornecedores && $totalFornecedores > 0) {
            $this->update(['status' => 'respondida']);
        }
    }

   /**
     * Processar resposta de um fornecedor
     */
    public function processarRespostaFornecedor($fornecedorId, $resposta): bool
    {
        try {
            $this->fornecedores()->updateExistingPivot($fornecedorId, [
                'status' => 'respondida',
                'resposta_fornecedor' => $resposta,
                'data_resposta' => now(),
                'updated_at' => now(),
            ]);

            $this->verificarStatusGeral();
            return true;
        } catch (\Exception $e) {
            \Log::error("Erro ao processar resposta: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar status de um fornecedor específico
     */
    public function getStatusFornecedor($fornecedorId): ?string
    {
        $fornecedor = $this->fornecedores()->where('fornecedores.id', $fornecedorId)->first();
        return $fornecedor ? $fornecedor->pivot->status : null;
    }

    /**
     * Obter fornecedores por status
     */
    public function fornecedoresPorStatus($status)
    {
        return $this->fornecedores()->wherePivot('status', $status)->get();
    }
   public function parsearRespostaFornecedor(string $resposta): array
    {
        $valores = [];
        $linhas = explode("\n", $resposta);
        
        foreach ($linhas as $linha) {
            if (preg_match('/item\s*(\d+).*?R\$\s*([\d,\.]+)/i', $linha, $matches)) {
                $itemIndex = $matches[1] - 1;
                $valor = (float) str_replace(['.', ','], ['', '.'], $matches[2]);
                $valores[$itemIndex] = $valor;
            }
        }
        
        return $valores;
    }
}
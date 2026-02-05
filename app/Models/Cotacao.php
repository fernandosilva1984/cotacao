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

class Cotacao extends Model
{
    use HasFactory, Notifiable, SoftDeletes, LogsActivity;
    
    protected $table = 'cotacoes';
    protected $primaryKey = 'id';

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
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty();
    }

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
            $cotacao->numero = static::gerarNumero($cotacao->id_empresa);
        });
    }

    // Atualize o método gerarNumero() para:

/**
 * Gera número da cotação no formato: COT-ID-ANO-SEQUENCIA
 * Garante unicidade mesmo em casos de concorrência
 */
public static function gerarNumero($idEmpresa): string
{
    $year = date('Y');
    $maxTentativas = 10; // Número máximo de tentativas para evitar loop infinito
    $tentativa = 0;
    
    do {
        $tentativa++;
        
        // Busca a última cotação da empresa específica no ano atual
        $last = static::where('id_empresa', $idEmpresa)
            ->whereYear('created_at', $year)
            ->orderBy('numero', 'desc')
            ->first();
        
        if (!$last) {
            $sequence = 1;
        } else {
            // Extrai a sequência do número existente
            $pattern = '/^COT-' . $idEmpresa . '-' . $year . '-(\d{6})$/';
            if (preg_match($pattern, $last->numero, $matches)) {
                $sequence = (int)$matches[1] + 1;
            } else {
                // Se o padrão não corresponder, busca o máximo e adiciona 1
                $maxSequence = static::where('id_empresa', $idEmpresa)
                    ->whereYear('created_at', $year)
                    ->where('numero', 'like', "COT-{$idEmpresa}-{$year}-%")
                    ->max('numero');
                
                if ($maxSequence) {
                    if (preg_match($pattern, $maxSequence, $matches)) {
                        $sequence = (int)$matches[1] + 1;
                    } else {
                        $sequence = 1;
                    }
                } else {
                    $sequence = 1;
                }
            }
        }
        
        $novoNumero = sprintf('COT-%d-%s-%06d', $idEmpresa, $year, $sequence);
        
        // Verifica se o número já existe (para evitar concorrência)
        $existe = static::where('numero', $novoNumero)->exists();
        
        if (!$existe) {
            return $novoNumero;
        }
        
        // Se existir, tenta novamente com sequência incrementada
        $sequence++;
        
    } while ($tentativa < $maxTentativas);
    
    // Se não conseguir gerar um número único, usa timestamp
    return sprintf('COT-%d-%s-%s', $idEmpresa, $year, time());
}
    /**
     * Método alternativo para geração de número usando contagem
     */
    public static function gerarNumeroAlternativo($idEmpresa): string
    {
        $year = date('Y');
        
        // Conta quantas cotações a empresa já tem no ano
        $count = static::where('id_empresa', $idEmpresa)
            ->whereYear('created_at', $year)
            ->count();
        
        $sequence = $count + 1;
        
        return sprintf('COT-%d-%s-%06d', $idEmpresa, $year, $sequence);
    }

    /**
     * Extrai componentes do número da cotação
     */
    public function parseNumero(): array
    {
        $pattern = '/^COT-(\d+)-(\d{4})-(\d{6})$/';
        if (preg_match($pattern, $this->numero, $matches)) {
            return [
                'id_empresa' => (int)$matches[1],
                'ano' => (int)$matches[2],
                'sequencia' => (int)$matches[3],
            ];
        }
        
        return [
            'id_empresa' => null,
            'ano' => null,
            'sequencia' => null,
        ];
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
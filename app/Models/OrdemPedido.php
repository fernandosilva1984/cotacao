<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class OrdemPedido extends Model
{
    use HasFactory, Notifiable, SoftDeletes, LogsActivity;
    
    protected $table = 'ordens_pedido';
    protected $primaryKey = 'id';

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
            // Usa transação para evitar concorrência
            DB::transaction(function () use ($ordem) {
                $ordem->numero = static::gerarNumeroSeguro($ordem->id_empresa);
            }, 3); // 3 tentativas
        });
    }

    /**
     * Gera número da ordem de pedido no formato: OP-ID-ANO-SEQUENCIA
     * Versão segura contra concorrência
     */
    private static function gerarNumeroSeguro($idEmpresa): string
    {
        $ano = date('Y');
        $maxTentativas = 10;
        $tentativa = 0;
        
        do {
            $tentativa++;
            
            // Busca o maior número sequencial existente
            $maxSequencia = self::getMaxSequencia($idEmpresa, $ano);
            $novoNumero = $maxSequencia + 1;
            
            $numeroFormatado = sprintf('OP-%d-%s-%05d', $idEmpresa, $ano, $novoNumero);
            
            // Verifica se já existe
            $existe = self::where('numero', $numeroFormatado)->exists();
            
            if (!$existe) {
                return $numeroFormatado;
            }
            
            // Aguarda um pouco antes de tentar novamente (para concorrência)
            if ($tentativa > 1) {
                usleep(100000); // 100ms
            }
            
        } while ($tentativa < $maxTentativas);
        
        // Fallback: usa microtime para garantir unicidade
        return sprintf('OP-%d-%s-%s', $idEmpresa, $ano, substr(str_replace('.', '', microtime(true)), -5));
    }

    /**
     * Obtém a maior sequência existente para a empresa/ano
     */
    private static function getMaxSequencia($idEmpresa, $ano): int
    {
        // Busca o maior número existente usando REGEXP
        $maxNumero = self::where('id_empresa', $idEmpresa)
            ->whereYear('created_at', $ano)
            ->where('numero', 'regexp', '^OP-' . $idEmpresa . '-' . $ano . '-[0-9]{5}$')
            ->max('numero');
        
        if ($maxNumero && preg_match('/^OP-' . $idEmpresa . '-' . $ano . '-(\d{5})$/', $maxNumero, $matches)) {
            return (int) $matches[1];
        }
        
        // Alternativa: busca o maior valor dos últimos 5 dígitos
        $maxSequencia = self::where('id_empresa', $idEmpresa)
            ->whereYear('created_at', $ano)
            ->where('numero', 'like', "OP-{$idEmpresa}-{$ano}-%")
            ->max(DB::raw('CAST(SUBSTRING(numero, -5) AS UNSIGNED)'));
        
        return $maxSequencia ?: 0;
    }

    /**
     * Gera número da ordem de pedido no formato: OP-ID-ANO-SEQUENCIA
     * Mantido para compatibilidade
     */
    private static function gerarNumero($idEmpresa): string
    {
        return self::gerarNumeroSeguro($idEmpresa);
    }

    /**
     * Método alternativo para geração de número usando contagem
     */
    public static function gerarNumeroAlternativo($idEmpresa): string
    {
        $ano = date('Y');
        
        // Conta quantas ordens de pedido a empresa já tem no ano
        $count = self::where('id_empresa', $idEmpresa)
            ->whereYear('created_at', $ano)
            ->count();
        
        $novoNumero = $count + 1;
        
        $numeroFormatado = sprintf('OP-%d-%s-%05d', $idEmpresa, $ano, $novoNumero);
        
        // Verifica se já existe (para segurança)
        $existe = self::where('numero', $numeroFormatado)->exists();
        
        if ($existe) {
            // Se existir, busca o próximo disponível
            return self::gerarNumeroSeguro($idEmpresa);
        }
        
        return $numeroFormatado;
    }

    /**
     * Extrai componentes do número da ordem de pedido
     */
    public function parseNumero(): array
    {
        $pattern = '/^OP-(\d+)-(\d{4})-(\d{5})$/';
        if (preg_match($pattern, $this->numero, $matches)) {
            return [
                'id_empresa' => (int)$matches[1],
                'ano' => (int)$matches[2],
                'sequencia' => (int)$matches[3],
            ];
        }
        
        // Tentativa de parse para números antigos (formato: OPANO00001)
        $patternAntigo = '/^OP(\d{4})(\d{5})$/';
        if (preg_match($patternAntigo, $this->numero, $matches)) {
            return [
                'id_empresa' => null,
                'ano' => (int)$matches[1],
                'sequencia' => (int)$matches[2],
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
     * Gera um número de ordem de pedido para migração de dados antigos
     */
    public static function gerarNumeroParaMigracao($idEmpresa, $ano, $sequencia): string
    {
        return sprintf('OP-%d-%s-%05d', $idEmpresa, $ano, $sequencia);
    }

    /**
     * Converte número antigo para o novo formato
     */
    public function converterParaNovoFormato($idEmpresa): string
    {
        $parsed = $this->parseNumero();
        
        if ($parsed['ano'] && $parsed['sequencia']) {
            return self::gerarNumeroParaMigracao(
                $idEmpresa ?: $this->id_empresa,
                $parsed['ano'],
                $parsed['sequencia']
            );
        }
        
        return static::gerarNumeroSeguro($idEmpresa ?: $this->id_empresa);
    }

    /**
     * CORREÇÃO DE DADOS EXISTENTES:
     * Use este método para corrigir números duplicados existentes
     */
    public static function corrigirNumerosDuplicados()
    {
        // Encontra números duplicados
        $duplicatas = DB::table('ordens_pedido')
            ->select('numero', DB::raw('COUNT(*) as count'))
            ->groupBy('numero')
            ->having('count', '>', 1)
            ->get();
        
        foreach ($duplicatas as $dup) {
            $ordens = self::where('numero', $dup->numero)
                ->orderBy('created_at')
                ->get();
            
            // Mantém a primeira, renomeia as outras
            for ($i = 1; $i < count($ordens); $i++) {
                $ordem = $ordens[$i];
                $ano = date('Y', strtotime($ordem->created_at));
                
                // Gera novo número único
                $novoNumero = self::gerarNumeroUnicoParaCorrecao($ordem->id_empresa, $ano);
                $ordem->numero = $novoNumero;
                $ordem->save();
                
                \Log::info("Ordem Pedido {$ordem->id}: Número corrigido de {$dup->numero} para {$novoNumero}");
            }
        }
    }

    /**
     * Gera número único para correção de dados
     */
    private static function gerarNumeroUnicoParaCorrecao($idEmpresa, $ano): string
    {
        $tentativas = 0;
        $maxTentativas = 100;
        
        do {
            // Começa com um número alto para evitar conflitos
            $sequencia = 90000 + $tentativas; // Começa em 90000
            $numero = sprintf('OP-%d-%s-%05d', $idEmpresa, $ano, $sequencia);
            
            $existe = self::where('numero', $numero)->exists();
            $tentativas++;
            
            if (!$existe) {
                return $numero;
            }
        } while ($tentativas < $maxTentativas);
        
        // Fallback extremo
        return sprintf('OP-%d-%s-%s', $idEmpresa, $ano, uniqid());
    }
}
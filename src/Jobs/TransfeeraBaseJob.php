<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Jobs;

use FlavioMoreir4\Transfeera\Services\RateLimitMonitor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job base reutilizável para processamento de operações Transfeera em fila.
 *
 * Features:
 * - Retry com backoff consciente do rate limit (baseado no header Retry-After)
 * - Log estruturado de cada tentativa
 * - Integração com RateLimitMonitor para evitar throttling
 * - Máximo de 5 tentativas com atraso progressivo
 *
 * Estenda esta classe e implemente o método handle():
 *
 * ```php
 * use FlavioMoreir4\Transfeera\Facades\Transfeera;
 *
 * class MeuJob extends TransfeeraBaseJob
 * {
 *     public function handle(): void
 *     {
 *         $this->logInfo('Iniciando processamento...');
 *         Transfeera::batches()->create($this->data);
 *         $this->logInfo('Processamento concluído com sucesso.');
 *     }
 * }
 * ```
 */
abstract class TransfeeraBaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Número máximo de tentativas.
     */
    public int $maxAttempts = 5;

    /**
     * @param  array<string, mixed>  $data  Dados para processamento
     * @param  string  $domain  Domínio da API (para monitor de rate limit)
     */
    public function __construct(
        public readonly array $data = [],
        public readonly string $domain = 'payments',
    ) {}

    /**
     * Executa o job. Subclasses devem implementar a lógica real.
     */
    abstract public function handle(): void;

    /**
     * Determina o atraso para a próxima tentativa com base no rate limit.
     *
     * Se o rate limit foi excedido, usa Retry-After + margem de segurança.
     * Caso contrário, usa backoff progressivo padrão.
     *
     * @return array<int, int> Segundos de atraso para cada tentativa
     */
    public function backoff(): array
    {
        $monitor = app(RateLimitMonitor::class);
        $reset = $monitor->getReset($this->domain);

        if ($reset !== null && $reset > time()) {
            $retryAfter = $reset - time() + 2; // +2s margem de segurança

            return [
                0,
                $retryAfter,
                $retryAfter * 2,
                $retryAfter * 4,
                $retryAfter * 8,
            ];
        }

        // Backoff progressivo padrão: 5s, 15s, 45s, 135s, 405s
        return [5, 15, 45, 135, 405];
    }

    /**
     * Callback executado quando o job falha após todas as tentativas.
     */
    public function failed(Throwable $e): void
    {
        Log::error('[TransfeeraJob] Falha definitiva após todas as tentativas.', [
            'job' => static::class,
            'domain' => $this->domain,
            'error' => $e->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Loga uma mensagem informativa com contexto do job.
     */
    protected function logInfo(string $message): void
    {
        Log::info("[TransfeeraJob] {$message}", [
            'job' => static::class,
            'domain' => $this->domain,
            'attempt' => $this->attempts(),
        ]);
    }

    /**
     * Loga um aviso com contexto do job.
     */
    protected function logWarning(string $message): void
    {
        Log::warning("[TransfeeraJob] {$message}", [
            'job' => static::class,
            'domain' => $this->domain,
            'attempt' => $this->attempts(),
        ]);
    }

    /**
     * Loga um erro com contexto do job.
     */
    protected function logError(string $message): void
    {
        Log::error("[TransfeeraJob] {$message}", [
            'job' => static::class,
            'domain' => $this->domain,
            'attempt' => $this->attempts(),
        ]);
    }
}

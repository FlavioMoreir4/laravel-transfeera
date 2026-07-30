<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Console\Commands;

use FlavioMoreir4\Transfeera\Auth\AccessToken;
use FlavioMoreir4\Transfeera\Auth\TokenManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class CacheWarmCommand extends Command
{
    protected $signature = 'transfeera:cache-warm
        {--account-id= : ID da conta digital (Hub de Contas) para pré-aquecer token multi-tenant}
        {--force : Força renovação mesmo se cache ainda válido}';

    protected $description = 'Pré-aquece o cache do token OAuth para evitar latência na primeira requisição';

    /**
     * Executa o comando.
     */
    public function handle(TokenManager $tokenManager): int
    {
        $accountId = $this->option('account-id');
        $accountId = is_string($accountId) ? $accountId : null;
        $force = (bool) $this->option('force');

        $cacheKey = 'transfeera_access_token'.($accountId ? ':'.$accountId : '');
        $label = $accountId ? "conta {$accountId}" : 'padrão';

        if (! $force) {
            /** @var AccessToken|null $cached */
            $cached = Cache::get($cacheKey);

            if ($cached instanceof AccessToken && $cached->isValid()) {
                $remaining = $cached->expiresAt() - time();
                $this->info("Token {$label} já está em cache e válido por mais {$remaining}s. Use --force para renovar.");

                return 0;
            }
        }

        $this->line("Pré-aquecendo token {$label}...");

        try {
            $token = $tokenManager->getToken($accountId);
            $this->info("✅ Token {$label} cacheado com sucesso (expira em ".date('H:i:s', $token->expiresAt()).').');
        } catch (Throwable $e) {
            $this->error("❌ Falha ao obter token {$label}: {$e->getMessage()}");

            return 1;
        }

        return 0;
    }
}

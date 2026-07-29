<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Concerns;

use FlavioMoreir4\Transfeera\Http\Connector;

/**
 * Classe base para todos os Resources da API.
 *
 * Fornece acesso ao Connector para que os Resources especializados
 * possam fazer requisições HTTP sem repetir a injeção de dependência.
 */
abstract class BaseResource
{
    public function __construct(
        protected readonly Connector $connector,
        protected readonly ?string $accountId = null,
    ) {}
}

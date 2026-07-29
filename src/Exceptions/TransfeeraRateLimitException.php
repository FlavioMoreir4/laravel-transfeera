<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Exceptions;

/**
 * Lançada quando o rate limit da API é excedido (HTTP 429).
 */
class TransfeeraRateLimitException extends TransfeeraException {}

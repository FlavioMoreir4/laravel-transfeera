<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Enums;

/**
 * Tipos de chave Pix suportados.
 */
enum PixKeyType: string
{
    case Cpf = 'cpf';
    case Cnpj = 'cnpj';
    case Email = 'email';
    case Phone = 'telefone';
    case Random = 'aleatoria';
    case Evp = 'evp';

    public function label(): string
    {
        return match ($this) {
            self::Cpf => 'CPF',
            self::Cnpj => 'CNPJ',
            self::Email => 'E-mail',
            self::Phone => 'Telefone',
            self::Random => 'Chave aleatória',
            self::Evp => 'EVP',
        };
    }
}

# Guia de Migração para Laravel 13

Este guia documenta a compatibilidade e migração do SDK **laravel-transfeera** para Laravel 13.

## Status Atual

| Recurso | Laravel 12 | Laravel 13 |
|---------|-----------|------------|
| SDK v1.10+ | ✅ Suporte total | ✅ Suporte total |
| PHP mínimo | 8.3 | 8.4 |
| `illuminate/*` | 12.x | 13.x |
| Orchestra Testbench | 10.x | 11.x |

## Requisitos

- PHP 8.4+
- Laravel 13.0+
- `orchestra/testbench:^11.0` (para desenvolvimento/testes)

## Instalação

O SDK não requer alterações na instalação. Basta atualizar seu projeto Laravel:

```bash
composer update flaviomoreir4/laravel-transfeera
```

Se estiver iniciando um novo projeto:

```bash
composer create-project laravel/laravel:^13.0 novo-projeto
composer require flaviomoreir4/laravel-transfeera
```

## O que mudou no Laravel 13 que afeta este SDK

### 1. Http facade

O Laravel 13 mantém a `Http` facade com a mesma API do Laravel 12. Todas as chamadas `Http::fake()`, `Http::response()`, e `Http::assertSent()` continuam funcionando sem alterações.

### 2. Cache

A cache facade (`Cache::store()`, `Cache::put()`, `Cache::get()`) mantém a mesma interface. O `TokenManager` do SDK (que usa cache para tokens OAuth2) continua compatível.

### 3. Config

`config()` helper e `Config` facade mantêm a mesma API. O `TransfeeraServiceProvider` que publica `config/transfeera.php` continua funcionando.

### 4. Eventos e Listeners

A interface de eventos do Laravel 13 mantém compatibilidade total com `ShouldQueue`, `SerializesModels`, e listeners registrados no `EventServiceProvider`.

## Atualizando dependências de teste

```bash
composer require --dev orchestra/testbench:^11.0
composer require --dev pestphp/pest:^5.0
composer require --dev larastan/larastan:^3.0
```

## Testando com Laravel 13

```bash
# Ambiente de teste com Laravel 13
composer update --with orchestra/testbench:^11.0
composer test
```

## Matriz de compatibilidade

| SDK | Laravel 12 | Laravel 13 |
|-----|-----------|------------|
| v1.9.x | ✅ | ❌ (não testado) |
| v1.10.x | ✅ | ✅ |
| v1.11+ | ✅ | ✅ |
| v2.0+ | ❌ (EOL) | ✅ |

## Problemas conhecidos

Nenhum problema conhecido. Se encontrar algo, abra uma issue no repositório.

## Rollback

Para voltar ao Laravel 12:

```bash
composer require laravel/framework:^12.0 --no-update
composer require --dev orchestra/testbench:^10.0 --no-update
composer update
```

# Changelog

Todas as mudanças importantes neste pacote serão documentadas aqui.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/),
e este projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

## [1.0.0] — 2025-07-29

### Adicionado

- **Fase 1 — Núcleo + Pagamentos**
  - Service Provider com auto-discovery
  - Facade `Transfeera`
  - `TokenManager` com cache e renovação automática
  - `Connector` com seleção de base URL por ambiente e domínio
  - `MtlsConfigurator` para mTLS em produção
  - Mapeamento de erros HTTP (401, 422, 429, 4xx/5xx)
  - `BatchResource` (CRUD + processar)
  - `TransferResource` (CRUD dentro de lote)
  - `BilletResource` (CRUD + consulta CIP)
  - `BankResource` (listar bancos)
  - `StatementResource` (saldo, resgate, relatório)
  - `RecurrenceResource` (listar, listar pagamentos, cancelar)
  - `PixResource` (consulta DICT, parse EMV)
  - Comando `php artisan transfeera:install`
  - Testes Pest (Unit + Feature)
  - PHPStan level 8
  - Rector configurado

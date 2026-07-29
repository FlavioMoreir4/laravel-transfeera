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
  - Testes Pest (Unit + Feature) — 26 testes
  - PHPStan level 8
  - Rector configurado

- **Fase 2 — Recebimentos**
  - `PixKeyResource` (CRUD, verificação, portabilidade claim/confirm/cancel)
  - `PixQrCodeResource` (estático, cobrança imediata, com vencimento, revogar)
  - `PixCashInResource` (listar, consultar por end2endId, devoluções)
  - `ChargeResource` (CRUD, cancelar, download PDF)
  - `PaymentLinkResource` (criar, consultar, excluir)
  - 17 novos testes de feature

- **Fase 3 — Pix Automático + Webhooks**
  - `AuthorizationResource` (CRUD, cancelar, atualizar split_payment)
  - `PaymentIntentResource` (CRUD, cancelar, reenviar retentativa)
  - `PaymentsWebhookResource` (URLs CRUD, eventos, reenvio)
  - `ReceivablesWebhookResource` (URLs CRUD, eventos, reenvio)
  - `ContaCertaWebhookResource` (URLs CRUD, eventos, reenvio)
  - `SignatureValidator` (validação HMAC-SHA256 com suporte a pagamentos e recebimentos)
  - `WebhookEvent` (evento Laravel dispatchable)
  - 18 novos testes de feature + 5 unitários
  - Total: **72 testes, 99 asserções**

- **Fase 4 — Conta Certa + Hub de Contas + MED**
  - `ValidationResource` (Conta Certa) — criar, listar, consultar validações
  - `BankResource` (Conta Certa) — listar bancos suportados
  - `AccountResource` (Hub de Contas) — criar, listar, consultar, encerrar contas digitais
  - `InfractionResource` (MED) — listar, consultar, enviar análise individual e em lote
  - 11 novos testes de feature
  - Total: **84 testes, 113 asserções**

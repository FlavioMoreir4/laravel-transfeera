# Registro de Decisões Arquiteturais (ADR)

Este diretório contém o registro de decisões arquiteturais (ADRs) do pacote Laravel Transfeera SDK, seguindo o formato de [Michael Nygard](https://cognitect.com/blog/2011/11/15/documenting-architecture-decisions).

## Decisões

| ID | Título | Status | Data |
|----|--------|--------|------|
| [ADR-001](001-zero-dependencies.md) | Zero dependências externas de produção | ✅ Aceito | 2025-07 |
| [ADR-002](002-native-readonly-dtos.md) | DTOs nativos `readonly class` sem bibliotecas | ✅ Aceito | 2025-07 |
| [ADR-003](003-domain-driven-resources.md) | Resources organizados por domínio de negócio | ✅ Aceito | 2025-07 |
| [ADR-004](004-oauth2-cache-strategy.md) | OAuth2 client_credentials com cache + lock | ✅ Aceito | 2025-07 |
| [ADR-005](005-mtls-strategy.md) | mTLS condicional apenas em produção | ✅ Aceito | 2025-07 |
| [ADR-006](006-never-break-compatibility.md) | "Nunca quebrar" — v2.0.0 cancelado | ✅ Aceito | 2025-07 |
| [ADR-007](007-webhook-hmac-validation.md) | Validação de webhook com HMAC-SHA256 | ✅ Aceito | 2025-07 |
| [ADR-008](008-monetary-cents.md) | Valores monetários em centavos (inteiros) | ✅ Aceito | 2025-07 |
| [ADR-009](009-multitenancy-accountid.md) | Multi-tenancy via escopo accountId | ✅ Aceito | 2025-07 |
| [ADR-010](010-testing-http-fake.md) | Testes com Http::fake() e fixtures estáticas | ✅ Aceito | 2025-07 |

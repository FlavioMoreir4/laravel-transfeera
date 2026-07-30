# ADR-003: Resources Organizados por Domínio de Negócio

- **Status:** ✅ Aceito
- **Data:** 2025-07-30

## Contexto

A API Transfeera cobre múltiplos domínios de negócio (pagamentos, recebimentos, validações, etc.). Cada domínio tem seus próprios endpoints, regras de erro e requisitos de autenticação (mTLS). Era necessário um padrão de organização que:

1. Refletisse a estrutura da API upstream
2. Facilitasse navegação e manutenção
3. Permitisse versionamento independente por domínio
4. Mantivesse consistência entre Resources

## Decisão

Organizar Resources em **subdiretórios por domínio** dentro de `src/Resources/`, cada um mapeando para uma pasta com nome semântico:

```
src/Resources/
├── Concerns/BaseResource.php      # Classe base abstrata com CRUD helpers
├── Payments/                       # 7 Resources
│   ├── BatchResource.php
│   ├── TransferResource.php
│   ├── BilletResource.php
│   ├── BankResource.php
│   ├── StatementResource.php
│   ├── RecurrenceResource.php
│   └── PixResource.php
├── Receivables/                    # 5 Resources
│   ├── PixKeyResource.php
│   ├── PixQrCodeResource.php
│   ├── PixCashInResource.php
│   ├── ChargeResource.php
│   └── PaymentLinkResource.php
├── PixAutomatico/                  # 2 Resources
├── Webhooks/                       # 3 Resources
├── ContaCerta/                     # 2 Resources
├── Accounts/                       # 1 Resource
└── Infractions/                    # 1 Resource
```

Cada Resource herda de `BaseResource`, que fornece:
- `getDTO()`, `postDTO()`, `putDTO()`, `patchDTO()` — métodos tipados com retorno DTO
- `getDTOList()` — listas de DTOs com extração automática de `data` wrapper
- `deleteRaw()` — deleção com retorno bruto

## Consequências

**Positivas:**
- Clareza: cada domínio é auto-contido
- Consistência: todos os Resources seguem o mesmo padrão de métodos
- Navegação rápida: qualquer desenvolvedor encontra o Resource correto
- Testes espelhados: `tests/Feature/` segue a mesma estrutura
- Isolamento: alterações em um domínio não afetam outros

**Negativas:**
- Duplicação de métodos CRUD boilerplate entre Resources
- Alguns Resources aceitam arrays diretamente (inconsistência com DTOs)
- `BaseResource` faz referência a `Connector` — acoplamento forte

## Alternativas Consideradas

1. **Resource único gigante** — Rejeitado. Violaria SRP e seria impossível de manter.
2. **Trait por domínio** — Rejeitado. Herança é mais idiomática e testável.
3. **Action Pattern (classes separadas por operação)** — Rejeitado. Superaria o necessário para um SDK. Resources com métodos são suficientes.

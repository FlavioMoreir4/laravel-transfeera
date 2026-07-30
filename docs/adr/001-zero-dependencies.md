# ADR-001: Zero Dependências Externas de Produção

- **Status:** ✅ Aceito
- **Data:** 2025-07-30
- **Decisão tomada por:** Flávio Moreira

## Contexto

Ao construir um SDK Laravel para uma API externa, a tentação natural é adicionar bibliotecas que "facilitam" o desenvolvimento: clientes HTTP alternativos (Saloon, Guzzle direto), builders de DTO (spatie/laravel-data), clientes de métricas (prometheus_push_gateway), etc.

Cada dependência adicional representa:
- Risco de conflito de versões com o projeto hospedeiro
- Aumento no surface de ataque de segurança
- Complexidade de manutenção (atualizações, breaking changes)
- Curva de aprendizado para contribuidores

## Decisão

O pacote terá **zero dependências externas de produção** além dos próprios componentes do Laravel:

```
illuminate/support    → ServiceProvider, Facade, helpers
illuminate/http       → HTTP Client (já embute Guzzle)
illuminate/contracts  → Cache, Events contracts
```

Para desenvolvimento, apenas ferramentas de qualidade:
```
orchestra/testbench   → Testes em Laravel isolado
pestphp/pest          → Test runner
larastan/larastan     → PHPStan level 8
rector/rector         → Refatoração automática
laravel/pint          → PSR-12 formatting
```

## Consequências

**Positivas:**
- Zero conflito de dependências com o projeto hospedeiro
- Instalação leve e rápida
- Manutenibilidade simplificada
- Segurança: surface de ataque mínimo

**Negativas:**
- Mais código manual para DTOs (sem spatie/laravel-data)
- Placeholder para métricas em vez de integração real com Prometheus
- Retry e timeouts dependem do Http::retry() do Laravel

## Alternativas Consideradas

1. **Saloon** — Rejeitado. Adicionaria dependência externa sem ganho significativo sobre `illuminate/http`.
2. **spatie/laravel-data** — Rejeitado. DTOs nativos `readonly class` são suficientes.
3. **Guzzle direto** — Rejeitado. `illuminate/http` já embute Guzzle e adiciona Facades, testes com `Http::fake()`.

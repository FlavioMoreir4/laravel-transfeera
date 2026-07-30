# ADR-006: Estratégia Híbrida — v2.0.0 Planejado com Deprecação

- **Status:** ✅ Aceito (substitui "v2 cancelado")
- **Data:** 2025-07-30
- **Última revisão:** 2025-07-30

## Contexto

O SDK foi inicialmente planejado com uma decisão de "nunca quebrar" (v2.0.0 cancelado) para proteger consumidores existentes de breaking changes. No entanto, essa decisão ignora que **todo ecossistema maduro evolui**:

- Laravel 12 eventualmente será EOL — precisaremos remover suporte
- PHP 8.3 features dão lugar a 8.4 (property hooks, etc.)
- A API pública do SDK tem inconsistências que só uma MAJOR pode limpar
- Pacotes de referência (Cashier, Spatie) fazem MAJORs periódicas com sucesso

## Decisão

Adotar **estratégia híbrida**: v1.x contínua com deprecação lenta, v2.0.0 planejada para ~**2026 Q1**.

### Timeline

```
Q3 2025          Q4 2025           Q1 2026
v1.10+           v1.13+            v2.0.0
├── @deprecated  ├── Deprecação    ├── Remove Laravel 12
│   em *Raw()    │   avançada      ├── Remove *Raw()
├── Suporte      ├── Últimas       ├── API unificada
│   Laravel 12   │   MINORs v1.x   │   (DTOs everywhere)
└── Foco em      └── Alerta        └── UPGRADE.md
    qualidade        em CI             completo
```

### O que muda na prática

| Hoje (v1.x) | Amanhã (v2.0) | Migração |
|-------------|---------------|----------|
| `@deprecated` em `*Raw()` | `*Raw()` removido | Use Resource methods |
| `array` nos returns sem DTO | Todos retornos tipados | `$e->getErrors()` |
| `Connector` público | `Connector` interno | Use Facade/Client |
| Laravel 12 suportado | Laravel 13+ apenas | `composer require laravel/framework:^13.0` |

### Regras de Deprecação

1. **@deprecated + @see** em PHPDoc apontando alternativa
2. **Log warning** em runtime (um por bootstrap, não por chamada)
3. **Aviso no CHANGELOG** com versão alvo de remoção
4. **6 meses entre deprecação e remoção**
5. **UPGRADE.md** com passo a passo para cada breaking change

## Consequências

**Positivas:**
- Compatibilidade no curto prazo (v1.x continua funcionando)
- Caminho claro de evolução sem sustos
- Alinhado com práticas de pacotes Laravel consolidados
- Permite limpar inconsistências acumuladas

**Negativas:**
- Consumidores precisarão migrar eventualmente
- Manutenção de código legado durante transição
- Complexidade de manter dois caminhos (com/sem deprecação)

## Alternativas Consideradas

1. **v2.0.0 cancelado (decisão anterior)** — Rejeitado. Impedia evolução e acumulava dívida técnica.
2. **v2.0.0 imediato** — Rejeitado. Prejudicaria consumidores existentes sem aviso.
3. **Deprecação sem MAJOR** — Rejeitado. `*Raw()` e Connector público exigem MAJOR para remover.

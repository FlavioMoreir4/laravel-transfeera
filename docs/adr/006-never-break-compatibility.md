# ADR-006: "Nunca Quebrar" — v2.0.0 Cancelado

- **Status:** ✅ Aceito
- **Data:** 2025-07-30

## Contexto

O SDK foi inicialmente planejado com uma v2.0.0 que introduziria 3 breaking changes:
1. Aceitar arrays em `create()`/`update()` em vez de DTOs obrigatórios
2. Remover métodos `*Raw()` que retornam array
3. Tornar `Connector` interno (fechar a classe)

Essas mudanças melhorariam a pureza da API pública, mas quebrariam todos os consumidores existentes.

## Decisão

**Cancelar v2.0.0 permanentemente.** Adotar a política de "Nunca quebrar" (Opção C):

- Arrays em `create()`/`update()` continuam aceitos
- Métodos `*Raw()` permanecem disponíveis
- `Connector` permanece público
- Apenas **MINORs** (features retrocompatíveis) e **PATCHs** (correções)
- Todo novo recurso deve ser adicionado sem alterar interfaces existentes

```php
// Isto continua funcionando PARA SEMPRE:
$batch = Transfeera::batches()->create(['name' => 'Lote']);  // array
$raw = $batch->createRaw([...]);                              // método raw
$connector = Transfeera::batches()->connector;                // connector público
```

## Consequências

**Positivas:**
- Zero custo de migração para consumidores existentes
- Confiança: consumidores sabem que atualizar o SDK não quebrará o sistema
- Menos pressão para "acertar de primeira" a API pública
- Compatibilidade garantida até o fim do suporte do Laravel 12/13

**Negativas:**
- Inconsistências da v1 permanecem (alguns métodos aceitam DTO, outros array)
- Impossibilidade de fazer refactors estruturais profundos
- Complexidade acumulada não pode ser "limpa" com breaking change

## Alternativas Consideradas

1. **v2.0.0 com breaking changes** — Rejeitado. Custo de migração injustificável para um SDK.
2. **Deprecação lenta com @deprecated + v2 eventual** — Rejeitado. Mesmo problema, só que adiado.
3. **Camada de compatibilidade (facade que aceita ambos)** — Rejeitado. Duplicaria a manutenção.

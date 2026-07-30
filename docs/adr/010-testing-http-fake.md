# ADR-010: Testes com `Http::fake()` e Fixtures Estáticas

- **Status:** ✅ Aceito
- **Data:** 2025-07-30

## Contexto

O SDK não deve fazer chamadas reais à API Transfeera durante os testes. Precisamos de uma estratégia de teste que:
1. Seja determinística (não depende de rede ou credenciais)
2. Cubra todos os cenários (sucesso, erro 401, 422, 429, 5xx)
3. Seja rápida (sem latência de rede)
4. Teste o SDK isoladamente, não a API upstream

## Decisão

Usar **`Http::fake()` do Laravel** com fixtures JSON extraídas de payloads reais da documentação:

```php
// Teste de Resource
test('cria lote com sucesso', function () {
    Http::fake([
        'api-sandbox.transfeera.com/batch' => Http::response([
            'id' => 'batch_123',
            'name' => 'Meu Lote',
            'status' => 'pending',
        ], 201),
    ]);

    $result = Transfeera::batches()->create(['name' => 'Meu Lote']);

    expect($result)->toHaveKey('id', 'batch_123');
});
```

**Padrões:**
- **Feature Tests:** testam Resources completos com `Http::fake()` global ou por URL
- **Unit Tests:** testam DTOs, TokenManager, SignatureValidator, middlewares
- **Fixtures:** arquivos JSON em `tests/Fixtures/` com payloads reais da documentação
- **Cache:** `Cache::flush()` + `Cache::store('array')->flush()` no `setUp()`
- **TokenManager:** store 'array' para isolar testes de autenticação

## Consequências

**Positivas:**
- 199 testes rodam em segundos
- Determinístico: mesmas fixtures, mesmos resultados
- Sem dependência de rede ou credenciais
- Cobertura de cenários de erro completos
- Http::fake() é suportado nativamente pelo Laravel

**Negativas:**
- Testes não detectam mudanças na API upstream (contrato não verificado)
- Manutenção de fixtures: precisam ser atualizadas se a API mudar
- Testes de integração reais exigem setup separado (não implementados)
- `Http::fake()` global pode mascarar URLs incorretas no código

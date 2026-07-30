# ADR-008: Valores Monetários em Centavos (Inteiros)

- **Status:** ✅ Aceito
- **Data:** 2025-07-30

## Contexto

A API Transfeera representa valores monetários em centavos (inteiros). O SDK precisa decidir se expõe os valores como recebe (centavos) ou converte para float (reais) para facilitar o uso.

## Decisão

**Manter centavos (inteiros) em toda a interface pública do SDK.** Nunca usar float.

```php
TransferDTO $dto = new TransferDTO(
    amount: 150000,        // R$ 1.500,00 — centavos
    pixKey: 'joao@email.com',
);

// A aplicação cliente faz a conversão na camada de UI:
// number_format($amount / 100, 2, ',', '.') → "1.500,00"
```

## Consequências

**Positivas:**
- Zero perda de precisão (problema clássico de floats: 0.1 + 0.2 ≠ 0.3)
- Consistência com a API upstream
- Operações de soma/comparação seguras sem arredondamento
- Armazenamento em banco de dados sem ambiguidade
- PHPStan detecta mistura de int/float

**Negativas:**
- Aplicação cliente precisa converter centavos → reais na UI
- Documentação precisa ser explícita sobre a unidade
- Frameworks de frontend podem esperar valores decimais

## Alternativas Consideradas

1. **Float com 2 casas decimais** — Rejeitado. Risco de precisão em operações matemáticas.
2. **String formatada ("1500.00")** — Rejeitado. Perde type safety e exige parsing.
3. **Objeto Money (value + currency)** — Rejeitado. Violaria ADR-001 (dependência externa).

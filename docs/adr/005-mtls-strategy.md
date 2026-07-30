# ADR-005: mTLS Condicional Apenas em Produção

- **Status:** ✅ Aceito
- **Data:** 2025-07-30

## Contexto

As APIs de Pagamentos e Conta Certa da Transfeera exigem certificação mútua TLS (mTLS) em produção. Em sandbox, o mTLS não é necessário. O SDK precisa aplicar o certificado apenas nos domínios corretos e apenas no ambiente correto.

## Decisão

Criar `MtlsConfigurator` que:
1. Verifica se o ambiente é `production` (sandbox ignora)
2. Aplica apenas nos domínios `payments` e `conta_certa`
3. Valida existencia dos arquivos de certificado e chave antes de configurar
4. Lança `TransfeeraException` descritiva se certificado estiver ausente em produção

```php
public function apply(PendingRequest $request): PendingRequest
{
    if ($this->environment !== 'production') {
        return $request;  // Sandbox: sem mTLS
    }

    // Valida paths...
    return $request->withOptions([
        'cert' => $certPath,
        'ssl_key' => $keyPath,
    ]);
}
```

No `Connector::buildRequest()`, o mTLS é aplicado condicionalmente:
```php
if (in_array($domain, [self::DOMAIN_PAYMENTS, self::DOMAIN_CONTA_CERTA], true)) {
    return $this->mtls->apply($request);
}
```

## Consequências

**Positivas:**
- Sandbox funciona sem configuração extra de certificado
- Produção falha cedo e claramente se certificado estiver ausente
- Isolamento: apenas os domínios que exigem mTLS são afetados

**Negativas:**
- Duas validações de arquivo similares (MtlsConfigurator + ServiceProvider::validateMtls())
- Caminhos absolutos no .env podem quebrar em deploys com paths diferentes

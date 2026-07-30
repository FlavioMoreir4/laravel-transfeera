# Comandos Artisan

O pacote `flaviomoreir4/laravel-transfeera` registra quatro comandos Artisan para facilitar a instalação, validação e operação do SDK.

---

## `transfeera:install`

Publica o arquivo de configuração e exibe as variáveis de ambiente necessárias.

```bash
php artisan transfeera:install
```

**O que faz:**

- Publica `config/transfeera.php` via tag `transfeera-config`
- Valida ambiente (`sandbox` ou `production`)
- Verifica se credenciais estão configuradas
- Em produção, alerta se certificado mTLS estiver ausente

---

## `transfeera:check`

Valida conectividade e credenciais com a API Transfeera.

```bash
php artisan transfeera:check
```

Saída esperada em caso de sucesso:

```text
🔍 Verificando conectividade e credenciais da API Transfeera...
🌐 Testando autenticação: https://login-api-sandbox.transfeera.com/authorization
OK: Credenciais validadas
```

Saída esperada em caso de falha:

```text
Falha na autenticação: {"error":"invalid_client"}
Falha na validação das credenciais
```

### Opções

| Opção | Descrição |
|-------|-----------|
| `--silent` | Suprime toda saída. Útil em CI/CD e health checks. Retorna código `0` em sucesso e `1` em falha. |

Exemplo em CI:

```bash
php artisan transfeera:check --silent || exit 1
```

### Validações realizadas

1. Ambiente (`sandbox` ou `production`) configurado.
2. `client_id` e `client_secret` presentes.
3. Em produção, emite `warning` se `mtls.cert_path` ou `mtls.key_path` estiverem vazios.
4. Requisição OAuth `client_credentials` real ao endpoint `/authorization`.
5. Retorna sucesso se a API responder com HTTP 200 ou 401 (401 prova que o endpoint está vivo, mas credenciais estão inválidas).

---

## `transfeera:debug`

Exibe diagnóstico completo do SDK sem tocar na API.

```bash
php artisan transfeera:debug
```

**O que exibe:**

- Ambiente e versão do pacote
- URLs base por domínio
- Configuração mascarada (credenciais ocultas)
- Lista de Resources disponíveis
- Status do token em cache

---

## `transfeera:cache-warm`

Pré-aquece o cache do token OAuth para evitar latência na primeira requisição.

```bash
php artisan transfeera:cache-warm
```

### Opções

| Opção | Descrição |
|-------|-----------|
| `--account-id` | Gera token com `scope=account_id:{accountId}` (Hub de Contas). |
| `--force` | Força renovação mesmo que haja token válido em cache. |

Exemplo multi-tenant:

```bash
php artisan transfeera:cache-warm --account-id=acc_123
```

---

## Saúde em CI/CD

Você pode combinar os comandos em pipelines:

```bash
php artisan transfeera:check --silent \
  && php artisan transfeera:cache-warm \
  && echo "Transfeera OK"
```

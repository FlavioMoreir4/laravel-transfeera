# Guia: Primeiro Pagamento com Laravel Transfeera

Este guia passo a passo mostra como realizar seu primeiro pagamento (transferência Pix) usando o SDK, do zero à confirmação.

## Pré-requisitos

- PHP 8.3+
- Laravel 12 ou 13
- Conta Transfeera (Sandbox ou Produção)
- Credenciais: Client ID, Client Secret
- Composer instalado

---

## 1. Instalação

```bash
composer require flaviomoreir4/laravel-transfeera
```

```bash
php artisan transfeera:install
```

O comando publica `config/transfeera.php` e valida o ambiente.

---

## 2. Configuração do `.env`

### Sandbox (Testes)

```env
TRANSFEERA_ENVIRONMENT=sandbox
TRANSFEERA_CLIENT_ID=seu_client_id_sandbox
TRANSFEERA_CLIENT_SECRET=seu_client_secret_sandbox
TRANSFEERA_USER_AGENT="MeuApp (dev@meuapp.com)"
```

### Produção

```env
TRANSFEERA_ENVIRONMENT=production
TRANSFEERA_CLIENT_ID=seu_client_id_prod
TRANSFEERA_CLIENT_SECRET=seu_client_secret_prod
TRANSFEERA_USER_AGENT="MeuApp (contato@meuapp.com)"

# mTLS OBRIGATÓRIO em produção para Pagamentos e Conta Certa
TRANSFEERA_MTLS_CERT_PATH=/etc/ssl/certs/transfeera_cert.pem
TRANSFEERA_MTLS_KEY_PATH=/etc/ssl/private/transfeera_key.pem
```

> **Obtenha credenciais Sandbox**: Acesse [Transfeera Developers](https://developers.transfeera.com) e crie uma aplicação.

---

## 3. Verificar Instalação

```bash
php artisan transfeera:install
```

Deve exibir:
```
✅ Configuração publicada
✅ Credenciais válidas
✅ Token obtido com sucesso
✅ Ambiente: sandbox
```

---

## 4. Primeiro Pagamento - Código Completo

### Usando DTOs (Recomendado v1.3+)

```php
<?php

namespace App\Http\Controllers;

use FlavioMoreir4\Transfeera\DTOs\BatchDTO;
use FlavioMoreir4\Transfeera\DTOs\TransferDTO;
use FlavioMoreir4\Transfeera\Facades\Transfeera;
use FlavioMoreir4\Transfeera\DTOs\Response\BatchResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\TransferResponseDTO;
use Illuminate\Http\JsonResponse;

class PagamentoController extends Controller
{
    public function primeiroPagamento(): JsonResponse
    {
        // 1. Criar lote de pagamento
        $batchDTO = new BatchDTO(
            name: 'Meu Primeiro Pagamento',
            type: 'immediate'  // ou 'scheduled' com scheduledDate
        );

        /** @var BatchResponseDTO $batch */
        $batch = Transfeera::batches()->create($batchDTO);

        // 2. Criar transferência dentro do lote
        $transferDTO = new TransferDTO(
            amount: 15000,           // R$ 150,00 em centavos
            pixKey: 'fornecedor@email.com',
            pixKeyType: 'email',     // cpf, cnpj, email, phone, evp
            description: 'Pagamento nota fiscal #1234'
        );

        /** @var TransferResponseDTO $transfer */
        $transfer = Transfeera::transfers()->create($batch->id, $transferDTO);

        // 3. Fechar lote (processar pagamentos)
        Transfeera::batches()->process($batch->id);

        return response()->json([
            'batch' => $batch->toArray(),
            'transfer' => $transfer->toArray(),
            'message' => 'Pagamento iniciado! Lote será processado.'
        ]);
    }
}
```

### Usando Arrays (Legado/Simples)

```php
$batch = Transfeera::batches()->create([
    'name' => 'Meu Primeiro Pagamento'
]);

$transfer = Transfeera::transfers()->create($batch['id'], [
    'amount' => 15000,
    'pix_key' => 'fornecedor@email.com',
    'pix_key_type' => 'email',
]);

Transfeera::batches()->process($batch['id']);
```

---

## 5. Verificar Status

```php
// Status do lote
$batch = Transfeera::batches()->get($batchId);
// $batch->status: pending, processing, processed, canceled

// Status da transferência
$transfer = Transfeera::transfers()->get($batchId, $transferId);
// $transfer->status: pending, processing, completed, failed, canceled
```

---

## 6. Fluxo Completo com Webhooks (Produção)

### 1. Configurar Webhook Secrets

```env
TRANSFEERA_WEBHOOK_SECRET_PAYMENTS=webhook_secret_pagamentos_forte
```

### 2. Registrar URL no Painel Transfeera

```
POST https://seuapp.com/webhooks/transfeera/payments
```

### 3. Listener para Eventos

```php
// App\Listeners\ProcessarPagamentoWebhook.php
use FlavioMoreir4\Transfeera\Events\TransfeeraWebhookReceived;

class ProcessarPagamentoWebhook
{
    public function handle(TransfeeraWebhookReceived $event): void
    {
        if ($event->domain !== 'payments') return;

        match ($event->type) {
            'batch.processed' => $this->loteProcessado($event->payload),
            'batch.failed' => $this->loteFalhou($event->payload),
            'transfer.completed' => $this->transferenciaConfirmada($event->payload),
            'transfer.failed' => $this->transferenciaFalhou($event->payload),
        };
    }
}
```

### 4. Registrar Listener

```php
// App\Providers\EventServiceProvider.php
protected $listen = [
    \FlavioMoreir4\Transfeera\Events\TransfeeraWebhookReceived::class => [
        \App\Listeners\ProcessarPagamentoWebhook::class,
    ],
];
```

---

## 7. Exemplo Completo: Service de Pagamento

```php
<?php

namespace App\Services;

use FlavioMoreir4\Transfeera\DTOs\BatchDTO;
use FlavioMoreir4\Transfeera\DTOs\TransferDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\BatchResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\TransferResponseDTO;
use FlavioMoreir4\Transfeera\Facades\Transfeera;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraException;

class PagamentoService
{
    /**
     * Realiza pagamento para fornecedor via Pix
     */
    public function pagarFornecedor(array $dados): array
    {
        try {
            // 1. Criar lote
            $batch = $this->criarLote($dados['lote_nome'] ?? 'Pagamento Fornecedor');

            // 2. Adicionar transferência
            $transfer = $this->adicionarTransferencia(
                batchId: $batch->id,
                valor: $dados['valor_centavos'],
                pixKey: $dados['pix_key'],
                pixKeyType: $dados['pix_key_type'] ?? 'email',
                descricao: $dados['descricao'] ?? ''
            );

            // 3. Processar lote
            Transfeera::batches()->process($batch->id);

            return [
                'success' => true,
                'batch_id' => $batch->id,
                'transfer_id' => $transfer->id,
                'message' => 'Pagamento enviado para processamento'
            ];

        } catch (TransfeeraException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getStatusCode()
            ];
        }
    }

    private function criarLote(string $nome): BatchResponseDTO
    {
        return Transfeera::batches()->create(new BatchDTO(
            name: $nome,
            type: 'immediate'
        ));
    }

    private function adicionarTransferencia(
        string $batchId,
        int $valor,
        string $pixKey,
        string $pixKeyType,
        string $descricao = ''
    ): TransferResponseDTO {
        return Transfeera::transfers()->create($batchId, new TransferDTO(
            amount: $valor,
            pixKey: $pixKey,
            pixKeyType: $pixKeyType,
            description: $descricao ?: null
        ));
    }
}
```

---

## 8. Uso no Controller

```php
<?php

namespace App\Http\Controllers;

use App\Services\PagamentoService;
use Illuminate\Http\Request;

class PagamentoController extends Controller
{
    public function __construct(
        private PagamentoService $pagamentoService
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'valor_centavos' => 'required|integer|min:1',
            'pix_key' => 'required|string',
            'pix_key_type' => 'required|in:cpf,cnpj,email,phone,evp',
            'descricao' => 'nullable|string|max:255',
            'lote_nome' => 'nullable|string|max:100',
        ]);

        $resultado = $this->pagamentoService->pagarFornecedor($validated);

        return response()->json($resultado, $resultado['success'] ? 201 : 422);
    }
}
```

---

## 9. Checklist de Produção

- [ ] Credenciais de produção no `.env`
- [ ] Certificados mTLS instalados (`TRANSFEERA_MTLS_CERT_PATH`, `TRANSFEERA_MTLS_KEY_PATH`)
- [ ] Webhook secrets configurados por domínio
- [ ] URLs de webhook registradas no painel Transfeera
- [ ] Listeners de webhook registrados no `EventServiceProvider`
- [ ] Handler de exceções global configurado (`app/Exceptions/Handler.php`)
- [ ] Logs de Transfeera configurados (`config/logging.php`)
- [ ] Testes em sandbox passando
- [ ] Monitoramento de erros 5xx e rate limit

---

## 10. Próximos Passos

- [Ver: Primeiro Recebimento](primeiro-recebimento.md) - Cobranças, QR Codes, Chaves Pix
- [Ver: Configuração de Webhooks](webhooks.md) - Segurança, validação, eventos
- [Ver: Tratamento de Erros](erros.md) - Exceções, retry, códigos de erro
- [Ver: Documentação API](https://docs.transfeera.dev/reference/endpoints.md) - Referência completa
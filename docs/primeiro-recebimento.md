# Guia: Primeiro Recebimento com Laravel Transfeera

Este guia mostra como receber pagamentos via Pix (chaves, QR Codes, cobranças) e boletos.

## Conceitos Fundamentais

| Tipo | Descrição | Uso |
|------|-----------|-----|
| **Chave Pix** | Identificador da conta (CPF, CNPJ, email, telefone, EVP) | Receber Pix direto |
| **QR Code Estático** | Mesmo código, valor definido pelo pagador | Doações, pagamentos variáveis |
| **QR Code Imediato** | Valor fixo, expira rápido (padrão 1h) | PDV, e-commerce |
| **QR Code com Vencimento** | Valor fixo, data de vencimento | Cobranças futuras, boletos híbridos |
| **Cobrança (Charge)** | Boleto + QR Code Pix unificados | Cobrança formal com vencimento |
| **Link de Pagamento** | Página hospedada pela Transfeera | Checkout simples sem integração complexa |

---

## 1. Chaves Pix - Configuração

### Criar Chave Pix

```php
use FlavioMoreir4\Transfeera\DTOs\PixKeyDTO;
use Transfeera;

$pixKeyDTO = new PixKeyDTO(
    type: 'email',           // cpf, cnpj, email, phone, evp
    value: 'financeiro@empresa.com'
);

$pixKey = Transfeera::pixKeys()->create($pixKeyDTO);
// Retorna PixKeyResponseDTO com id, type, value, status
```

### Verificar Chave (Código enviado por email/SMS)

```php
// Usuário recebe código e informa
Transfeera::pixKeys()->verify($pixKeyId, '123456');
```

### Portabilidade (Claim) - Reivindicar Chave de Outra Instituição

```php
// Solicitar portabilidade
$claim = Transfeera::pixKeys()->claim('11999999999'); // telefone

// Confirmar (após receber código no app do banco)
Transfeera::pixKeys()->confirmClaim($claim->id);

// Cancelar se desistir
Transfeera::pixKeys()->cancelClaim($claim->id);
```

---

## 2. QR Codes Pix

### QR Code Estático (Valor Definido pelo Pagador)

```php
use FlavioMoreir4\Transfeera\DTOs\PixQrCodeStaticDTO;

$dto = new PixQrCodeStaticDTO(
    key: 'financeiro@empresa.com',
    value: null,              // Pagador define valor
    description: 'Doação',
    additionalData: 'REF123'  // Opcional
);

$qrStatic = Transfeera::pixQrCodes()->createStatic($dto);
// Retorna: id, key, emv (copia e cola), image_url
```

### QR Code Imediato (Valor Fixo, Expiração Curta)

```php
use FlavioMoreir4\Transfeera\DTOs\PixQrCodeImmediateDTO;

$dto = new PixQrCodeImmediateDTO(
    key: 'financeiro@empresa.com',
    value: 25000,             // R$ 250,00 em centavos
    description: 'Produto X',
    additionalData: 'PEDIDO123'
);

$qrImmediate = Transfeera::pixQrCodes()->createImmediate($dto);
// Expira em ~1 hora (configurável na Transfeera)
```

### QR Code com Vencimento (Boleto Híbrido)

```php
use FlavioMoreir4\Transfeera\DTOs\PixQrCodeDueDTO;

$dto = new PixQrCodeDueDTO(
    key: 'financeiro@empresa.com',
    value: 15000,             // R$ 150,00
    dueDate: '2025-12-31',    // Data vencimento
    description: 'Fatura Janeiro',
    additionalData: 'FAT202501'
);

$qrDue = Transfeera::pixQrCodes()->createDue($dto);
// Funciona como boleto: vence na data, pode pagar via Pix ou código de barras
```

### Revogar QR Code

```php
Transfeera::pixQrCodes()->revoke($qrCodeId);
```

---

## 3. Cobranças (Charges) - Boleto + Pix Unificado

A cobrança cria **boleto + QR Code Pix** em uma única chamada. O cliente escolhe como pagar.

```php
use FlavioMoreir4\Transfeera\DTOs\ChargeDTO;

$chargeDTO = new ChargeDTO(
    payerName: 'João Silva',
    value: 15000,                 // R$ 150,00
    payerDocument: '12345678909', // CPF
    dueDate: '2025-12-31',        // Vencimento
    metadata: ['pedido_id' => 'PED123']
);

$charge = Transfeera::charges()->create($chargeDTO);
// Retorna ChargeResponseDTO com:
// - boleto: código de barras, linha digitável, pdf_url
// - pix: qr_code (emv, image_url), end2end_id
```

### Listar e Filtrar Cobranças

```php
$charges = Transfeera::charges()->list([
    'status' => 'pending',        // pending, completed, canceled, expired
    'start_date' => '2025-01-01',
    'end_date' => '2025-12-31',
    'page' => 1,
    'per_page' => 20
]);
```

### Cancelar Cobrança

```php
Transfeera::charges()->cancel($chargeId);
```

### Baixar Comprovante (PDF)

```php
// Precisa do receivable_id (retornado na cobrança)
$pdf = Transfeera::charges()->downloadPdf($chargeId, $receivableId);
// Retorna string binária do PDF ou URL
header('Content-Type: application/pdf');
echo $pdf;
```

---

## 4. Links de Pagamento (Checkout Hospedado)

### Criar Link

```php
use FlavioMoreir4\Transfeera\DTOs\PaymentLinkDTO;

$linkDTO = new PaymentLinkDTO(
    name: 'Produto Premium',
    value: 29900,                 // R$ 299,00
    description: 'Assinatura Anual',
    expiresIn: 30,                // Dias para expirar
    redirectUrl: 'https://meuapp.com/sucesso',
    metadata: ['plano' => 'premium']
);

$link = Transfeera::paymentLinks()->create($linkDTO);
// Retorna: id, url (ex: https://pay.transfeera.com/abc123), status
```

### Enviar para Cliente

```php
// Envie $link->url por email, WhatsApp, SMS
// Cliente acessa, escolhe Pix ou Boleto, paga
// Webhook notifica pagamento confirmado
```

---

## 5. Cash-in (Pix Recebidos) - Consulta

```php
// Listar Pix recebidos por período
$pixList = Transfeera::pixCashIn()->list([
    'start_date' => '2025-01-01',
    'end_date' => '2025-12-31',
    'status' => 'completed'       // completed, returned
]);

// Consultar por End2EndId (único do Pix)
$pix = Transfeera::pixCashIn()->getByEnd2EndId('E2E123456789');

// Solicitar devolução
$refund = Transfeera::pixCashIn()->requestRefund('E2E123', [
    'amount' => 5000  // Valor parcial em centavos
]);

// Consultar devoluções de um cash-in
$refunds = Transfeera::pixCashIn()->getRefunds('E2E123');
```

---

## 6. Exemplo Completo: E-commerce

```php
<?php

namespace App\Services;

use FlavioMoreir4\Transfeera\DTOs\ChargeDTO;
use FlavioMoreir4\Transfeera\DTOs\PaymentLinkDTO;
use FlavioMoreir4\Transfeera\Facades\Transfeera;
use FlavioMoreir4\Transfeera\DTOs\Response\ChargeResponseDTO;

class RecebimentoService
{
    /**
     * Cria cobrança para pedido (boleto + Pix)
     */
    public function criarCobrancaPedido(array $pedido): ChargeResponseDTO
    {
        $chargeDTO = new ChargeDTO(
            payerName: $pedido['cliente_nome'],
            value: $pedido['valor_total_centavos'],
            payerDocument: $pedido['cliente_cpf_cnpj'],
            dueDate: $pedido['vencimento'],           // Y-m-d
            metadata: [
                'pedido_id' => $pedido['id'],
                'itens' => $pedido['itens']
            ]
        );

        return Transfeera::charges()->create($chargeDTO);
    }

    /**
     * Cria link de pagamento para checkout
     */
    public function criarLinkCheckout(array $pedido): string
    {
        $link = Transfeera::paymentLinks()->create(new PaymentLinkDTO(
            name: "Pedido #{$pedido['id']}",
            value: $pedido['valor_total_centavos'],
            description: "Pedido e-commerce #{$pedido['id']}",
            expiresIn: 7,
            redirectUrl: route('pedido.sucesso', $pedido['id']),
            metadata: ['pedido_id' => $pedido['id']]
        ));

        return $link->url;  // Envie para cliente
    }

    /**
     * Gera QR Code Pix para pagamento imediato (PDV)
     */
    public function gerarQrCodePdv(float $valor, string $pedidoId): array
    {
        $qr = Transfeera::pixQrCodes()->createImmediate(
            new \FlavioMoreir4\Transfeera\DTOs\PixQrCodeImmediateDTO(
                key: config('transfeera.pix_key_recebimento'),
                value: (int)($valor * 100),
                description: "Pedido #{$pedidoId}",
                additionalData: $pedidoId
            )
        );

        return [
            'emv' => $qr->emv,           // Copia e cola
            'image_url' => $qr->imageUrl, // Imagem QR Code
            'id' => $qr->id
        ];
    }
}
```

---

## 7. Controller de Exemplo

```php
<?php

namespace App\Http\Controllers;

use App\Services\RecebimentoService;
use Illuminate\Http\Request;

class RecebimentoController extends Controller
{
    public function __construct(
        private RecebimentoService $recebimentoService
    ) {}

    // Cobrança tradicional (boleto + Pix)
    public function cobrar(Request $request)
    {
        $validated = $request->validate([
            'cliente_nome' => 'required|string|max:255',
            'cliente_cpf_cnpj' => 'required|string|max:18',
            'valor_total' => 'required|numeric|min:0.01',
            'vencimento' => 'required|date|after:today',
            'itens' => 'array',
        ]);

        $valorCentavos = (int)($validated['valor_total'] * 100);

        $charge = $this->recebimentoService->criarCobrancaPedido([
            'cliente_nome' => $validated['cliente_nome'],
            'cliente_cpf_cnpj' => $validated['cliente_cpf_cnpj'],
            'valor_total_centavos' => $valorCentavos,
            'vencimento' => $validated['vencimento'],
            'itens' => $validated['itens'] ?? [],
        ]);

        return response()->json([
            'cobranca' => $charge->toArray(),
            'boleto' => [
                'codigo_barras' => $charge->boletoCode ?? null,
                'linha_digitavel' => $charge->boletoDigitableLine ?? null,
                'pdf_url' => $charge->pdfUrl ?? null,
            ],
            'pix' => [
                'emv' => $charge->pixEmv ?? null,
                'qr_code_url' => $charge->pixImageUrl ?? null,
            ]
        ]);
    }

    // Link de pagamento
    public function linkPagamento(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'valor' => 'required|numeric|min:0.01',
            'descricao' => 'nullable|string',
            'dias_expiracao' => 'integer|min:1|max:365',
        ]);

        $url = $this->recebimentoService->criarLinkCheckout([
            'nome' => $validated['nome'],
            'valor' => (int)($validated['valor'] * 100),
            'descricao' => $validated['descricao'],
            'dias_expiracao' => $validated['dias_expiracao'] ?? 30,
        ]);

        return response()->json(['url' => $url]);
    }

    // QR Code PDV
    public function qrCodePdv(Request $request)
    {
        $validated = $request->validate([
            'valor' => 'required|numeric|min:0.01',
            'pedido_id' => 'required|string',
        ]);

        $qr = $this->recebimentoService->gerarQrCodePdv(
            $validated['valor'],
            $validated['pedido_id']
        );

        return response()->json($qr);
    }
}
```

---

## 8. Webhooks de Recebimento

### Eventos Principais

| Evento | Descrição |
|--------|-----------|
| `pix.received` | Pix recebido (cash-in) |
| `charge.completed` | Cobrança paga (boleto ou Pix) |
| `charge.expired` | Cobrança expirada |
| `charge.canceled` | Cobrança cancelada |
| `pix_key.claimed` | Chave reivindicada (portabilidade) |

### Listener Exemplo

```php
// App\Listeners\RecebimentoWebhookListener.php
use FlavioMoreir4\Transfeera\Events\TransfeeraWebhookReceived;

class RecebimentoWebhookListener
{
    public function handle(TransfeeraWebhookReceived $event): void
    {
        if ($event->domain !== 'receivables') return;

        match ($event->type) {
            'pix.received' => $this->processarPixRecebido($event->payload),
            'charge.completed' => $this->confirmarPagamento($event->payload),
            'charge.expired' => $this->cobrancaExpirou($event->payload),
        };
    }

    private function processarPixRecebido(array $payload): void
    {
        $end2endId = $payload['data']['end2end_id'] ?? null;
        $valor = $payload['data']['value'] ?? 0;
        
        // Associar a pedido, emitir nota fiscal, etc.
        \Log::info("Pix recebido: {$valor/100} - E2E: {$end2endId}");
    }

    private function confirmarPagamento(array $payload): void
    {
        $chargeId = $payload['data']['id'] ?? null;
        // Atualizar status do pedido para "pago"
    }
}
```

---

## 9. Próximos Passos

- [Configuração de Webhooks](webhooks.md) - Segurança, validação HMAC
- [Tratamento de Erros](erros.md) - Códigos, retry, exceções
- [Pix Automático](pix-automatico.md) - Autorizações, payment intents
- [Documentação API](https://docs.transfeera.dev/reference/endpoints.md) - Referência completa
# Laravel Transfeera

[![Latest Version](https://img.shields.io/packagist/v/flaviomoreir4/laravel-transfeera.svg)](https://packagist.org/packages/flaviomoreir4/laravel-transfeera)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://php.net)
[![License](https://img.shields.io/github/license/flaviomoreir4/laravel-transfeera)](LICENSE)

SDK Laravel para integração completa com a API **Transfeera** — Pagamentos, Recebimentos, Pix Automático, Conta Certa, Hub de Contas e MED/Infrações.

## Requisitos

- PHP 8.2+
- Laravel 11+
- Composer

## Instalação

```bash
composer require flaviomoreir4/laravel-transfeera
```

### Configuração rápida

```bash
php artisan transfeera:install
```

Isso publica o arquivo `config/transfeera.php` e valida o ambiente.

### Variáveis de ambiente (.env)

```env
TRANSFEERA_ENVIRONMENT=sandbox
TRANSFEERA_CLIENT_ID=seu_client_id
TRANSFEERA_CLIENT_SECRET=seu_client_secret
TRANSFEERA_USER_AGENT="MeuApp (email@exemplo.com)"

# Apenas em produção:
TRANSFEERA_MTLS_CERT_PATH=/caminho/para/certificado.pem
TRANSFEERA_MTLS_KEY_PATH=/caminho/para/chave.pem
```

## Uso

### Via Facade

```php
use Transfeera;

// Pagamentos — Lotes
$batch = Transfeera::batches()->create(['name' => 'Pagamentos Fornecedores']);
$batch = Transfeera::batches()->get('batch_123');
$batches = Transfeera::batches()->list(['page' => 1]);
$batch = Transfeera::batches()->update('batch_123', ['name' => 'Novo Nome']);
Transfeera::batches()->delete('batch_123');
Transfeera::batches()->process('batch_123'); // Fechar lote

// Pagamentos — Transferências
$transfer = Transfeera::transfers()->create('batch_123', [
    'amount' => 15000, // em centavos
    'pix_key' => 'fulano@email.com',
    'pix_key_type' => 'email',
]);
$transfers = Transfeera::transfers()->list('batch_123');

// Pagamentos — Boletos
$billet = Transfeera::billets()->create($data);
$billet = Transfeera::billets()->get('billet_456');

// Pagamentos — Consulta de bancos
$banks = Transfeera::banks()->list();

// Pagamentos — Saldo e extrato
$balance = Transfeera::statement()->getBalance();
$report = Transfeera::statement()->requestReport([
    'data_inicio' => '2025-01-01',
    'data_fim' => '2025-01-31',
]);

// Pagamentos — Pix
$pixData = Transfeera::pix()->lookupKey('fulano@email.com');
$parsed = Transfeera::pix()->parseEmv('00020126580014BR.GOV.BCB.PIX...');

// Recorrências
$recurrences = Transfeera::recurrences()->list();
Transfeera::recurrences()->cancel('rec_789');
```

### Via injeção de dependência

```php
use FlavioMoreir4\Transfeera\TransfeeraClient;

class PagamentoService
{
    public function __construct(
        private readonly TransfeeraClient $transfeera,
    ) {}

    public function pagar(array $data): array
    {
        $batch = $this->transfeera->batches()->create([
            'name' => 'Pagamento via Service',
        ]);

        return $this->transfeera->transfers()->create($batch['id'], $data);
    }
}
```

### Hub de Contas (múltiplas contas digitais)

```php
// Passando o accountId, o TokenManager adiciona scope=account_id:{id}
$batch = Transfeera::batches('conta_digital_123')->create([
    'name' => 'Lote da Conta 123',
]);
```

## Cobertura de endpoints

### Fase 1 ✅ — Núcleo + Pagamentos

| Recurso | Status |
|---------|--------|
| Service Provider & Config | ✅ |
| TokenManager (client_credentials + cache) | ✅ |
| Connector com mTLS | ✅ |
| Mapeamento de erros HTTP | ✅ |
| Bancos — listar | ✅ |
| Lotes — CRUD + processar | ✅ |
| Transferências — CRUD | ✅ |
| Boletos — CRUD + consulta CIP | ✅ |
| Saldo/Extrato — consultar, resgatar, relatório | ✅ |
| Pix — consulta DICT + parse EMV | ✅ |
| Recorrências — listar, listar pagamentos, cancelar | ✅ |
| Testes (Pest) | ✅ |
| PHPStan level 8 | ✅ |
| Rector | ✅ |

### Próximas fases

- **Fase 2** — Recebimentos (Chaves Pix, QR Codes, Cash-in, Cobranças, Links)
- **Fase 3** — Pix Automático + Webhooks
- **Fase 4** — Conta Certa + Hub de Contas + MED

## Testes

```bash
composer test
```

## Análise estática

```bash
composer phpstan
```

## Changelog

Veja [CHANGELOG.md](CHANGELOG.md) para o histórico de alterações.

## Licença

MIT — veja o arquivo [LICENSE](LICENSE) para detalhes.

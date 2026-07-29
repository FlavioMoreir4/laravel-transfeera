<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs;

/**
 * DTO para criação/atualização de boleto (em lote ou avulso).
 *
 * @see https://docs.transfeera.dev/reference/post_batch-id-billet.md
 */
readonly class BilletDTO
{
    /**
     * @param  string  $payerName        Nome do pagador
     * @param  int     $value            Valor em centavos
     * @param  string  $dueDate          Data de vencimento (Y-m-d)
     * @param  string  $document         CPF/CNPJ do pagador
     * @param  string  $documentType     Tipo do documento: cpf, cnpj
     * @param  string|null $email        Email do pagador
     * @param  string|null $phone        Telefone do pagador
     * @param  string|null $address      Endereço
     * @param  string|null $city         Cidade
     * @param  string|null $state        Estado (UF)
     * @param  string|null $zipCode      CEP
     * @param  string|null $neighborhood Bairro
     * @param  string|null $addressNumber Número
     * @param  string|null $addressComplement Complemento
     * @param  string|null $instructions Instruções do boleto
     * @param  string|null $description  Descrição
     * @param  array<string, mixed>|null $metadata Metadados
     */
    public function __construct(
        public string $payerName,
        public int $value,
        public string $dueDate,
        public string $document,
        public string $documentType,
        public ?string $email = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?string $state = null,
        public ?string $zipCode = null,
        public ?string $neighborhood = null,
        public ?string $addressNumber = null,
        public ?string $addressComplement = null,
        public ?string $instructions = null,
        public ?string $description = null,
        public ?array $metadata = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'payer_name' => $this->payerName,
            'value' => $this->value,
            'due_date' => $this->dueDate,
            'document' => $this->document,
            'document_type' => $this->documentType,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zipCode,
            'neighborhood' => $this->neighborhood,
            'address_number' => $this->addressNumber,
            'address_complement' => $this->addressComplement,
            'instructions' => $this->instructions,
            'description' => $this->description,
            'metadata' => $this->metadata,
        ], fn ($value) => $value !== null);
    }
}
<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Unit\DTOs;

use FlavioMoreir4\Transfeera\DTOs\BatchDTO;

test('converte batch dto para array', function () {
    $dto = new BatchDTO('Lote Teste', 'immediate', '2025-12-31');

    expect($dto->toArray())->toBe([
        'name' => 'Lote Teste',
        'type' => 'immediate',
        'scheduled_date' => '2025-12-31',
    ]);
});

test('omite campos nulos no batch dto', function () {
    $dto = new BatchDTO('Lote Simples');

    expect($dto->toArray())->toBe(['name' => 'Lote Simples']);
});

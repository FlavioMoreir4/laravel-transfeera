<?php

namespace FlavioMoreir4\Transfeera\Tests;

use FlavioMoreir4\Transfeera\Tests\Integration\IntegrationTestCase;

/*
|--------------------------------------------------------------------------
| Pest — Configuração
|--------------------------------------------------------------------------
*/

uses(TestCase::class)->in('Unit');
uses(TestCase::class)->in('Feature');
uses(IntegrationTestCase::class)->in('Integration');

/*
|--------------------------------------------------------------------------
| Helpers globais
|--------------------------------------------------------------------------
*/

/**
 * Caminho para os fixtures de teste.
 */
function fixturePath(string $name): string
{
    return __DIR__.'/Fixtures/'.$name;
}

/**
 * Carrega um fixture JSON como array.
 */
function fixtureJson(string $name): array
{
    $path = fixturePath($name);

    if (! file_exists($path)) {
        throw new \RuntimeException("Fixture not found: {$path}");
    }

    return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
}

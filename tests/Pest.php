<?php

namespace FlavioMoreir4\Transfeera\Tests;

/*
|--------------------------------------------------------------------------
| Pest — Configuração
|--------------------------------------------------------------------------
*/

uses(TestCase::class)->in(__DIR__);

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

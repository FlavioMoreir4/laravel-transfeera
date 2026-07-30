<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Concerns;

use FlavioMoreir4\Transfeera\DTOs\Response\ResponseDTOInterface;
use FlavioMoreir4\Transfeera\Http\Connector;

/**
 * Classe base para todos os Resources da API.
 *
 * Fornece métodos tipados para operações CRUD que retornam DTOs,
 * além de métodos "raw" para casos onde o DTO não existe.
 */
abstract class BaseResource
{
    public function __construct(
        protected readonly Connector $connector,
        protected readonly ?string $accountId = null,
    ) {}

    /**
     * Converte resposta da API para DTO tipado.
     *
     * @template T of ResponseDTOInterface
     *
     * @param  class-string<T>  $dtoClass
     * @param  array<string, mixed>  $data
     * @return T
     */
    protected function toDTO(string $dtoClass, array $data): ResponseDTOInterface
    {
        return $dtoClass::fromResponse($data);
    }

    /**
     * Converte lista de respostas da API para DTOs tipados.
     *
     * @template T of ResponseDTOInterface
     *
     * @param  class-string<T>  $dtoClass
     * @param  array<int, array<string, mixed>>  $dataList
     * @return array<int, T>
     */
    protected function toDTOList(string $dtoClass, array $dataList): array
    {
        return array_map(
            fn (array $data): ResponseDTOInterface => $dtoClass::fromResponse($data),
            $dataList
        );
    }

    /**
     * Extrai lista de dados da resposta da API.
     * A API pode retornar a lista diretamente ou envolta em 'data'.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function extractDataList(array $response): array
    {
        if (isset($response['data']) && is_array($response['data'])) {
            return $response['data'];
        }

        // Se a resposta é um array indexado (lista direta)
        if (isset($response[0]) && is_array($response[0])) {
            return $response;
        }

        // Retorna array vazio se não for lista
        return [];
    }

    /**
     * Faz requisição GET e retorna DTO único.
     *
     * @template T of ResponseDTOInterface
     *
     * @param  class-string<T>  $dtoClass
     * @return T
     */
    protected function getDTO(string $domain, string $path, array $params, string $dtoClass): ResponseDTOInterface
    {
        $response = $this->connector->get($domain, $path, $params, $this->accountId);

        return $this->toDTO($dtoClass, $response);
    }

    /**
     * Faz requisição GET e retorna lista de DTOs.
     *
     * @template T of ResponseDTOInterface
     *
     * @param  class-string<T>  $dtoClass
     * @return array<int, T>
     */
    protected function getDTOList(string $domain, string $path, array $params, string $dtoClass): array
    {
        $response = $this->connector->get($domain, $path, $params, $this->accountId);
        $dataList = $this->extractDataList($response);

        return $this->toDTOList($dtoClass, $dataList);
    }

    /**
     * Faz requisição POST e retorna DTO do recurso criado.
     *
     * @template T of ResponseDTOInterface
     *
     * @param  class-string<T>  $dtoClass
     * @return T
     */
    protected function postDTO(string $domain, string $path, array $data, string $dtoClass): ResponseDTOInterface
    {
        $response = $this->connector->post($domain, $path, $data, $this->accountId);

        return $this->toDTO($dtoClass, $response);
    }

    /**
     * Faz requisição PUT e retorna DTO do recurso atualizado.
     *
     * @template T of ResponseDTOInterface
     *
     * @param  class-string<T>  $dtoClass
     * @return T
     */
    protected function putDTO(string $domain, string $path, array $data, string $dtoClass): ResponseDTOInterface
    {
        $response = $this->connector->put($domain, $path, $data, $this->accountId);

        return $this->toDTO($dtoClass, $response);
    }

    /**
     * Faz requisição PATCH e retorna DTO do recurso atualizado.
     *
     * @template T of ResponseDTOInterface
     *
     * @param  class-string<T>  $dtoClass
     * @return T
     */
    protected function patchDTO(string $domain, string $path, array $data, string $dtoClass): ResponseDTOInterface
    {
        $response = $this->connector->patch($domain, $path, $data, $this->accountId);

        return $this->toDTO($dtoClass, $response);
    }

    /**
     * Faz requisição DELETE e retorna DTO da operação.
     *
     * @template T of ResponseDTOInterface
     *
     * @param  class-string<T>  $dtoClass
     * @return T
     */
    protected function deleteDTO(string $domain, string $path, string $dtoClass): ResponseDTOInterface
    {
        $response = $this->connector->delete($domain, $path, $this->accountId);

        return $this->toDTO($dtoClass, $response);
    }

    /**
     * Faz requisição DELETE.
     *
     * @return array<string, mixed> Resposta bruta (confirmação)
     *
     * @deprecated v2.0.0 será removido. Use deleteDTO() ou o método específico do Resource.
     * @see BaseResource::deleteRaw()
     */
    protected function deleteRaw(string $domain, string $path): array
    {
        return $this->connector->delete($domain, $path, $this->accountId);
    }
}

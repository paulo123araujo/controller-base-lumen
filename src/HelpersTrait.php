<?php

namespace ControllerBase;

use Illuminate\Http\JsonResponse;

/**
 * Trait com helpers
 */
trait HelpersTrait
{
    /**
     * A partir de uma resposa booleana de sucesso/falha retorna seu Json correspondente
     *
     * @param boolean $success
     * @return JsonResponse
     */
    private function returnSuccessOrFail(bool $success): JsonResponse
    {
        if (!$success) {
            return $this->apiResponse(500);
        }

        return $this->apiResponse(200);
    }

    /**
     * A partir da string de Model, retorna o nome do objeto
     *
     * @return string
     */
    private function getModelWithoutClass(): string
    {
        // Pego o nome do model, algo como "App\Models\Transaction" transformo em lower
        $model = strtolower($this->model);

        // Explodo-o pelas \
        $modelParts = explode("\\", $model);

        // e retorno o último elemento, ou seja, a classe sem namespace
        return end($modelParts);
    }
}

<?php

namespace ControllerBase;

use Illuminate\Http\JsonResponse;

trait APIResponseTrait
{
    /**
     * Trata uma resposta
     *
     * @param integer $code O código HTTP da resposta, também gera o status da resposta
     * @param array $data Dados adicionais
     * @param string $message A mensagem a ser enviada como message
     * @return \Illuminate\Http\JsonResponse
     */
    final protected function apiResponse(int $code, array $data = [], string $message = ""): JsonResponse
    {
        [$status, $message] = $this->prepareStatusAndMessage($code, $data, $message);

        $data = array_merge(compact(["status", "message"]), $data);

        $headers = [
            "Content-Type" => "application/json",
            "Charset" => "utf8"
        ];

        return response()->json($data, $code, $headers, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Retorna um array contendo o status e a mensagem tratados
     *
     * @param integer $code
     * @param array $data
     * @param string $message
     * @return array
     */
    private function prepareStatusAndMessage(int $code, array $data, string $message): array
    {
        $status = ($code >= 200 && $code <= 299) ? "Sucesso" : "Erro";

        $message = (!$message && isset($data["message"])) ? $data["message"] : $message;

        $message = $message ? $message : $this->getMessageByCode($code);

        return [$status, $message];
    }

    /**
     * A partir de um código HTTP, retorna sua representação textual
     * Fonte: https://developer.mozilla.org/pt-BR/docs/Web/HTTP/Status
     *
     * @param integer $code
     * @return string
     */
    private function getMessageByCode(int $code): string
    {
        switch ($code) {
            case 200:
                return 'OK';
            case 201:
                return 'Criado';
            case 202:
                return 'Aceito';
            case 204:
                return 'Sem conteúdo';
            case 400:
                return 'Requisição inválida';
            case 401:
                return 'Não autorizado';
            case 403:
                return 'Acesso negado';
            case 404:
                return 'Não encontrado';
            case 500:
                return 'Erro interno';
            case 504:
                return 'Tempo esgotado';
            default:
                return '';
        }
    }
}

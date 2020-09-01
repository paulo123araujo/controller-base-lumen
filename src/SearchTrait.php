<?php

namespace ControllerBase;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * Trait para a busca de objetos
 */
trait SearchTrait
{
    /**
     * Função para aplicar os filtros a uma query no index
     *
     * @param Builder $query
     * @return void
     */
    protected function applyFilters(Request $request, Builder $query): Builder
    {
        $filters = $this->extractFilters($request->all());
        foreach ($filters as $field => $value) {
            // Se o valor não tiver sido passado, vou para o próximo
            if (empty($value)) {
                continue;
            }

            [$field, $operator] = $this->prepareFieldAndOperator($field);

            // se o operator for inválido, já retorno
            if ($operator == '') {
                continue;
            }

            $this->applyFilterInQuery($query, $field, $operator, $value);
        }
        return $query;
    }

    /**
     * A partir do array de filters do Controller, retorna os que foram recebidos
     * em requestData
     *
     * @param array $requestData
     * @return array
     */
    private function extractFilters(array $requestData): array
    {
        // Retorno tanto as keys usadas como field, quanto os values direto
        $filters = array_map(function ($field, $rules) {
            return (is_string($field)) ? $field : $rules;
        }, array_keys($this->filters), $this->filters);

        return Arr::only($requestData, $filters);
    }

    /**
     * Prepara o field (extraindo-o de $this->filters) e o operator
     *
     * @param string $field
     * @return array [field, operator]
     */
    private function prepareFieldAndOperator(string $field)
    {
        // Se não existirem regras para o field, retorno o padrão
        if (!isset($this->filters[$field])) {
            return [$field, '='];
        }

        // Recupero dados das regras desse field
        $rules    = $this->filters[$field];
        $operator = (isset($rules['operator'])) ? $rules['operator'] : '=';
        $field    = (isset($rules['field']))    ? $rules['field']    : $field;

        // valido o operador
        $validOperators = ['=', '!=', '>', '>=', '<', '<='];
        if (!in_array($operator, $validOperators)) {
            return [$field, ''];
        }

        return [$field, $operator];
    }

    /**
     * Realmente aplica uma regra a Query
     *
     * @param Builder $query
     * @param string $field
     * @param string $operator
     * @param $value
     * @return void
     */
    private function applyFilterInQuery(Builder &$query, string $field, string $operator, $value)
    {
        if ($operator == '=' && is_array($value)) {
            $query->whereIn($field, $value);
            return;
        }

        if ($operator == '!=' && is_array($value)) {
            $query->whereNotIn($field, $value);
            return;
        }

        // É um array e o operator não é = nem !=, operator inválido
        if (is_array($value)) {
            return;
        }

        $query->where($field, $operator, $value);
    }
}

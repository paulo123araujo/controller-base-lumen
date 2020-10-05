<?php

namespace ControllerBase;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Laravel\Lumen\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use APIResponseTrait, HelpersTrait, SearchTrait;

    protected $model;

    /**
     * Regras para a validação na função create (POST)
     *
     * @var array
     */
    protected $createRules;

    /**
     * Regras para a validação na função update (PUT)
     *
     * @var array
     */
    protected $updateRules;

    /**
     * Filtros para a listagem de objetos
     *
     * @var array
     */
    protected $filters;

    /**
     * Define as regras de updateRules como as mesmas de createRules
     *
     * @param string $model
     * @param array $createRules
     * @param array $filters
     */
    public function __construct(string $model, array $createRules, array $filters)
    {
        $this->model = $model;
        $this->createRules = $createRules;
        $this->filters = $filters;

        $createWithoutRequired = [];
        foreach ($this->createRules as $field => $value) {
            $value = str_replace(['|required|'], '|', $value);
            $value = preg_replace('/required[^_]\|?|\|?required[^_]?$/', '', $value);
            $createWithoutRequired[$field] = $value;
        }

        $this->updateRules = array_merge($createWithoutRequired, $this->updateRules);
    }

    /**
     * Função executada antes de exibição de um objeto
     *
     * @param Model $data
     * @return void
     */
    protected function preShow(Model &$data)
    {
    }

    public function show(int $id): JsonResponse
    {
        $object = $this->model::find($id);

        if (!$object || $object->is_exception) {
            return $this->apiResponse(404);
        }

        $this->preShow($object);

        return $this->apiResponse(200, ["data" => $object]);
    }

    /**
     * Função executada antes de exibição de um array de objetos
     *
     * @param Model $data
     * @return void
     */
    public function preIndex(array &$data)
    {
    }

    /**
     * Retorna todos os objetos
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $pSize  = $request->input('size', config('database.pageSize'));
        $query  = $this->model::query();
        $query  = $this->applyFilters($request, $query);
        $result = $query->paginate($pSize)->toArray();

        $data   = $result['data'];
        $pagination = Arr::except($result, 'data');

        $this->preIndex($data);
        return $this->apiResponse(200, compact(['data', 'pagination']));
    }

    /**
     * Função executada antes da efetiva criação de um objeto
     * Pode contar validações e em caso de error, retornar um Illuminate\Http\JsonResponse
     *
     * @param array $data Os dados do objeto
     * @return void|JsonResponse
     */
    protected function preCreate(array &$data)
    {
    }

    /**
     * Função executada após a criação de um objeto
     *
     * @param Model $object
     * @return void
     */
    protected function posCreate(Model $object)
    {
    }

    /**
     * Cria um objeto
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        // Valido os dados
        $data = $request->all();
        $validator = Validator::make($data, $this->createRules);

        if ($validator->fails()) {
            $fails = $validator->errors()->all();
            return $this->apiResponse(400, compact('fails'), 'Parâmetros inválidos');
        }

        $preCreateReturns = $this->preCreate($data);
        if ($preCreateReturns) {
            return $preCreateReturns;
        }

        // Crio o objeto
        $created = $this->model::create($data);

        if (!$created) {
            return $this->apiResponse(500);
        }

        $this->posCreate($created);

        return $this->apiResponse(201, ['id' => $created->id]);
    }

    /**
     * Função executada antes da efetiva alteração de um objeto
     * Pode contar validações e em caso de error, retornar um Illuminate\Http\JsonResponse
     *
     * @param Model $object
     * @return void|JsonResponse
     */
    protected function preUpdate(Model &$object)
    {
    }

    /**
     * Função executada após a alteração de um objeto
     *
     * @param Model $object
     * @return void
     */
    protected function posUpdate(Model $object)
    {
    }

    /**
     * Atualiza um objeto
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id, string $date = ''): JsonResponse
    {
        // Busca o objeto
        $object = $this->model::find($id);

        // Se o objeto não for encontrado
        if (!$object) {
            return $this->apiResponse(404);
        }

        // Valido os dados
        $data = $request->all();
        $validator = Validator::make($data, $this->updateRules);

        if ($validator->fails()) {
            $fails = $validator->errors()->all();
            return $this->apiResponse(400, compact('fails'), 'Parâmetros inválidos');
        }

        $preUpdateReturns = $this->preUpdate($object);
        if ($preUpdateReturns) {
            return $preUpdateReturns;
        }

        $updated = $object->update($data);

        if (!$updated) {
            return $this->apiResponse(500);
        }

        $this->posUpdate($object);
        return $this->apiResponse(200);
    }

    /**
     * Função executada antes da efetiva deleção de um objeto
     * Pode contar validações e em caso de error, retornar um Illuminate\Http\JsonResponse
     *
     * @param Model $object
     * @return void|JsonResponse
     */
    protected function preDelete(Model $object)
    {
    }

    /**
     * Função executada após a alteração de um objeto
     *
     * @param Model $object
     * @return void
     */
    protected function posDelete(Model $object)
    {
    }

    /**
     * Deleta um objeto
     *
     * @param int $id
     * @return Illuminate\Http\JsonResponse
     */
    public function delete(int $id, string $date = '')
    {
        $object = $this->model::find($id);

        // Se o objeto não for encontrada
        if (!$object) {
            return $this->apiResponse(404);
        }

        $preDeleteReturns = $this->preDelete($object);
        if ($preDeleteReturns) {
            return $preDeleteReturns;
        }

        $deleted = $object->delete();

        if (!$deleted) {
            return $this->apiResponse(500, [], 'Problemas ao deletar o objeto');
        }

        $this->posDelete($object);
        return $this->apiResponse(200);
    }

    /**
     * Verifica a existencia de um objeto
     *
     * @param integer $id
     * @return JsonResponse
     */
    public function exists(int $id): JsonResponse
    {
        $exists = (bool) $this->model::where('id', $id)->exists();
        return $this->apiResponse(200, compact('exists'));
    }
}

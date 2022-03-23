<?php

namespace Modules\Menu\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Menu\Http\Requests\CreateMenuRequest;
use Modules\Menu\Http\Requests\UpdateMenuRequest;

// Base Api
use Modules\Core\Http\Controllers\Api\BaseApiController;

use Modules\Menu\Repositories\MenuRepository;

use Modules\Menu\Transformers\MenuTransformer;


class MenuApiController extends BaseApiController
{
  private $menu;

  public function __construct(MenuRepository $menu)
  {
    $this->menu = $menu;
  }

  /**
  * GET ITEMS
  *
  * @return mixed
  */
  public function index(Request $request)
  {

    try {

      $params = $this->getParamsRequest($request);

      $menus = $this->menu->getItemsBy($params);

      $response = ['data' => MenuTransformer::collection($menus)];

      $params->page ? $response["meta"] = ["page" => $this->pageTransformer($menus)] : false;

    } catch (\Exception $e) {
      \Log::error($e);
      $status = $this->getStatusError($e->getCode());
      $response = ["errors" => $e->getMessage()];

    }
    //Return response
    return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
  }

    /**
     * GET A ITEM
     *
     * @param string $criteria
     * @param Request $request
     * @return JsonResponse
     */
  public function show(string $criteria, Request $request): JsonResponse
  {
    try {

      $params = $this->getParamsRequest($request);

      $menu = $this->menu->getItem($criteria, $params);

      if (!$menu) throw new \Exception('Item not found', 204);

      $response = ["data" => new MenuTransformer($menu)];

      $params->page ? $response["meta"] = ["page" => $this->pageTransformer($menu)] : false;

    } catch (\Exception $e) {

      $status = $this->getStatusError($e->getCode());
      $response = ["errors" => $e->getMessage()];

    }

    return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
  }

  /**
   * CREATE A ITEM
   *
   * @param Request $request
   * @return JsonResponse
   */
  public function create(Request $request): JsonResponse
  {
    \DB::beginTransaction();
    try {
      $data = $request->input('attributes') ?? [];//Get data

      $this->validateRequestApi(new CreateMenuRequest($data));

      $menu = $this->menu->create($data);

      $response = ["data" => new MenuTransformer($menu)];

      \DB::commit();

    } catch (\Exception $e) {

      \DB::rollback();
      $status = $this->getStatusError($e->getCode());
      $response = ["errors" => $e->getMessage()];

    }

    return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
  }

  /**
   * UPDATE ITEM
   *
   * @param $criteria
   * @param Request $request
   * @return JsonResponse
   */
  public function update($criteria, Request $request): JsonResponse
  {
    \DB::beginTransaction();
    try {

      $data = $request->input('attributes') ?? [];//Get data

      $params = $this->getParamsRequest($request);

      $this->menu->updateBy($criteria, $data, $params);

      $response = ["data" => 'Item Updated'];
      \DB::commit();

    } catch (\Exception $e) {
      \DB::rollback();
      $status = $this->getStatusError($e->getCode());
      $response = ["errors" => $e->getMessage()];

    }

    return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
  }

  /**
   * DELETE A ITEM
   *
   * @param $criteria
   * @return JsonResponse
   */
  public function delete($criteria, Request $request): JsonResponse
  {
    \DB::beginTransaction();
    try {

      $params = $this->getParamsRequest($request);

      $this->menu->deleteBy($criteria, $params);

      $response = ["data" => "Item deleted"];

      \DB::commit();

    } catch (\Exception $e) {

      \DB::rollback();
      $status = $this->getStatusError($e->getCode());
      $response = ["errors" => $e->getMessage()];

    }

    return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
  }

}

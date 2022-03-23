<?php

namespace Modules\Menu\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Menu\Http\Requests\CreateMenuItemRequest;
use Modules\Menu\Repositories\MenuItemRepository;
use Modules\Menu\Entities\Menu;
use Modules\Menu\Transformers\MenuitemTransformer;
use Modules\Core\Http\Controllers\Api\BaseApiController;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Modules\Menu\Services\MenuItemUriGenerator;

class MenuItemApiController extends BaseApiController
{
    private MenuItemRepository $menuitem;
    private Menu $menu;
    private MenuItemUriGenerator $menuItemUriGenerator;

    public function __construct(MenuItemRepository $menuitem, Menu $menu, MenuItemUriGenerator $menuItemUriGenerator)
    {
        $this->menuitem = $menuitem;
        $this->menu = $menu;
        $this->menuItemUriGenerator = $menuItemUriGenerator;
    }

    /**
     * GET ITEMS
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $params = $this->getParamsRequest($request);

            $menuitems = $this->menuitem->getItemsBy($params);

            $response = ['data' => MenuitemTransformer::collection($menuitems)];

            $params->page ? $response["meta"] = ["page" => $this->pageTransformer($menuitems)] : false;

        } catch (\Exception $e) {
            \Log::error($e);
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];

        }

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

            $menuitem = $this->menuitem->getItem($criteria, $params);

            if (!$menuitem) throw new \Exception('Item not found', 204);

            $response = ["data" => new MenuitemTransformer($menuitem)];

            $params->page ? $response["meta"] = ["page" => $this->pageTransformer($menuitem)] : false;

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
            $data = $request->input('attributes') ?? [];

            $this->validateRequestApi(new CreateMenuItemRequest($data));

            $menuItem = $this->menuitem->create($data);

            $response = ["data" => new MenuitemTransformer($menuItem)];
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
     * @param string $criteria
     * @param Request $request
     * @return JsonResponse
     */
    public function update(string $criteria, Request $request): JsonResponse
    {
        \DB::beginTransaction();

        try {

            $data = $request->input('attributes') ?? [];

            $params = $this->getParamsRequest($request);

            $menu = $this->menu->find($data['menu_id']);

            $languages = LaravelLocalization::getSupportedLanguagesKeys();

            if (!$menu) throw new \Exception('Item not found', 204);

            foreach ($languages as $lang) {
                if ($data['link_type'] === 'page' && !empty($data['page_id'])) {
                    $data[$lang]['uri'] = $this->menuItemUriGenerator->generateUri($data['page_id'], $data['parent_id'], $lang);
                }
            }

            if (!isset($data['parent_id'])) $data['parent_id'] = $this->menuitem->getRootForMenu($menu->id)->id;

            $menuitem = $this->menuitem->getItem($criteria, $params);

            if (!$menuitem) throw new \Exception('Item not found', 204);

            $this->menuitem->update($menuitem, $data);

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
     * @param string $criteria
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(string $criteria, Request $request): JsonResponse
    {
        \DB::beginTransaction();
        try {

            $params = $this->getParamsRequest($request);

            $menuitem = $this->menuitem->getItem($criteria, $params);

            if (!$menuitem) throw new \Exception('Item not found', 204);

            $this->menuitem->destroy($menuitem);

            $response = ["data" => "Item deleted"];

            \DB::commit();

        } catch (\Exception $e) {

            \DB::rollback();
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];

        }

        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }

    public function updateItems(Request $request): JsonResponse
    {
        try {

            $params = $this->getParamsRequest($request);

            $data = $request->input('attributes') ?? [];


            $dataEntity = $this->menuitem->getItemsBy($params);

            $crterians = $dataEntity->pluck('id');

            $dataEntity = $this->menuitem->updateItems($crterians, $data);

            $response = ["data" => MenuitemTransformer::collection($dataEntity)];

            $params->page ? $response["meta"] = ["page" => $this->pageTransformer($dataEntity)] : false;

        } catch (\Exception $e) {

            \Log::error($e->getMessage());
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];

        }

        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }

    public function deleteItems(Request $request): JsonResponse
    {
        try {

            $params = $this->getParamsRequest($request);

            $dataEntity = $this->menuitem->getItemsBy($params);

            $crterians = $dataEntity->pluck('id');

            $this->menuitem->deleteItems($crterians);

            $response = ["data" => "Items deleted"];

        } catch (\Exception $e) {

            \Log::error($e->getMessage());
            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];

        }

        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }

    public function updateOrderner(Request $request): JsonResponse
    {
        \DB::beginTransaction();
        try {
            $params = $this->getParamsRequest($request);

            $data = $request->input('attributes');

            $newData = $this->menuitem->updateOrders($data);

            $response = ['data' => 'updated items'];

            \DB::commit();

        } catch (\Exception $e) {

            \DB::rollback();

            $status = $this->getStatusError($e->getCode());

            $response = ["errors" => $e->getMessage()];
        }

        return response()->json($response, $status ?? 200);
    }

}

<?php

namespace Modules\Menu\Http\Controllers\Api;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Modules\Menu\Entities\Menuitem;
use Modules\Menu\Http\Requests\CreateMenuItemRequest;
use Modules\Menu\Http\Requests\UpdateMenuItemRequest;
use Modules\Menu\Repositories\MenuItemRepository;
use Modules\Menu\Services\MenuOrdener;
use Modules\Menu\Transformers\MenuitemTransformer;

class MenuItemController extends Controller
{
    /**
     * @var Repository
     */
    private Repository $cache;
    /**
     * @var MenuOrdener
     */
    private MenuOrdener $menuOrdener;
    /**
     * @var MenuItemRepository
     */
    private MenuItemRepository $menuItem;

    public function __construct(MenuOrdener $menuOrdener, Repository $cache, MenuItemRepository $menuItem)
    {
        $this->cache = $cache;
        $this->menuOrdener = $menuOrdener;
        $this->menuItem = $menuItem;
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
    public function show(Menuitem $menuitem, Request $request): JsonResponse
    {
        try {

            $response = ["data" => new MenuitemTransformer($menuitem)];

        } catch (\Exception $e) {

            $status = $this->getStatusError($e->getCode());
            $response = ["errors" => $e->getMessage()];
        }

        return response()->json($response ?? ["data" => "Request successful"], $status ?? 200);
    }

    /**
     * CREATE A ITEM
     *
     * @param CreateMenuItemRequest $request
     * @return JsonResponse
     */
    public function create(CreateMenuItemRequest $request): JsonResponse
    {
        \DB::beginTransaction();
        try {
            $data = $request->all();

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
    public function updateItem(Menuitem $menuitem, UpdateMenuItemRequest $request): JsonResponse
    {
        \DB::beginTransaction();

        try {

            $data = $request->all();

            $this->menuitem->update($menuitem, $data);

            $response = ["data" => trans('menu::messages.menuitem updated')];

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
    public function deleteItem(Menuitem $menuitem): JsonResponse
    {
        \DB::beginTransaction();
        try {
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

    /**
     * Update all menu items
     * @param Request $request
     */
    public function update(Request $request)
    {
        $this->cache->tags('menuItems')->flush();

        $this->menuOrdener->handle($request->get('menu'));
    }

    /**
     * Delete a menu item
     * @param Request $request
     * @return mixed
     */
    public function delete(Request $request)
    {
        $menuItem = $this->menuItem->find($request->get('menuitem'));

        if (! $menuItem) {
            return Response::json(['errors' => true]);
        }

        $this->menuItem->destroy($menuItem);

        return Response::json(['errors' => false]);
    }
}

<?php

namespace Modules\Menu\Repositories\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Modules\Core\Repositories\Eloquent\EloquentBaseRepository;
use Modules\Menu\Events\MenuItemIsCreating;
use Modules\Menu\Events\MenuItemIsUpdating;
use Modules\Menu\Events\MenuItemWasCreated;
use Modules\Menu\Events\MenuItemWasUpdated;
use Modules\Menu\Repositories\MenuItemRepository;

class EloquentMenuItemRepository extends EloquentBaseRepository implements MenuItemRepository
{
    /**
     * @param $data
     * @return array|Builder|Collection|Model|null
     */
    public function create($data): array|Builder|Collection|Model|null
    {
        event($event = new MenuItemIsCreating($data));
        $menuItem = $this->model->create($event->getAttributes());

        event(new MenuItemWasCreated($menuItem));

        return $menuItem;
    }

    /**
     * @param $menuItem
     * @param $data
     * @return array|Builder|Collection|Model|null
     */
    public function update($menuItem, $data): array|Builder|Collection|Model|null
    {
        event($event = new MenuItemIsUpdating($menuItem, $data));
        $menuItem->update($event->getAttributes());

        event(new MenuItemWasUpdated($menuItem));

        return $menuItem;
    }

    /**
     * Get online root elements
     *
     * @param int $menuId
     * @return Collection
     */
    public function rootsForMenu(int $menuId): Collection
    {
        return $this->model->whereHas('translations', function (Builder $q) {
            $q->where('status', 1);
            $q->where('locale', App::getLocale());
        })->with('translations')->whereMenuId($menuId)->orderBy('position')->get();
    }

    /**
     * Get all root elements
     *
     * @param int $menuId
     * @return Collection
     */
    public function allRootsForMenu(int $menuId): Collection
    {
        return $this->model->with('translations')->whereMenuId($menuId)->orderBy('parent_id')->orderBy('position')->get();
    }

    /**
     * Get Items to build routes
     *
     * @return array
     */
    public function getForRoutes(): array
    {
        $menuitems = DB::table('menu__menus')
            ->select(
                'primary',
                'menu__menuitems.id',
                'menu__menuitems.parent_id',
                'menu__menuitems.module_name',
                'menu__menuitem_translations.uri',
                'menu__menuitem_translations.locale'
            )
            ->join('menu__menuitems', 'menu__menus.id', '=', 'menu__menuitems.menu_id')
            ->join('menu__menuitem_translations', 'menu__menuitems.id', '=', 'menu__menuitem_translations.menuitem_id')
            ->where('uri', '!=', '')
            ->where('module_name', '!=', '')
            ->where('status', '=', 1)
            ->where('primary', '=', 1)
            ->orderBy('module_name')
            ->get();

        $menuitemsArray = [];
        foreach ($menuitems as $menuitem) {
            $menuitemsArray[$menuitem->module_name][$menuitem->locale] = $menuitem->uri;
        }

        return $menuitemsArray;
    }

    /**
     * Get the root menu item for the given menu id
     *
     * @param int $menuId
     * @return array|Builder|Collection|Model|null
     */
    public function getRootForMenu(int $menuId): array|Builder|Collection|Model|null
    {
        return $this->model->with('translations')->where(['menu_id' => $menuId, 'is_root' => true])->firstOrFail();
    }

    /**
     * Return a complete tree for the given menu id
     *
     * @param int $menuId
     * @return object
     */
    public function getTreeForMenu(int $menuId): object
    {
        $items = $this->rootsForMenu($menuId);

        return $items->noCleaning()->nest();
    }

    /**
     * @param string $uri
     * @param string $locale
     * @return array|Builder|Collection|Model|null
     */
    public function findByUriInLanguage(string $uri, string $locale): array|Builder|Collection|Model|null
    {
        return $this->model->whereHas('translations', function (Builder $q) use ($locale, $uri) {
            $q->where('status', 1);
            $q->where('locale', $locale);
            $q->where('uri', $uri);
        })->with('translations')->first();
    }


    /**
     * Get resources by an array of attributes
     * @param object $params
     * @return LengthAwarePaginator|Collection
     */
    public function getItemsBy($params = false): LengthAwarePaginator|Collection
    {
        /*== initialize query ==*/
        $query = $this->model->query();

        /*== RELATIONSHIPS ==*/
        if (in_array('*', $params->include)) {//If Request all relationships
            $query->with([]);
        } else {//Especific relationships
            $includeDefault = [];//Default relationships
            if (isset($params->include))//merge relations with default relationships
                $includeDefault = array_merge($includeDefault, $params->include);
            $query->with($includeDefault);//Add Relationships to query
        }

        /*== FILTERS ==*/
        if (isset($params->filter)) {
            $filter = $params->filter;//Short filter

            //Filter by date
            if (isset($filter->date)) {
                $date = $filter->date;//Short filter date
                $date->field = $date->field ?? 'created_at';
                if (isset($date->from))//From a date
                    $query->whereDate($date->field, '>=', $date->from);
                if (isset($date->to))//to a date
                    $query->whereDate($date->field, '<=', $date->to);
            }

            //Order by
            if (isset($filter->order)) {
                $orderByField = $filter->order->field ?? 'position';//Default field
                $orderWay = $filter->order->way ?? 'desc';//Default way
                $query->orderBy($orderByField, $orderWay);//Add order to query
            }

            // Filter By Menu
            if (isset($filter->menu)) {
                $query->where('menu_id', $filter->menu);
            }

            //add filter by search
            if (isset($filter->search)) {
                //find search in columns
                $query->where(function ($query) use ($filter) {
                    $query->whereHas('translations', function ($query) use ($filter) {
                        $query->where('locale', $filter->locale)
                            ->where('title', 'like', '%' . $filter->search . '%');
                    })->orWhere('id', 'like', '%' . $filter->search . '%')
                        ->orWhere('updated_at', 'like', '%' . $filter->search . '%')
                        ->orWhere('created_at', 'like', '%' . $filter->search . '%');
                });
            }

        }

        /*== FIELDS ==*/
        if (isset($params->fields) && count($params->fields))
            $query->select($params->fields);

        /*== REQUEST ==*/
        if (isset($params->page) && $params->page) {
            return $query->paginate($params->take);
        } else {
            $params->take ? $query->take($params->take) : false;//Take
            return $query->get();
        }
    }

    /**
     * Find a resource by id or slug
     * @param string $criteria
     * @param object $params
     * @return Model|Collection|Builder|array|null
     */
    public function getItem(string $criteria, $params = false): Model|Collection|Builder|array|null
    {
        //Initialize query
        $query = $this->model->query();

        /*== RELATIONSHIPS ==*/
        if (in_array('*', $params->include)) {//If Request all relationships
            $query->with([]);
        } else {//Especific relationships
            $includeDefault = [];//Default relationships
            if (isset($params->include))//merge relations with default relationships
                $includeDefault = array_merge($includeDefault, $params->include);
            $query->with($includeDefault);//Add Relationships to query
        }

        /*== FILTER ==*/
        if (isset($params->filter)) {
            $filter = $params->filter;

            if (isset($filter->field))//Filter by specific field
                $query->where($filter->field, $criteria);
            else//Filter by ID
                $query->where('id', $criteria);
        }

        /*== FIELDS ==*/
        if (isset($params->fields) && count($params->fields))
            $query->select($params->fields);

        /*== REQUEST ==*/
        return $query->first();
    }

    /**
     * @param $data
     * @return array
     */
    public function updateOrders($data): array
    {
        $menuitems = [];
        foreach ($data['menuitems'] as $menuitem) {
            $menuitems[] = $this->model->find($menuitem['id'])
                ->update([
                    'position' => $menuitem['position'],
                    'parent_id' => $menuitem['parent_id']
                ]);
        }
        return $menuitems;
    }
}

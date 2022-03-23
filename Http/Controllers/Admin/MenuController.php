<?php

namespace Modules\Menu\Http\Controllers\Admin;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Modules\Core\Http\Controllers\Admin\AdminBaseController;
use Modules\Menu\Entities\Menu;
use Modules\Menu\Http\Requests\CreateMenuRequest;
use Modules\Menu\Http\Requests\UpdateMenuRequest;
use Modules\Menu\Repositories\MenuItemRepository;
use Modules\Menu\Repositories\MenuRepository;
use Modules\Menu\Services\MenuRenderer;

class MenuController extends AdminBaseController
{
    /**
     * @var MenuRepository
     */
    private MenuRepository $menu;
    /**
     * @var MenuItemRepository
     */
    private MenuItemRepository $menuItem;
    /**
     * @var MenuRenderer
     */
    private MenuRenderer $menuRenderer;

    public function __construct(
        MenuRepository $menu,
        MenuItemRepository $menuItem,
        MenuRenderer $menuRenderer
    ) {
        parent::__construct();
        $this->menu = $menu;
        $this->menuItem = $menuItem;
        $this->menuRenderer = $menuRenderer;
    }

    public function index(): View|Factory|Application
    {
        $menus = $this->menu->all();

        return view('menu::admin.menus.index', compact('menus'));
    }

    public function create(): View|Factory|Application
    {
        return view('menu::admin.menus.create');
    }

    public function store(CreateMenuRequest $request)
    {
        $this->menu->create($request->all());

        return redirect()->route('admin.menu.menu.index')
            ->withSuccess(trans('menu::messages.menu created'));
    }

    public function edit(Menu $menu): View|Factory|Application
    {
        $menuItems = $this->menuItem->allRootsForMenu($menu->id);

        $menuStructure = $this->menuRenderer->renderForMenu($menu->id, $menuItems->nest());

        return view('menu::admin.menus.edit', compact('menu', 'menuStructure'));
    }

    public function update(Menu $menu, UpdateMenuRequest $request)
    {
        $this->menu->update($menu, $request->all());

        return redirect()->route('admin.menu.menu.index')
            ->withSuccess(trans('menu::messages.menu updated'));
    }

    public function destroy(Menu $menu)
    {
        $this->menu->destroy($menu);

        return redirect()->route('admin.menu.menu.index')
            ->withSuccess(trans('menu::messages.menu deleted'));
    }
}

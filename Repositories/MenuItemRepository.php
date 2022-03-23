<?php

namespace Modules\Menu\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Repositories\BaseRepository;

interface MenuItemRepository extends BaseRepository
{
    /**
     * Get online root elements
     *
     * @param int $menuId
     * @return Collection
     */
    public function rootsForMenu(int $menuId): Collection;

    /**
     * Get all root elements
     *
     * @param int $menuId
     * @return Collection
     */
    public function allRootsForMenu(int $menuId): Collection;

    /**
     * Get the menu items ready for routes
     * @return mixed
     */
    public function getForRoutes(): mixed;

    /**
     * Get the root menu item for the given menu id
     * @param int $menuId
     * @return array|Builder|Collection|Model|null
     */
    public function getRootForMenu(int $menuId): array|Builder|Collection|Model|null;

    /**
     * Return a complete tree for the given menu id
     *
     * @param int $menuId
     * @return object
     */
    public function getTreeForMenu(int $menuId): object;

    /**
     * @param string $uri
     * @param string $locale
     * @return array|Builder|Collection|Model|null
     */
    public function findByUriInLanguage(string $uri, string $locale): array|Builder|Collection|Model|null;
}

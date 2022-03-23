<?php

namespace Modules\Menu\Blade;

use Illuminate\Support\Arr;

final class MenuDirective
{
    /**
     * @var
     */
    private $name;
    /**
     * @var
     */
    private $presenter;
    /**
     * @var
     */
    private $bindings;

    /**
     * @param $arguments
     * @return string|null
     */
    public function show($arguments): ?string
    {
        $this->extractArguments($arguments);

        return $this->returnMenu();
    }

    /**
     * Extract the possible arguments as class properties
     * @param array $arguments
     */
    private function extractArguments(array $arguments)
    {
        $this->name = Arr::get($arguments, 0);
        $this->presenter = Arr::get($arguments, 1);
        $this->bindings = Arr::get($arguments, 2, []);
    }

    /**
     * Prepare arguments and return menu
     * @return string|null
     */
    private function returnMenu(): ?string
    {
        $customPresenter = config('encore.menu.config.default_menu_presenter');
        if ($this->presenter === null && $customPresenter !== null) {
            $this->presenter = $customPresenter;
        }

        return app('menus')->get($this->name, $this->presenter, $this->bindings);
    }

    /**
     * @return string|null
     */
    public function __toString()
    {
        return $this->returnMenu();
    }
}

<?php

namespace Modules\Menu\Blade\Facades;

use Illuminate\Support\Facades\Facade;

final class MenuDirective extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'menu.menu.directive';
    }
}

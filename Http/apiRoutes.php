<?php

use Illuminate\Routing\Router;

/** @var Router $router */
$router->group(['prefix' => '/menuitem', 'middleware' => 'api.token'], function (Router $router) {
    $router->get('/', [
        'as' => 'api.menu.menuitem.index',
        'uses' => 'MenuItemController@index',
        'middleware' => 'token-can:menu.menuitems.index',
    ]);
    $router->get('/{menuitem}', [
        'as' => 'api.menu.menuitem.show',
        'uses' => 'MenuItemController@show',
        'middleware' => 'token-can:menu.menuitems.index',
    ]);
    $router->post('/', [
        'as' => 'api.menu.menuitem.create',
        'uses' => 'MenuItemController@create',
        'middleware' => 'token-can:menu.menuitems.create',
    ]);
    $router->put('{menuitem}', [
        'as' => 'api.menuitem.updateItem',
        'uses' => 'MenuItemController@updateItem',
        'middleware' => 'token-can:menu.menuitems.edit',
    ]);
    $router->post('{menuitem}', [
        'as' => 'api.menuitem.deleteItem',
        'uses' => 'MenuItemController@deleteItem',
        'middleware' => 'token-can:menu.menuitems.destroy',
    ]);
    $router->post('/update', [
        'as' => 'api.menuitem.update',
        'uses' => 'MenuItemController@update',
        'middleware' => 'token-can:menu.menuitems.edit',
    ]);
    $router->post('/delete', [
        'as' => 'api.menuitem.delete',
        'uses' => 'MenuItemController@delete',
        'middleware' => 'token-can:menu.menuitems.destroy',
    ]);
});
$router->group(['prefix' => 'menu', 'middleware' => 'api.token'], function (Router $router) {
    $router->get('/', [
        'as' => 'api.menu.menu.index',
        'uses' => 'MenuApiController@index',
        'middleware' => 'can:menu.menus.index',
    ]);
    $router->get('/{menu}', [
        'as' => 'api.menu.menu.show',
        'uses' => 'MenuApiController@show',
        'middleware' => 'can:menu.menus.index',
    ]);
    $router->post('/', [
        'as' => 'api.menu.menu.create',
        'uses' => 'MenuApiController@create',
        'middleware' => 'can:menu.menus.create',
    ]);
    $router->put('/{menu}', [
        'as' => 'api.menu.menu.update',
        'uses' => 'MenuApiController@update',
        'middleware' => 'can:menu.menus.edit',
    ]);
    $router->delete('/{menu}', [
        'as' => 'api.menu.menu.delete',
        'uses' => 'MenuApiController@delete',
        'middleware' => 'can:menu.menus.delete',
    ]);
});



<?php

namespace BackendBridge\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\ApiRouter;
use Plenty\Plugin\Routing\Router;

class BackendBridgeRouteServiceProvider extends RouteServiceProvider
{
    public function map(Router $router, ApiRouter $api)
    {
        $api->version(['v1'], ['middleware' => ['oauth']], function ($routerApi) {
            /** @var ApiRouter $routerApi */
            $routerApi->get('backendbridge/maske', ['uses' => 'BackendBridge\Controllers\MaskeController@show']);
            $routerApi->post('backendbridge/maske', ['uses' => 'BackendBridge\Controllers\MaskeController@store']);
        });
    }
}

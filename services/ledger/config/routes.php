<?php

declare(strict_types=1);

use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');

Router::get('/conta/{uuid}/saldo', 'App\Controller\ContaController@saldo');
Router::get('/conta/{uuid}/extrato', 'App\Controller\ContaController@extrato');

Router::get('/favicon.ico', function () {
    return '';
});

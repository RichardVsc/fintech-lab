<?php

declare(strict_types=1);

use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'Ledger\Controller\IndexController@index');

Router::get('/conta/{uuid}/saldo', 'Ledger\Controller\ContaController@saldo');
Router::get('/conta/{uuid}/extrato', 'Ledger\Controller\ContaController@extrato');

Router::get('/favicon.ico', function () {
    return '';
});

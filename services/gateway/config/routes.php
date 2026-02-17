<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
use Gateway\Controller\EstornoController;
use Gateway\Controller\TransacaoController;
use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'Gateway\Controller\IndexController@index');

Router::post('/transacao', [TransacaoController::class, 'criar']);

Router::post('/estorno', [EstornoController::class, 'solicitar']);

Router::get('/favicon.ico', function () {
    return '';
});

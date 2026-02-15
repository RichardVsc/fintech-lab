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
return [
    'generator' => [
        'amqp' => [
            'consumer' => [
                'namespace' => 'Autorizador\\Amqp\\Consumer',
            ],
            'producer' => [
                'namespace' => 'Autorizador\\Amqp\\Producer',
            ],
        ],
        'aspect' => [
            'namespace' => 'Autorizador\\Aspect',
        ],
        'command' => [
            'namespace' => 'Autorizador\\Command',
        ],
        'controller' => [
            'namespace' => 'Autorizador\\Controller',
        ],
        'job' => [
            'namespace' => 'Autorizador\\Job',
        ],
        'listener' => [
            'namespace' => 'Autorizador\\Listener',
        ],
        'middleware' => [
            'namespace' => 'Autorizador\\Middleware',
        ],
        'Process' => [
            'namespace' => 'Autorizador\\Processes',
        ],
    ],
];

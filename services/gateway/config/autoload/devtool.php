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
                'namespace' => 'Gateway\\Amqp\\Consumer',
            ],
            'producer' => [
                'namespace' => 'Gateway\\Amqp\\Producer',
            ],
        ],
        'aspect' => [
            'namespace' => 'Gateway\\Aspect',
        ],
        'command' => [
            'namespace' => 'Gateway\\Command',
        ],
        'controller' => [
            'namespace' => 'Gateway\\Controller',
        ],
        'job' => [
            'namespace' => 'Gateway\\Job',
        ],
        'listener' => [
            'namespace' => 'Gateway\\Listener',
        ],
        'middleware' => [
            'namespace' => 'Gateway\\Middleware',
        ],
        'Process' => [
            'namespace' => 'Gateway\\Processes',
        ],
    ],
];

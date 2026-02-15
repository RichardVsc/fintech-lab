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
                'namespace' => 'Ledger\\Amqp\\Consumer',
            ],
            'producer' => [
                'namespace' => 'Ledger\\Amqp\\Producer',
            ],
        ],
        'aspect' => [
            'namespace' => 'Ledger\\Aspect',
        ],
        'command' => [
            'namespace' => 'Ledger\\Command',
        ],
        'controller' => [
            'namespace' => 'Ledger\\Controller',
        ],
        'job' => [
            'namespace' => 'Ledger\\Job',
        ],
        'listener' => [
            'namespace' => 'Ledger\\Listener',
        ],
        'middleware' => [
            'namespace' => 'Ledger\\Middleware',
        ],
        'Process' => [
            'namespace' => 'Ledger\\Processes',
        ],
    ],
];

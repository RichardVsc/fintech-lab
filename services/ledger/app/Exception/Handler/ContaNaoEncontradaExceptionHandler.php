<?php

declare(strict_types=1);

namespace Ledger\Exception\Handler;

use Ledger\Exception\ContaNaoEncontradaException;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ContaNaoEncontradaExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $this->stopPropagation();

        $body = json_encode(['error' => $throwable->getMessage()]);

        return $response
            ->withStatus(404)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new SwooleStream($body));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ContaNaoEncontradaException;
    }
}

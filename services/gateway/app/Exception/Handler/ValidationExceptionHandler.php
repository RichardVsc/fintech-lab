<?php

declare(strict_types=1);

namespace Gateway\Exception\Handler;

use Gateway\Validation\Exception\ValidationException;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ValidationExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->stopPropagation();

        /** @var ValidationException $throwable */
        $body = json_encode(['erros' => $throwable->getErrors()], JSON_UNESCAPED_UNICODE);

        return $response
            ->withStatus(422)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(new SwooleStream($body));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidationException;
    }
}

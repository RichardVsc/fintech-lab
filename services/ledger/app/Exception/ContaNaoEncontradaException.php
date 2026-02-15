<?php

declare(strict_types=1);

namespace Ledger\Exception;

use RuntimeException;

final class ContaNaoEncontradaException extends RuntimeException
{
    public function __construct(string $identificador)
    {
        parent::__construct(sprintf('Conta não encontrada: %s', $identificador));
    }
}

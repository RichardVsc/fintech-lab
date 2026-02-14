<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class TransacaoDuplicadaException extends RuntimeException
{
    public function __construct(string $transacaoId)
    {
        parent::__construct(sprintf('Transação já processada: %s', $transacaoId));
    }
}

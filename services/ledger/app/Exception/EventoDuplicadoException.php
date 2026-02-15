<?php

declare(strict_types=1);

namespace Ledger\Exception;

use RuntimeException;

final class EventoDuplicadoException extends RuntimeException
{
    public function __construct(string $transacaoId)
    {
        parent::__construct(sprintf('Evento já registrado para transação: %s', $transacaoId));
    }
}

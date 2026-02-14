<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class SaldoInsuficienteException extends RuntimeException
{
    public function __construct(int $saldoAtual, int $valorSolicitado)
    {
        parent::__construct(sprintf(
            'Saldo insuficiente: disponível %d, solicitado %d',
            $saldoAtual,
            $valorSolicitado,
        ));
    }
}

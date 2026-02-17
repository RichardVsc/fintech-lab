<?php

declare(strict_types=1);

namespace Autorizador\Exception;

use RuntimeException;

final class EstornoNaoPermitidoException extends RuntimeException
{
    public function __construct(string $transacaoId, string $motivo)
    {
        parent::__construct(sprintf('Estorno não permitido para %s: %s', $transacaoId, $motivo));
    }
}

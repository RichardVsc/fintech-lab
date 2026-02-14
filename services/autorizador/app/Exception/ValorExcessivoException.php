<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class ValorExcessivoException extends RuntimeException
{
    public function __construct(int $valor)
    {
        parent::__construct(sprintf(
            'Valor excede limite máximo: %d centavos (máximo: 1000000)',
            $valor,
        ));
    }
}

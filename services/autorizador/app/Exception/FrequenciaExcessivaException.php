<?php

declare(strict_types=1);

namespace Autorizador\Exception;

use RuntimeException;

final class FrequenciaExcessivaException extends RuntimeException
{
    public function __construct(string $cartaoMascarado)
    {
        parent::__construct(sprintf(
            'Frequência excessiva: 3+ transações em 60s para cartão %s',
            $cartaoMascarado,
        ));
    }
}

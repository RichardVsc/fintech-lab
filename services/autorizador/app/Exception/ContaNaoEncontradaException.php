<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;

final class ContaNaoEncontradaException extends RuntimeException
{
    public function __construct(string $cartaoMascarado)
    {
        parent::__construct(sprintf('Conta não encontrada para cartão: %s', $cartaoMascarado));
    }
}

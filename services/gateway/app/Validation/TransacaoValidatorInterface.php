<?php

declare(strict_types=1);

namespace App\Validation;

interface TransacaoValidatorInterface
{
    public function validate(?string $cartaoNumero, mixed $valor, ?string $comerciante): void;
}

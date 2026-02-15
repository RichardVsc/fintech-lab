<?php

declare(strict_types=1);

namespace Gateway\Validation;

interface TransacaoValidatorInterface
{
    public function validate(?string $cartaoNumero, mixed $valor, ?string $comerciante): void;
}

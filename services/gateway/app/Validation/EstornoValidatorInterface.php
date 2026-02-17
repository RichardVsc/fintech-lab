<?php

declare(strict_types=1);

namespace Gateway\Validation;

interface EstornoValidatorInterface
{
    public function validate(?string $transacaoId): void;
}

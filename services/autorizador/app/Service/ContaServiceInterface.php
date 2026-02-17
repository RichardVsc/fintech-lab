<?php

declare(strict_types=1);

namespace Autorizador\Service;

interface ContaServiceInterface
{
    /**
     * Debits the account inside a DB transaction with pessimistic lock.
     * Returns the remaining balance after deduction.
     */
    public function debitar(string $cartaoMascarado, int $valor): int;

    /**
     * Credits the account inside a DB transaction with pessimistic lock.
     * Returns the remaining balance after credit.
     */
    public function creditar(string $cartaoMascarado, int $valor): int;
}

<?php

declare(strict_types=1);

namespace App\Service;

interface ContaServiceInterface
{
    /**
     * Debits the account inside a DB transaction with pessimistic lock.
     * Returns the remaining balance after deduction.
     */
    public function debitar(string $cartaoMascarado, int $valor): int;
}

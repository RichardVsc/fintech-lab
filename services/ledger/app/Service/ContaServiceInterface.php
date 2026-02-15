<?php

declare(strict_types=1);

namespace Ledger\Service;

use Ledger\Model\SaldoAtual;

interface ContaServiceInterface
{
    public function buscarSaldo(string $uuid): SaldoAtual;

    public function buscarExtrato(string $uuid): array;
}

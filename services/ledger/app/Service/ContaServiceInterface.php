<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\SaldoAtual;

interface ContaServiceInterface
{
    public function buscarSaldo(string $uuid): SaldoAtual;

    public function buscarExtrato(string $uuid): array;
}

<?php

declare(strict_types=1);

namespace App\Service;

interface AntiFraudeServiceInterface
{
    public function validar(string $cartaoMascarado, int $valor): void;

    public function registrar(string $cartaoMascarado, string $transacaoId): void;
}

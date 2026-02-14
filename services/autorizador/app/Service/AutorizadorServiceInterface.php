<?php

declare(strict_types=1);

namespace App\Service;

use Shared\Event\TransacaoRecebida;

interface AutorizadorServiceInterface
{
    public function processar(TransacaoRecebida $evento): void;
}

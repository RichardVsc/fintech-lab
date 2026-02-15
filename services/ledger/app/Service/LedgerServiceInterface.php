<?php

declare(strict_types=1);

namespace Ledger\Service;

use Shared\Event\TransacaoAprovada;

interface LedgerServiceInterface
{
    public function processar(TransacaoAprovada $evento): void;
}

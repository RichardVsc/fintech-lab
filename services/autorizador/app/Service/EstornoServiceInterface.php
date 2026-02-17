<?php

declare(strict_types=1);

namespace Autorizador\Service;

use Shared\Event\EstornoSolicitado;

interface EstornoServiceInterface
{
    public function processar(EstornoSolicitado $evento): void;
}

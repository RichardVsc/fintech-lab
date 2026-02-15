<?php

declare(strict_types=1);

return [
    Autorizador\Service\AutorizadorServiceInterface::class => Autorizador\Service\AutorizadorService::class,
    Autorizador\Service\ContaServiceInterface::class => Autorizador\Service\ContaService::class,
    Autorizador\Service\AntiFraudeServiceInterface::class => Autorizador\Service\AntiFraudeService::class,
];

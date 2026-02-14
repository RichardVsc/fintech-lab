<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ContaServiceInterface;
use Psr\Http\Message\ResponseInterface;

class ContaController extends AbstractController
{
    public function __construct(
        private readonly ContaServiceInterface $contaService,
    ) {}

    public function saldo(string $uuid): ResponseInterface
    {
        $conta = $this->contaService->buscarSaldo($uuid);

        return $this->response->json([
            'conta_id' => $conta->uuid,
            'cartao_mascarado' => $conta->cartao_mascarado,
            'saldo' => $conta->saldo,
        ]);
    }

    public function extrato(string $uuid): ResponseInterface
    {
        return $this->response->json(
            $this->contaService->buscarExtrato($uuid),
        );
    }
}

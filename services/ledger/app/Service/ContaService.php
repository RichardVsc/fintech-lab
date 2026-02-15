<?php

declare(strict_types=1);

namespace Ledger\Service;

use Ledger\Exception\ContaNaoEncontradaException;
use Ledger\Model\EventoContabil;
use Ledger\Model\SaldoAtual;

final class ContaService implements ContaServiceInterface
{
    public function buscarSaldo(string $uuid): SaldoAtual
    {
        $conta = SaldoAtual::query()
            ->where('uuid', $uuid)
            ->first();

        if ($conta === null) {
            throw new ContaNaoEncontradaException($uuid);
        }

        return $conta;
    }

    public function buscarExtrato(string $uuid): array
    {
        $conta = $this->buscarSaldo($uuid);

        $eventos = EventoContabil::query()
            ->where('conta_id', $conta->id)
            ->orderBy('id')
            ->get(['tipo', 'transacao_id', 'valor', 'comerciante', 'created_at'])
            ->toArray();

        return [
            'conta_id' => $conta->uuid,
            'cartao_mascarado' => $conta->cartao_mascarado,
            'saldo' => $conta->saldo,
            'eventos' => $eventos,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Autorizador\Service;

use Autorizador\Exception\ContaNaoEncontradaException;
use Autorizador\Exception\SaldoInsuficienteException;
use Autorizador\Model\Conta;
use Hyperf\DbConnection\Db;

final class ContaService implements ContaServiceInterface
{
    public function debitar(string $cartaoMascarado, int $valor): int
    {
        return Db::transaction(function () use ($cartaoMascarado, $valor) {
            $conta = Conta::query()
                ->where('cartao_mascarado', $cartaoMascarado)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($conta === null) {
                throw new ContaNaoEncontradaException($cartaoMascarado);
            }

            if ($conta->saldo < $valor) {
                throw new SaldoInsuficienteException($conta->saldo, $valor);
            }

            $conta->saldo -= $valor;
            $conta->save();

            return $conta->saldo;
        });
    }
}

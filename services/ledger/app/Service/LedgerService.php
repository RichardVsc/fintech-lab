<?php

declare(strict_types=1);

namespace Ledger\Service;

use Ledger\Exception\ContaNaoEncontradaException;
use Ledger\Exception\EventoDuplicadoException;
use Ledger\Model\EventoContabil;
use Ledger\Model\SaldoAtual;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\DbConnection\Db;
use Shared\Event\EstornoAprovado;
use Shared\Event\TransacaoAprovada;

final class LedgerService implements LedgerServiceInterface
{
    public function __construct(
        private readonly StdoutLoggerInterface $logger,
    ) {}

    public function processar(TransacaoAprovada $evento): void
    {
        $this->log('Processando transação %s — cartão %s, valor %d',
            $evento->transacaoId, $evento->cartaoMascarado, $evento->valor);

        $this->verificarIdempotencia($evento->transacaoId, 'debito_realizado');

        $saldoFinal = Db::transaction(function () use ($evento) {
            $conta = SaldoAtual::query()
                ->where('cartao_mascarado', $evento->cartaoMascarado)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($conta === null) {
                throw new ContaNaoEncontradaException($evento->cartaoMascarado);
            }

            EventoContabil::create([
                'conta_id' => $conta->id,
                'tipo' => 'debito_realizado',
                'transacao_id' => $evento->transacaoId,
                'valor' => $evento->valor,
                'comerciante' => $evento->comerciante,
            ]);

            $conta->saldo -= $evento->valor;
            $conta->save();

            return $conta->saldo;
        });

        $this->log('Transação %s registrada — novo saldo: %d', $evento->transacaoId, $saldoFinal);
    }

    public function processarEstorno(EstornoAprovado $evento): void
    {
        $this->log('Processando estorno %s — cartão %s, valor %d',
            $evento->transacaoId, $evento->cartaoMascarado, $evento->valor);

        $this->verificarIdempotencia($evento->transacaoId, 'estorno_realizado');

        $saldoFinal = Db::transaction(function () use ($evento) {
            $conta = SaldoAtual::query()
                ->where('cartao_mascarado', $evento->cartaoMascarado)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($conta === null) {
                throw new ContaNaoEncontradaException($evento->cartaoMascarado);
            }

            EventoContabil::create([
                'conta_id' => $conta->id,
                'tipo' => 'estorno_realizado',
                'transacao_id' => $evento->transacaoId,
                'valor' => $evento->valor,
                'comerciante' => $evento->comerciante,
            ]);

            $conta->saldo += $evento->valor;
            $conta->save();

            return $conta->saldo;
        });

        $this->log('Estorno %s registrado — novo saldo: %d', $evento->transacaoId, $saldoFinal);
    }

    private function verificarIdempotencia(string $transacaoId, string $tipo): void
    {
        $exists = EventoContabil::query()
            ->where('transacao_id', $transacaoId)
            ->where('tipo', $tipo)
            ->exists();

        if ($exists) {
            throw new EventoDuplicadoException($transacaoId);
        }
    }

    private function log(string $message, mixed ...$args): void
    {
        $this->logger->info(sprintf('[Ledger] %s', $args ? sprintf($message, ...$args) : $message));
    }
}

<?php

declare(strict_types=1);

namespace Autorizador\Service;

use Autorizador\Amqp\Producer\EstornoAprovadoProducer;
use Autorizador\Amqp\Producer\EstornoNegadoProducer;
use Autorizador\Exception\EstornoNaoPermitidoException;
use Hyperf\Amqp\Producer;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\DbConnection\Db;
use Shared\Event\EstornoAprovado;
use Shared\Event\EstornoNegado;
use Shared\Event\EstornoSolicitado;

final class EstornoService implements EstornoServiceInterface
{
    public function __construct(
        private readonly ContaServiceInterface $contaService,
        private readonly Producer $producer,
        private readonly StdoutLoggerInterface $logger,
    ) {}

    public function processar(EstornoSolicitado $evento): void
    {
        $this->log('Processando estorno para transação %s', $evento->transacaoId);

        try {
            $transacao = $this->buscarTransacao($evento->transacaoId);
            $this->verificarPermissao($transacao);

            $saldoRestante = $this->contaService->creditar($transacao->cartao_mascarado, $transacao->valor);

            $this->marcarEstornada($evento->transacaoId);

            $this->aprovar($evento->transacaoId, $transacao, $saldoRestante);
        } catch (EstornoNaoPermitidoException $e) {
            $this->negar($evento->transacaoId, $e->getMessage());
        }
    }

    private function buscarTransacao(string $transacaoId): object
    {
        $transacao = Db::table('transacoes_processadas')
            ->where('transacao_id', $transacaoId)
            ->first();

        if ($transacao === null) {
            throw new EstornoNaoPermitidoException($transacaoId, 'transação não encontrada');
        }

        return $transacao;
    }

    private function verificarPermissao(object $transacao): void
    {
        if ($transacao->resultado === 'negada') {
            throw new EstornoNaoPermitidoException($transacao->transacao_id, 'transação foi negada');
        }

        if ($transacao->resultado === 'estornada') {
            throw new EstornoNaoPermitidoException($transacao->transacao_id, 'já estornada');
        }
    }

    private function marcarEstornada(string $transacaoId): void
    {
        Db::table('transacoes_processadas')
            ->where('transacao_id', $transacaoId)
            ->update(['resultado' => 'estornada']);
    }

    private function aprovar(string $transacaoId, object $transacao, int $saldoRestante): void
    {
        $aprovado = new EstornoAprovado(
            transacaoId: $transacaoId,
            cartaoMascarado: $transacao->cartao_mascarado,
            valor: $transacao->valor,
            comerciante: $transacao->comerciante,
            saldoRestante: $saldoRestante,
            timestamp: date('c'),
        );

        $this->producer->produce(new EstornoAprovadoProducer($aprovado));
        $this->log('Estorno APROVADO para transação %s — saldo restante: %d', $transacaoId, $saldoRestante);
    }

    private function negar(string $transacaoId, string $motivo): void
    {
        $negado = new EstornoNegado(
            transacaoId: $transacaoId,
            motivo: $motivo,
            timestamp: date('c'),
        );

        $this->producer->produce(new EstornoNegadoProducer($negado));
        $this->log('Estorno NEGADO para transação %s — %s', $transacaoId, $motivo);
    }

    private function log(string $message, mixed ...$args): void
    {
        $this->logger->info(sprintf('[Autorizador] %s', $args ? sprintf($message, ...$args) : $message));
    }
}

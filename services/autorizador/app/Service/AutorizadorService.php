<?php

declare(strict_types=1);

namespace App\Service;

use App\Amqp\Producer\TransacaoAprovadaProducer;
use App\Amqp\Producer\TransacaoNegadaProducer;
use App\Exception\ContaNaoEncontradaException;
use App\Exception\FrequenciaExcessivaException;
use App\Exception\SaldoInsuficienteException;
use App\Exception\TransacaoDuplicadaException;
use App\Exception\ValorExcessivoException;
use Hyperf\Amqp\Producer;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\DbConnection\Db;
use Shared\Event\TransacaoAprovada;
use Shared\Event\TransacaoNegada;
use Shared\Event\TransacaoRecebida;

final class AutorizadorService implements AutorizadorServiceInterface
{
    public function __construct(
        private readonly ContaServiceInterface $contaService,
        private readonly AntiFraudeServiceInterface $antiFraudeService,
        private readonly Producer $producer,
        private readonly StdoutLoggerInterface $logger,
    ) {}

    public function processar(TransacaoRecebida $evento): void
    {
        $this->log('Processando transação %s — cartão %s, valor %d, comerciante %s',
            $evento->transacaoId, $evento->cartaoMascarado, $evento->valor, $evento->comerciante);

        try {
            $this->verificarIdempotencia($evento->transacaoId);
            $this->antiFraudeService->validar($evento->cartaoMascarado, $evento->valor);
            $saldoRestante = $this->contaService->debitar($evento->cartaoMascarado, $evento->valor);

            $this->aprovar($evento, $saldoRestante);
        } catch (TransacaoDuplicadaException $e) {
            $this->warn($e->getMessage());
        } catch (SaldoInsuficienteException|FrequenciaExcessivaException|ValorExcessivoException|ContaNaoEncontradaException $e) {
            $this->negar($evento, $e->getMessage());
        }
    }

    private function aprovar(TransacaoRecebida $evento, int $saldoRestante): void
    {
        $this->registrarProcessamento($evento->transacaoId, 'aprovada');
        $this->antiFraudeService->registrar($evento->cartaoMascarado, $evento->transacaoId);

        $aprovada = new TransacaoAprovada(
            transacaoId: $evento->transacaoId,
            saldoRestante: $saldoRestante,
            timestamp: date('c'),
        );

        $this->producer->produce(new TransacaoAprovadaProducer($aprovada));
        $this->log('Transação %s APROVADA — saldo restante: %d', $evento->transacaoId, $saldoRestante);
    }

    private function negar(TransacaoRecebida $evento, string $motivo): void
    {
        $this->registrarProcessamento($evento->transacaoId, 'negada');

        $negada = new TransacaoNegada(
            transacaoId: $evento->transacaoId,
            motivo: $motivo,
            timestamp: date('c'),
        );

        $this->producer->produce(new TransacaoNegadaProducer($negada));
        $this->log('Transação %s NEGADA — %s', $evento->transacaoId, $motivo);
    }

    private function log(string $message, mixed ...$args): void
    {
        $this->logger->info(sprintf('[Autorizador] %s', $args ? sprintf($message, ...$args) : $message));
    }

    private function warn(string $message, mixed ...$args): void
    {
        $this->logger->warning(sprintf('[Autorizador] %s', $args ? sprintf($message, ...$args) : $message));
    }

    private function verificarIdempotencia(string $transacaoId): void
    {
        $exists = Db::table('transacoes_processadas')
            ->where('transacao_id', $transacaoId)
            ->exists();

        if ($exists) {
            throw new TransacaoDuplicadaException($transacaoId);
        }
    }

    private function registrarProcessamento(string $transacaoId, string $resultado): void
    {
        Db::table('transacoes_processadas')->insert([
            'transacao_id' => $transacaoId,
            'resultado' => $resultado,
        ]);
    }
}

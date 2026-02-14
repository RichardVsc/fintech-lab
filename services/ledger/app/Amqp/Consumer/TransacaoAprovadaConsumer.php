<?php

declare(strict_types=1);

namespace App\Amqp\Consumer;

use App\Exception\EventoDuplicadoException;
use App\Service\LedgerServiceInterface;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Result;
use Hyperf\Contract\StdoutLoggerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Shared\Event\TransacaoAprovada;
use Throwable;

#[Consumer(
    exchange: 'transacoes',
    routingKey: 'transacao.aprovada',
    queue: 'ledger.transacao_aprovada',
    nums: 1,
)]
class TransacaoAprovadaConsumer extends ConsumerMessage
{
    protected string|Type $type = Type::TOPIC;

    public function __construct(
        private readonly LedgerServiceInterface $ledgerService,
        private readonly StdoutLoggerInterface $logger,
    ) {}

    public function consumeMessage($data, AMQPMessage $message): Result
    {
        try {
            $evento = TransacaoAprovada::fromArray($data);
            $this->ledgerService->processar($evento);

            return Result::ACK;
        } catch (EventoDuplicadoException $e) {
            $this->logger->warning(sprintf('[Ledger] %s', $e->getMessage()));
            return Result::ACK;
        } catch (Throwable $e) {
            $this->logger->error(sprintf('[Ledger] Erro ao processar mensagem: %s', $e->getMessage()));
            return Result::NACK;
        }
    }
}

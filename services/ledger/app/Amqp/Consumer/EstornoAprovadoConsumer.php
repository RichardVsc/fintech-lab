<?php

declare(strict_types=1);

namespace Ledger\Amqp\Consumer;

use Ledger\Exception\EventoDuplicadoException;
use Ledger\Service\LedgerServiceInterface;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Result;
use Hyperf\Contract\StdoutLoggerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Shared\Event\EstornoAprovado;
use Throwable;

#[Consumer(
    exchange: 'transacoes',
    routingKey: 'estorno.aprovado',
    queue: 'ledger.estorno_aprovado',
    nums: 1,
)]
class EstornoAprovadoConsumer extends ConsumerMessage
{
    protected string|Type $type = Type::TOPIC;

    public function __construct(
        private readonly LedgerServiceInterface $ledgerService,
        private readonly StdoutLoggerInterface $logger,
    ) {}

    public function consumeMessage($data, AMQPMessage $message): Result
    {
        try {
            $evento = EstornoAprovado::fromArray($data);
            $this->ledgerService->processarEstorno($evento);

            return Result::ACK;
        } catch (EventoDuplicadoException $e) {
            $this->logger->warning(sprintf('[Ledger] %s', $e->getMessage()));
            return Result::ACK;
        } catch (Throwable $e) {
            $this->logger->error(sprintf('[Ledger] Erro ao processar estorno: %s', $e->getMessage()));
            return Result::NACK;
        }
    }
}

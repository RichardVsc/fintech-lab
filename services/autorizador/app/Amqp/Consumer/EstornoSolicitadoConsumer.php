<?php

declare(strict_types=1);

namespace Autorizador\Amqp\Consumer;

use Autorizador\Service\EstornoServiceInterface;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Result;
use Hyperf\Contract\StdoutLoggerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Shared\Event\EstornoSolicitado;
use Throwable;

#[Consumer(
    exchange: 'transacoes',
    routingKey: 'estorno.solicitado',
    queue: 'autorizador.estorno_solicitado',
    nums: 1,
)]
class EstornoSolicitadoConsumer extends ConsumerMessage
{
    protected string|Type $type = Type::TOPIC;

    public function __construct(
        private readonly EstornoServiceInterface $estornoService,
        private readonly StdoutLoggerInterface $logger,
    ) {}

    public function consumeMessage($data, AMQPMessage $message): Result
    {
        try {
            $evento = EstornoSolicitado::fromArray($data);
            $this->estornoService->processar($evento);

            return Result::ACK;
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                '[Autorizador] Erro inesperado ao processar estorno: %s',
                $e->getMessage(),
            ));

            return Result::NACK;
        }
    }
}

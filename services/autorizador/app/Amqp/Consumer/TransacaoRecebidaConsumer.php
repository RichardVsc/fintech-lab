<?php

declare(strict_types=1);

namespace Autorizador\Amqp\Consumer;

use Autorizador\Service\AutorizadorServiceInterface;
use Hyperf\Amqp\Annotation\Consumer;
use Hyperf\Amqp\Message\ConsumerMessage;
use Hyperf\Amqp\Message\Type;
use Hyperf\Amqp\Result;
use Hyperf\Contract\StdoutLoggerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Shared\Event\TransacaoRecebida;
use Throwable;

#[Consumer(
    exchange: 'transacoes',
    routingKey: 'transacao.recebida',
    queue: 'autorizador.transacao_recebida',
    nums: 1,
)]
class TransacaoRecebidaConsumer extends ConsumerMessage
{
    protected string|Type $type = Type::TOPIC;

    public function __construct(
        private readonly AutorizadorServiceInterface $autorizadorService,
        private readonly StdoutLoggerInterface $logger,
    ) {}

    public function consumeMessage($data, AMQPMessage $message): Result
    {
        try {
            $evento = TransacaoRecebida::fromArray($data);
            $this->autorizadorService->processar($evento);

            return Result::ACK;
        } catch (Throwable $e) {
            $this->logger->error(sprintf(
                '[Autorizador] Erro inesperado ao processar mensagem: %s',
                $e->getMessage(),
            ));

            return Result::NACK;
        }
    }
}

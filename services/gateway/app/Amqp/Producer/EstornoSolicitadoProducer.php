<?php

declare(strict_types=1);

namespace Gateway\Amqp\Producer;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;
use Shared\Event\EstornoSolicitado;

#[Producer(exchange: 'transacoes', routingKey: 'estorno.solicitado')]
class EstornoSolicitadoProducer extends ProducerMessage
{
    protected string|Type $type = Type::TOPIC;

    protected string $exchange = 'transacoes';

    protected array|string $routingKey = 'estorno.solicitado';

    public function __construct(EstornoSolicitado $evento)
    {
        $this->payload = $evento->toArray();
    }
}

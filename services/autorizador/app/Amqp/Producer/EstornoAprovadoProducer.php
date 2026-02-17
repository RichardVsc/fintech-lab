<?php

declare(strict_types=1);

namespace Autorizador\Amqp\Producer;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;
use Shared\Event\EstornoAprovado;

#[Producer(exchange: 'transacoes', routingKey: 'estorno.aprovado')]
class EstornoAprovadoProducer extends ProducerMessage
{
    protected string|Type $type = Type::TOPIC;

    protected string $exchange = 'transacoes';

    protected array|string $routingKey = 'estorno.aprovado';

    public function __construct(EstornoAprovado $evento)
    {
        $this->payload = $evento->toArray();
    }
}

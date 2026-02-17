<?php

declare(strict_types=1);

namespace Autorizador\Amqp\Producer;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;
use Shared\Event\EstornoNegado;

#[Producer(exchange: 'transacoes', routingKey: 'estorno.negado')]
class EstornoNegadoProducer extends ProducerMessage
{
    protected string|Type $type = Type::TOPIC;

    protected string $exchange = 'transacoes';

    protected array|string $routingKey = 'estorno.negado';

    public function __construct(EstornoNegado $evento)
    {
        $this->payload = $evento->toArray();
    }
}

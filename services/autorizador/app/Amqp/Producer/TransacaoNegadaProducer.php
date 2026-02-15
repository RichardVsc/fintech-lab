<?php

declare(strict_types=1);

namespace Autorizador\Amqp\Producer;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;
use Shared\Event\TransacaoNegada;

#[Producer(exchange: 'transacoes', routingKey: 'transacao.negada')]
class TransacaoNegadaProducer extends ProducerMessage
{
    protected string|Type $type = Type::TOPIC;

    public function __construct(TransacaoNegada $evento)
    {
        $this->payload = $evento->toArray();
    }
}

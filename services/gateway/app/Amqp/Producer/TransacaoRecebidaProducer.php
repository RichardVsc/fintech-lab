<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;
use Shared\Event\TransacaoRecebida;

#[Producer(exchange: 'transacoes', routingKey: 'transacao.recebida')]
class TransacaoRecebidaProducer extends ProducerMessage
{
    protected string|Type $type = Type::TOPIC;

    public function __construct(TransacaoRecebida $evento)
    {
        $this->payload = $evento->toArray();
    }
}

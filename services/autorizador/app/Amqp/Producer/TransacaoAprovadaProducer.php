<?php

declare(strict_types=1);

namespace App\Amqp\Producer;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;
use Shared\Event\TransacaoAprovada;

#[Producer(exchange: 'transacoes', routingKey: 'transacao.aprovada')]
class TransacaoAprovadaProducer extends ProducerMessage
{
    protected string|Type $type = Type::TOPIC;

    public function __construct(TransacaoAprovada $evento)
    {
        $this->payload = $evento->toArray();
    }
}

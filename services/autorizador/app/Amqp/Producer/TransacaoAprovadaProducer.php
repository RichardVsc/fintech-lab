<?php

declare(strict_types=1);

namespace Autorizador\Amqp\Producer;

use Hyperf\Amqp\Annotation\Producer;
use Hyperf\Amqp\Message\ProducerMessage;
use Hyperf\Amqp\Message\Type;
use Shared\Event\TransacaoAprovada;

#[Producer(exchange: 'transacoes', routingKey: 'transacao.aprovada')]
class TransacaoAprovadaProducer extends ProducerMessage
{
    protected string|Type $type = Type::TOPIC;

    protected string $exchange = 'transacoes';

    protected array|string $routingKey = 'transacao.aprovada';

    public function __construct(TransacaoAprovada $evento)
    {
        $this->payload = $evento->toArray();
    }
}

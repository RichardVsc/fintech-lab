<?php

declare(strict_types=1);

namespace Gateway\Controller;

use Gateway\Amqp\Producer\EstornoSolicitadoProducer;
use Gateway\Validation\EstornoValidatorInterface;
use Hyperf\Amqp\Producer;
use Shared\Event\EstornoSolicitado;

class EstornoController extends AbstractController
{
    public function __construct(
        private readonly Producer $producer,
        private readonly EstornoValidatorInterface $validator,
    ) {}

    public function solicitar()
    {
        $transacaoId = $this->request->input('transacao_id');

        $this->validator->validate($transacaoId);

        $evento = new EstornoSolicitado(
            transacaoId: $transacaoId,
            timestamp: date('c'),
        );

        $this->producer->produce(new EstornoSolicitadoProducer($evento));

        return $this->response->json([
            'transacao_id' => $transacaoId,
            'status' => 'estorno_solicitado',
        ])->withStatus(202);
    }
}

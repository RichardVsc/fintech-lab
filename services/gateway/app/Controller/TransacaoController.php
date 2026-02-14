<?php

declare(strict_types=1);

namespace App\Controller;

use App\Amqp\Producer\TransacaoRecebidaProducer;
use App\Validation\TransacaoValidatorInterface;
use Hyperf\Amqp\Producer;
use Ramsey\Uuid\Uuid;
use Shared\Event\TransacaoRecebida;

class TransacaoController extends AbstractController
{
    public function __construct(
        private readonly Producer $producer,
        private readonly TransacaoValidatorInterface $validator,
    ) {}

    public function criar()
    {
        $cartaoNumero = $this->request->input('cartao_numero');
        $valor = $this->request->input('valor');
        $comerciante = $this->request->input('comerciante');

        $this->validator->validate($cartaoNumero, $valor, $comerciante);

        $transacaoId = Uuid::uuid4()->toString();
        $cartaoMascarado = '**** **** **** ' . substr($cartaoNumero, -4);

        $evento = new TransacaoRecebida(
            transacaoId: $transacaoId,
            cartaoMascarado: $cartaoMascarado,
            valor: $valor,
            comerciante: $comerciante,
            timestamp: date('c'),
        );

        $this->producer->produce(new TransacaoRecebidaProducer($evento));

        return $this->response->json([
            'transacao_id' => $transacaoId,
            'status' => 'processando',
        ])->withStatus(202);
    }
}

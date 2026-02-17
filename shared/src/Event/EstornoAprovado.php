<?php

declare(strict_types=1);

namespace Shared\Event;

readonly class EstornoAprovado
{
    public function __construct(
        public string $transacaoId,
        public string $cartaoMascarado,
        public int $valor,
        public string $comerciante,
        public int $saldoRestante,
        public string $timestamp,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            transacaoId: $data['transacao_id'],
            cartaoMascarado: $data['cartao_mascarado'],
            valor: $data['valor'],
            comerciante: $data['comerciante'],
            saldoRestante: $data['saldo_restante'],
            timestamp: $data['timestamp'],
        );
    }

    public function toArray(): array
    {
        return [
            'transacao_id' => $this->transacaoId,
            'cartao_mascarado' => $this->cartaoMascarado,
            'valor' => $this->valor,
            'comerciante' => $this->comerciante,
            'saldo_restante' => $this->saldoRestante,
            'timestamp' => $this->timestamp,
        ];
    }
}

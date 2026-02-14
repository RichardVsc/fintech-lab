<?php

declare(strict_types=1);

namespace Shared\Event;

readonly class TransacaoAprovada
{
    public function __construct(
        public string $transacaoId,
        public int $saldoRestante,
        public string $timestamp,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            transacaoId: $data['transacao_id'],
            saldoRestante: $data['saldo_restante'],
            timestamp: $data['timestamp'],
        );
    }

    public function toArray(): array
    {
        return [
            'transacao_id' => $this->transacaoId,
            'saldo_restante' => $this->saldoRestante,
            'timestamp' => $this->timestamp,
        ];
    }
}

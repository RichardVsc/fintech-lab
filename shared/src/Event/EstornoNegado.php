<?php

declare(strict_types=1);

namespace Shared\Event;

readonly class EstornoNegado
{
    public function __construct(
        public string $transacaoId,
        public string $motivo,
        public string $timestamp,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            transacaoId: $data['transacao_id'],
            motivo: $data['motivo'],
            timestamp: $data['timestamp'],
        );
    }

    public function toArray(): array
    {
        return [
            'transacao_id' => $this->transacaoId,
            'motivo' => $this->motivo,
            'timestamp' => $this->timestamp,
        ];
    }
}

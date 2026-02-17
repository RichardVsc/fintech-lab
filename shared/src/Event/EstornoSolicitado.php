<?php

declare(strict_types=1);

namespace Shared\Event;

readonly class EstornoSolicitado
{
    public function __construct(
        public string $transacaoId,
        public string $timestamp,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            transacaoId: $data['transacao_id'],
            timestamp: $data['timestamp'],
        );
    }

    public function toArray(): array
    {
        return [
            'transacao_id' => $this->transacaoId,
            'timestamp' => $this->timestamp,
        ];
    }
}

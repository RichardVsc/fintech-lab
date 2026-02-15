<?php

declare(strict_types=1);

namespace Ledger\Model;

class SaldoAtual extends Model
{
    protected ?string $table = 'saldo_atual';

    public const CREATED_AT = null;

    protected array $fillable = [
        'uuid',
        'cartao_mascarado',
        'saldo',
    ];

    protected array $casts = [
        'id' => 'integer',
        'saldo' => 'integer',
    ];
}

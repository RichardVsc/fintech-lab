<?php

declare(strict_types=1);

namespace App\Model;

class Conta extends Model
{
    protected ?string $table = 'contas';

    protected array $fillable = [
        'cartao_mascarado',
        'saldo',
    ];

    protected array $casts = [
        'id' => 'integer',
        'saldo' => 'integer',
    ];
}

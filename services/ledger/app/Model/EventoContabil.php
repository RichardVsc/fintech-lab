<?php

declare(strict_types=1);

namespace App\Model;

class EventoContabil extends Model
{
    protected ?string $table = 'eventos_contabeis';

    public const UPDATED_AT = null;

    protected array $fillable = [
        'conta_id',
        'tipo',
        'transacao_id',
        'valor',
        'comerciante',
    ];

    protected array $casts = [
        'id' => 'integer',
        'conta_id' => 'integer',
        'valor' => 'integer',
    ];
}

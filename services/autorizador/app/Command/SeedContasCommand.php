<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;

#[Command]
class SeedContasCommand extends HyperfCommand
{
    protected ?string $name = 'seed:contas';
    protected string $description = 'Seed test accounts with initial balance';

    public function handle(): void
    {
        $contas = [
            ['cartao_mascarado' => '**** **** **** 1234', 'saldo' => 50_000_000],
            ['cartao_mascarado' => '**** **** **** 5678', 'saldo' => 10_000_000],
            ['cartao_mascarado' => '**** **** **** 9012', 'saldo' => 1_000_000],
        ];

        foreach ($contas as $conta) {
            Db::table('contas')->updateOrInsert(
                ['cartao_mascarado' => $conta['cartao_mascarado']],
                [...$conta, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')],
            );
        }

        $this->info('Contas seeded successfully.');
    }
}

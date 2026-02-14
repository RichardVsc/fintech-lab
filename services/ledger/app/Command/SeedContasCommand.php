<?php

declare(strict_types=1);

namespace App\Command;

use App\Model\EventoContabil;
use App\Model\SaldoAtual;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\DbConnection\Db;
use Ramsey\Uuid\Uuid;

#[Command]
class SeedContasCommand extends HyperfCommand
{
    protected ?string $name = 'seed:contas';
    protected string $description = 'Seed test accounts with initial balance via credit events';

    public function handle(): void
    {
        $contas = [
            ['cartao_mascarado' => '**** **** **** 1234', 'saldo' => 50_000_000],
            ['cartao_mascarado' => '**** **** **** 5678', 'saldo' => 10_000_000],
            ['cartao_mascarado' => '**** **** **** 9012', 'saldo' => 1_000_000],
        ];

        foreach ($contas as $i => $dados) {
            Db::transaction(function () use ($dados, $i) {
                $conta = SaldoAtual::query()
                    ->where('cartao_mascarado', $dados['cartao_mascarado'])
                    ->first();

                if ($conta !== null) {
                    return;
                }

                $conta = SaldoAtual::create([
                    'uuid' => Uuid::uuid4()->toString(),
                    'cartao_mascarado' => $dados['cartao_mascarado'],
                    'saldo' => 0,
                ]);

                $transacaoId = sprintf('seed-inicial-%03d', $i + 1);

                EventoContabil::create([
                    'conta_id' => $conta->id,
                    'tipo' => 'credito_realizado',
                    'transacao_id' => $transacaoId,
                    'valor' => $dados['saldo'],
                    'comerciante' => null,
                ]);

                $conta->saldo = $dados['saldo'];
                $conta->save();
            });
        }

        $this->info('Contas seeded successfully (via credit events).');
    }
}

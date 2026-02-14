<?php

declare(strict_types=1);

namespace App\Service;

use App\Exception\FrequenciaExcessivaException;
use App\Exception\ValorExcessivoException;
use Hyperf\Redis\Redis;

final class AntiFraudeService implements AntiFraudeServiceInterface
{
    private const int VALOR_MAXIMO = 1_000_000;
    private const int JANELA_SEGUNDOS = 60;
    private const int TRANSACOES_MAXIMAS = 3;

    public function __construct(
        private readonly Redis $redis,
    ) {}

    public function validar(string $cartaoMascarado, int $valor): void
    {
        $this->validarValorMaximo($valor);
        $this->validarFrequencia($cartaoMascarado);
    }

    public function registrar(string $cartaoMascarado, string $transacaoId): void
    {
        $key = $this->chaveFrequencia($cartaoMascarado);
        $agora = microtime(true);

        $this->redis->zAdd($key, $agora, $transacaoId);
        $this->redis->expire($key, self::JANELA_SEGUNDOS * 2);
    }

    private function validarValorMaximo(int $valor): void
    {
        if ($valor > self::VALOR_MAXIMO) {
            throw new ValorExcessivoException($valor);
        }
    }

    private function validarFrequencia(string $cartaoMascarado): void
    {
        $key = $this->chaveFrequencia($cartaoMascarado);
        $agora = microtime(true);
        $inicio = $agora - self::JANELA_SEGUNDOS;

        $this->redis->zRemRangeByScore($key, '-inf', (string) $inicio);

        $count = $this->redis->zCard($key);

        if ($count >= self::TRANSACOES_MAXIMAS) {
            throw new FrequenciaExcessivaException($cartaoMascarado);
        }
    }

    private function chaveFrequencia(string $cartaoMascarado): string
    {
        return 'antifraude:freq:' . md5($cartaoMascarado);
    }
}

<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateTransacoesProcessadasTable extends Migration
{
    public function up(): void
    {
        Schema::create('transacoes_processadas', function (Blueprint $table) {
            $table->string('transacao_id', 36)->primary();
            $table->string('resultado', 10);
            $table->timestamp('processada_em')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transacoes_processadas');
    }
}

<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class AddDetalhesToTransacoesProcessadas extends Migration
{
    public function up(): void
    {
        Schema::table('transacoes_processadas', function (Blueprint $table) {
            $table->bigInteger('valor')->nullable()->after('resultado');
            $table->string('cartao_mascarado', 19)->nullable()->after('valor');
            $table->string('comerciante', 255)->nullable()->after('cartao_mascarado');
        });
    }

    public function down(): void
    {
        Schema::table('transacoes_processadas', function (Blueprint $table) {
            $table->dropColumn(['valor', 'cartao_mascarado', 'comerciante']);
        });
    }
}

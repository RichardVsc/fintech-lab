<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateSaldoAtualTable extends Migration
{
    public function up(): void
    {
        Schema::create('saldo_atual', function (Blueprint $table) {
            $table->id();
            $table->string('uuid', 36)->unique();
            $table->string('cartao_mascarado', 19)->unique();
            $table->bigInteger('saldo')->default(0);
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saldo_atual');
    }
}

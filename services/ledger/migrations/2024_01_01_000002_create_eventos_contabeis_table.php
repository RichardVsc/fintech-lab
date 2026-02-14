<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class CreateEventosContabeisTable extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_contabeis', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conta_id');
            $table->string('tipo', 30);
            $table->string('transacao_id', 36)->unique();
            $table->bigInteger('valor');
            $table->string('comerciante', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('conta_id')->references('id')->on('saldo_atual');
            $table->index('conta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_contabeis');
    }
}

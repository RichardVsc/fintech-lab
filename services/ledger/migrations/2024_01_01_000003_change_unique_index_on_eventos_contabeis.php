<?php

declare(strict_types=1);

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\Schema;

class ChangeUniqueIndexOnEventosContabeis extends Migration
{
    public function up(): void
    {
        Schema::table('eventos_contabeis', function (Blueprint $table) {
            $table->dropUnique(['transacao_id']);
            $table->unique(['transacao_id', 'tipo'], 'eventos_contabeis_transacao_id_tipo_unique');
        });
    }

    public function down(): void
    {
        Schema::table('eventos_contabeis', function (Blueprint $table) {
            $table->dropUnique('eventos_contabeis_transacao_id_tipo_unique');
            $table->unique('transacao_id');
        });
    }
}

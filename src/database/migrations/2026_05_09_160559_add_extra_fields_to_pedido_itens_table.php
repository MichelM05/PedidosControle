<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pedido_itens', function (Blueprint $table) {
            $table->date('dt_entrega')->nullable();
            $table->string('item_lei')->nullable();
            $table->string('tipo_manutencao')->nullable();
            $table->string('local_prestacao')->nullable();
            $table->decimal('desconto_absoluto', 12, 4)->nullable();
            $table->decimal('icms_monofasico', 12, 4)->nullable();
            $table->decimal('reducao_base_icms', 12, 4)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_itens', function (Blueprint $table) {
            $table->dropColumn([
                'dt_entrega',
                'item_lei',
                'tipo_manutencao',
                'local_prestacao',
                'desconto_absoluto',
                'icms_monofasico',
                'reducao_base_icms',
            ]);
        });
    }
};

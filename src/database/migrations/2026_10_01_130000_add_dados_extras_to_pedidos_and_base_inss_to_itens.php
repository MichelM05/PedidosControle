<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            // Cabeçalho do PDF: frete, condição de pagamento, comprador, totais, blocos de endereço e observações
            $table->json('dados_extras')->nullable();
        });

        Schema::table('pedido_itens', function (Blueprint $table) {
            $table->decimal('base_inss', 8, 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pedidos', function (Blueprint $table) {
            $table->dropColumn('dados_extras');
        });

        Schema::table('pedido_itens', function (Blueprint $table) {
            $table->dropColumn('base_inss');
        });
    }
};

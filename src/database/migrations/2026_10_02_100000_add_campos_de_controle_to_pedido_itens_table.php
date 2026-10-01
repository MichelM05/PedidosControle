<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pedido_itens', function (Blueprint $table) {
            // Cidade de entrega (a planilha de controle usa só a cidade, sem a UF)
            $table->string('cidade_entrega')->nullable();

            // Etapas do controle de produção: texto livre ou data ("recebido 02/09", "12/03/2026", "xxxxx")
            foreach (['desenho_nesting', 'compra_mp', 'compra_insumo', 'usinagem', 'corte_dobra', 'solda', 'pintura', 'montagem'] as $etapa) {
                $table->string($etapa)->nullable();
            }

            $table->string('responsavel')->nullable();
            $table->string('status')->default('andamento');
        });
    }

    public function down(): void
    {
        Schema::table('pedido_itens', function (Blueprint $table) {
            $table->dropColumn([
                'cidade_entrega', 'desenho_nesting', 'compra_mp', 'compra_insumo', 'usinagem',
                'corte_dobra', 'solda', 'pintura', 'montagem', 'responsavel', 'status',
            ]);
        });
    }
};

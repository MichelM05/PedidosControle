<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Histórico de alterações: uma linha por campo alterado (ou por criação/exclusão).
        // Guarda cópias (nome do usuário, número do pedido, descrição do item) para continuar legível
        // mesmo se o usuário, o pedido ou o item forem apagados; por isso não há chaves estrangeiras para pedido/item.
        Schema::create('historico', function (Blueprint $table) {
            $table->id();
            $table->uuid('lote')->index(); // alterações salvas juntas (uma requisição)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('usuario_nome');
            $table->unsignedBigInteger('pedido_id')->nullable()->index();
            $table->string('pedido_numero')->nullable();
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('item_descricao')->nullable();
            $table->string('acao', 20); // criou | editou | excluiu
            $table->string('campo')->nullable();
            $table->text('valor_anterior')->nullable();
            $table->text('valor_novo')->nullable();
            $table->string('origem')->nullable(); // "Importado do PDF", "Criado manualmente"...
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historico');
    }
};

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Uma alteração registrada: quem, quando, em qual pedido/item, o quê e de/para qual valor. */
class Historico extends Model
{
    protected $table = 'historico';

    public $timestamps = false;

    protected $fillable = [
        'lote', 'user_id', 'usuario_nome', 'pedido_id', 'pedido_numero', 'item_id', 'item_descricao',
        'acao', 'campo', 'valor_anterior', 'valor_novo', 'origem',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

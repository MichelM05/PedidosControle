<?php

namespace App\Helpers;

class UtilsNormalizarNumero
{
    public static function normalizarNumero(?string $valor): ?string
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }
        $valor = trim(str_replace('.', '', $valor));
        $valor = str_replace(',', '.', $valor);

        return $valor !== '' ? $valor : null;
    }
}

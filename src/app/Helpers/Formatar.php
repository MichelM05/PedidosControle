<?php

namespace App\Helpers;

use Carbon\CarbonInterface;

/**
 * Formatação de valores para exibição (padrão pt-BR). Retorna "—" quando não há valor.
 */
class Formatar
{
    public const VAZIO = '—';

    public static function numero(mixed $valor, int $casas = 2): string
    {
        return $valor === null || $valor === '' ? self::VAZIO : number_format((float) $valor, $casas, ',', '.');
    }

    public static function moeda(mixed $valor): string
    {
        return $valor === null || $valor === '' ? self::VAZIO : 'R$ '.self::numero($valor);
    }

    /** Preço unitário: até 4 casas, sem zeros à direita além das 2 primeiras (igual ao PDF). */
    public static function preco(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return self::VAZIO;
        }

        return preg_replace('/(\d,\d{2})0{1,2}$/', '$1', self::numero($valor, 4));
    }

    public static function quantidade(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return self::VAZIO;
        }

        return rtrim(rtrim(self::numero($valor, 4), '0'), ',');
    }

    public static function data(?CarbonInterface $data): string
    {
        return $data ? $data->format('d/m/Y') : self::VAZIO;
    }

    public static function texto(?string $texto): string
    {
        return $texto !== null && trim($texto) !== '' ? $texto : self::VAZIO;
    }

    /** Formata CNPJ de 14 dígitos (XX.XXX.XXX/XXXX-XX); outros valores voltam como estão. */
    public static function cnpj(?string $valor): string
    {
        if ($valor === null || trim($valor) === '') {
            return self::VAZIO;
        }
        $d = preg_replace('/\D/', '', $valor);

        return strlen($d) === 14
            ? sprintf('%s.%s.%s/%s-%s', substr($d, 0, 2), substr($d, 2, 3), substr($d, 5, 3), substr($d, 8, 4), substr($d, 12, 2))
            : $valor;
    }
}

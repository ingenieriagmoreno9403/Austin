<?php

namespace App\Support;

class HomologacionProveedor
{
    public const ESTADOS = [
        'prospecto' => 'Prospecto',
        'en_revision' => 'En revisión',
        'aprobado' => 'Aprobado',
        'condicionado' => 'Condicionado',
        'suspendido' => 'Suspendido',
        'bloqueado' => 'Bloqueado',
    ];

    public static function normalizar($valor): string
    {
        $estado = strtolower(trim((string) $valor));

        return array_key_exists($estado, self::ESTADOS) ? $estado : 'aprobado';
    }

    public static function etiqueta($valor): string
    {
        return self::ESTADOS[self::normalizar($valor)];
    }

    public static function puedeInvitar($valor): bool
    {
        return in_array(self::normalizar($valor), ['aprobado', 'condicionado'], true);
    }

    public static function puedeCotizar($valor): bool
    {
        return ! in_array(self::normalizar($valor), ['suspendido', 'bloqueado'], true);
    }
}

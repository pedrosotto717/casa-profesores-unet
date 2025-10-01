<?php declare(strict_types=1);

namespace App\Enums;

enum UserStatus: string
{
    case AprobacionPendiente = 'aprobacion_pendiente';
    case Solvente = 'solvente';
    case Insolvente = 'insolvente';
}
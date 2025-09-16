<?php declare(strict_types=1);

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Pendiente = 'pendiente';
    case Confirmada = 'confirmada';
    case Anulada = 'anulada';
}

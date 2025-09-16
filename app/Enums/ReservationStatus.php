<?php declare(strict_types=1);

namespace App\Enums;

enum ReservationStatus: string
{
    case Pendiente = 'pendiente';
    case Aprobada = 'aprobada';
    case Rechazada = 'rechazada';
    case Cancelada = 'cancelada';
    case Completada = 'completada';
    case Expirada = 'expirada';
}

<?php declare(strict_types=1);

namespace App\Enums;

enum InvitationStatus: string
{
    case Pendiente = 'pendiente';
    case Aceptada = 'aceptada';
    case Rechazada = 'rechazada';
    case Expirada = 'expirada';
    case Revocada = 'revocada';
}

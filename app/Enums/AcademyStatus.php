<?php declare(strict_types=1);

namespace App\Enums;

enum AcademyStatus: string
{
    case Activa = 'activa';
    case Cerrada = 'cerrada';
    case Cancelada = 'cancelada';
}

<?php declare(strict_types=1);

namespace App\Enums;

enum EstatusPago: string
{
    case Pagado = 'Pagado';
    case Pendiente = 'Pendiente';
    case Gratis = 'Gratis';
}
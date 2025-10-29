<?php declare(strict_types=1);

namespace App\Enums;

enum EstatusFactura: string
{
    case Pagado = 'Pagado';
    case Pendiente = 'Pendiente';
}


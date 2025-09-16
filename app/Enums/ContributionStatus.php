<?php declare(strict_types=1);

namespace App\Enums;

enum ContributionStatus: string
{
    case Pendiente = 'pendiente';
    case Pagado = 'pagado';
    case Vencido = 'vencido';
}

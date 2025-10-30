<?php declare(strict_types=1);

namespace App\Enums;

enum BeneficiarioEstatus: string
{
    case Pendiente = 'pendiente';
    case Aprobado = 'aprobado';
    case Inactivo = 'inactivo';
}

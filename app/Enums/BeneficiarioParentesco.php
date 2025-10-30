<?php declare(strict_types=1);

namespace App\Enums;

enum BeneficiarioParentesco: string
{
    case Conyuge = 'conyuge';
    case Hijo = 'hijo';
    case Madre = 'madre';
    case Padre = 'padre';
}

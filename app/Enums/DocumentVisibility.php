<?php declare(strict_types=1);

namespace App\Enums;

enum DocumentVisibility: string
{
    case Publico = 'publico';
    case Privado = 'privado';
    case Restringido = 'restringido';
}

<?php declare(strict_types=1);

namespace App\Enums;

enum DocumentVisibility: string
{
    case Publico = 'publico';
    case Interno = 'interno';
    case SoloAdmin = 'solo_admin';
}

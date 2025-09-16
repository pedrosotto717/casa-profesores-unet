<?php declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Docente = 'docente';
    case Administrador = 'administrador';
    case Obrero = 'obrero';
    case Estudiante = 'estudiante';
    case Invitado = 'invitado';
}

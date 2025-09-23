<?php

namespace Database\Seeders;

use App\Models\Academy;
use App\Models\User;
use Illuminate\Database\Seeder;

final class AcademiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Academies derived from database_structure.md §3.3
     */
    public function run(): void
    {
        $leadInstructor = User::first();

        $academies = [
            'Escuela de natación',
            'Karate',
            'Yoga',
            'Bailoterapia',
            'Nado sincronizado',
            'Tareas dirigidas',
        ];

        foreach ($academies as $academyName) {
            Academy::firstOrCreate(
                ['name' => $academyName],
                [
                    'description' => null, // TODO: Add academy descriptions
                    'lead_instructor_id' => $leadInstructor->id, // Assign instructors when available
                    'status' => 'activa', // Using enum value from database_structure.md
                ]
            );
        }

        // TODO: Create academy_schedules according to cpu_reglamento_negocio.md §4
        // - Piscina: 50% capacity reserved for swimming school
        // - Sauna: 15min slots, max 6 people, Mon-Fri 15:00-19:00
    }
}

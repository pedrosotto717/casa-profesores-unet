<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Academy;
use App\Models\AcademySchedule;
use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class AcademiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Academies derived from database_structure.md §3.3
     */
    public function run(): void
    {
        $leadInstructor = User::create([
            'name' => 'Instructor',
            'email' => 'instructor.test@unet.edu.ve',
            'password' => Hash::make('12345678'),
            'role' => UserRole::Instructor,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'sso_uid' => null,
            'status' => UserStatus::Solvente,
        ]);
    
        $academies = [
            'Escuela de natación' => [
                'description' => 'Academia de natación para todas las edades',
                'area_name' => 'Piscina Actualizada',
                'schedules' => [
                    // Lunes y Miércoles 4:00 PM - 6:00 PM
                    ['day_of_week' => 1, 'start_time' => '16:00', 'end_time' => '18:00', 'capacity' => 15],
                    ['day_of_week' => 3, 'start_time' => '16:00', 'end_time' => '18:00', 'capacity' => 15],
                ]
            ],
            'Karate' => [
                'description' => 'Arte marcial tradicional japonés',
                'area_name' => 'Cancha de usos múltiples',
                'schedules' => [
                    // Martes y Jueves 6:00 PM - 8:00 PM
                    ['day_of_week' => 2, 'start_time' => '18:00', 'end_time' => '20:00', 'capacity' => 20],
                    ['day_of_week' => 4, 'start_time' => '18:00', 'end_time' => '20:00', 'capacity' => 20],
                ]
            ],
            'Yoga' => [
                'description' => 'Práctica de yoga para bienestar físico y mental',
                'area_name' => 'Salón Primavera',
                'schedules' => [
                    // Lunes, Miércoles y Viernes 7:00 AM - 8:00 AM
                    ['day_of_week' => 1, 'start_time' => '07:00', 'end_time' => '08:00', 'capacity' => 25],
                    ['day_of_week' => 3, 'start_time' => '07:00', 'end_time' => '08:00', 'capacity' => 25],
                    ['day_of_week' => 5, 'start_time' => '07:00', 'end_time' => '08:00', 'capacity' => 25],
                ]
            ],
            'Bailoterapia' => [
                'description' => 'Terapia a través del baile y movimiento',
                'area_name' => 'Salón Pradera',
                'schedules' => [
                    // Martes y Jueves 7:00 PM - 8:30 PM
                    ['day_of_week' => 2, 'start_time' => '19:00', 'end_time' => '20:30', 'capacity' => 30],
                    ['day_of_week' => 4, 'start_time' => '19:00', 'end_time' => '20:30', 'capacity' => 30],
                ]
            ],
            'Nado sincronizado' => [
                'description' => 'Deporte acuático artístico y técnico',
                'area_name' => 'Piscina Actualizada',
                'schedules' => [
                    // Sábados 9:00 AM - 11:00 AM
                    ['day_of_week' => 6, 'start_time' => '09:00', 'end_time' => '11:00', 'capacity' => 12],
                ]
            ],
            'Tareas dirigidas' => [
                'description' => 'Apoyo académico para estudiantes',
                'area_name' => 'Aula de Informática',
                'schedules' => [
                    // Lunes a Viernes 3:00 PM - 5:00 PM
                    ['day_of_week' => 1, 'start_time' => '15:00', 'end_time' => '17:00', 'capacity' => 20],
                    ['day_of_week' => 2, 'start_time' => '15:00', 'end_time' => '17:00', 'capacity' => 20],
                    ['day_of_week' => 3, 'start_time' => '15:00', 'end_time' => '17:00', 'capacity' => 20],
                    ['day_of_week' => 4, 'start_time' => '15:00', 'end_time' => '17:00', 'capacity' => 20],
                    ['day_of_week' => 5, 'start_time' => '15:00', 'end_time' => '17:00', 'capacity' => 20],
                ]
            ],
        ];

        foreach ($academies as $academyName => $academyData) {
            $academy = Academy::firstOrCreate(
                ['name' => $academyName],
                [
                    'description' => $academyData['description'],
                    'lead_instructor_id' => $leadInstructor->id,
                    'status' => 'activa',
                ]
            );

            // Create academy schedules
            $this->createAcademySchedules($academy, $academyData);
        }
    }

    /**
     * Create schedules for an academy
     */
    private function createAcademySchedules(Academy $academy, array $academyData): void
    {
        // Find the area where the academy takes place
        $area = Area::where('name', $academyData['area_name'])->first();
        
        if (!$area) {
            // If area doesn't exist, skip creating schedules
            return;
        }

        // Clear existing schedules for this academy
        AcademySchedule::where('academy_id', $academy->id)->delete();

        // Create new schedules
        foreach ($academyData['schedules'] as $schedule) {
            AcademySchedule::create([
                'academy_id' => $academy->id,
                'area_id' => $area->id,
                'day_of_week' => $schedule['day_of_week'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
                'capacity' => $schedule['capacity'],
            ]);
        }
    }
}

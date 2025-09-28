<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\AreaSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class AreasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Areas derived from database_structure.md §3.1
     * Capacities derived from cpu_reglamento_negocio.md §5
     */
    public function run(): void
    {
        // Areas that are NOT reservable according to reglamento
        $nonReservableAreas = [
            'Salón Orquídea (Restaurant)', // acceso público, sin invitación - derivado de cpu_reglamento_negocio.md
            'Parque infantil',            // uso libre con reglas, no reserva - derivado de cpu_reglamento_negocio.md
        ];

        $areas = [
            'Piscina' => [
                'capacity' => null, // Variable capacity based on usage
                'description' => 'Área acuática para recreación y deportes. Requiere traje de baño y ducha previa.',
                'is_reservable' => true,
            ],
            'Salón Orquídea (Restaurant)' => [
                'capacity' => null, // Managed by concessionaire
                'description' => 'Restaurante con acceso público. Tiempo máximo de permanencia: 150 minutos.',
                'is_reservable' => false,
            ],
            'Salón Primavera' => [
                'capacity' => 100, // From cpu_reglamento_negocio.md §5
                'description' => 'Terraza techada para eventos y celebraciones.',
                'is_reservable' => true,
            ],
            'Salón Pradera' => [
                'capacity' => 150, // From cpu_reglamento_negocio.md §5
                'description' => 'Terraza techada para eventos y celebraciones.',
                'is_reservable' => true,
            ],
            'Auditorio Paramillo' => [
                'capacity' => 100, // From cpu_reglamento_negocio.md §5
                'description' => 'Auditorio para presentaciones y eventos formales.',
                'is_reservable' => true,
            ],
            'Kiosco Tuquerena' => [
                'capacity' => 30, // From cpu_reglamento_negocio.md §5
                'description' => 'Kiosco para eventos al aire libre.',
                'is_reservable' => true,
            ],
            'Kiosco Morusca' => [
                'capacity' => 30, // From cpu_reglamento_negocio.md §5
                'description' => 'Kiosco para eventos al aire libre.',
                'is_reservable' => true,
            ],
            'Sauna' => [
                'capacity' => 6, // From cpu_reglamento_negocio.md §4.1 (máx. 6 personas por turno)
                'description' => 'Área de relajación con turnos de 15 minutos. Exclusivo para docentes.',
                'is_reservable' => true,
            ],
            'Cancha de usos múltiples' => [
                'capacity' => null, // Variable based on sport
                'description' => 'Cancha deportiva para múltiples actividades.',
                'is_reservable' => true,
            ],
            'Cancha de bolas criollas' => [
                'capacity' => null, // Variable based on game
                'description' => 'Cancha especializada para bolas criollas.',
                'is_reservable' => true,
            ],
            'Parque infantil' => [
                'capacity' => null, // Variable based on supervision
                'description' => 'Área de recreación para menores de 10 años. Requiere supervisión adulta.',
                'is_reservable' => false,
            ],
            'Mesa de pool (billar)' => [
                'capacity' => 4, // Typical for billiards
                'description' => 'Mesa de billar para recreación.',
                'is_reservable' => true,
            ],
            'Mesa de ping pong' => [
                'capacity' => 4, // Typical for ping pong
                'description' => 'Mesa de ping pong para recreación.',
                'is_reservable' => true,
            ],
            'Peluquería (con previa cita)' => [
                'capacity' => 2, // Typical for salon
                'description' => 'Servicio de peluquería con cita previa.',
                'is_reservable' => true,
            ],
        ];

        foreach ($areas as $areaName => $areaData) {
            $area = Area::firstOrCreate(
                ['slug' => Str::slug($areaName)],
                [
                    'name' => $areaName,
                    'description' => $areaData['description'],
                    'capacity' => $areaData['capacity'],
                    'is_reservable' => $areaData['is_reservable'],
                    'is_active' => true,
                ]
            );

            // Create schedules for the area if it's reservable
            if ($areaData['is_reservable']) {
                $this->createAreaSchedules($area, $areaName);
            }
        }
    }

    /**
     * Create schedules for an area based on its type and reglamento requirements.
     */
    private function createAreaSchedules(Area $area, string $areaName): void
    {
        // Clear existing schedules for this area
        AreaSchedule::where('area_id', $area->id)->delete();

        $schedules = $this->getSchedulesForArea($areaName);

        foreach ($schedules as $schedule) {
            AreaSchedule::create([
                'area_id' => $area->id,
                'day_of_week' => $schedule['day_of_week'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
                'is_open' => $schedule['is_open'],
            ]);
        }
    }

    /**
     * Get schedules for specific areas based on reglamento.
     */
    private function getSchedulesForArea(string $areaName): array
    {
        return match ($areaName) {
            'Sauna' => [
                // Lunes a Viernes 3:00 PM - 7:00 PM (según reglamento §4.1)
                ['day_of_week' => 1, 'start_time' => '15:00', 'end_time' => '19:00', 'is_open' => true], // Lunes
                ['day_of_week' => 2, 'start_time' => '15:00', 'end_time' => '19:00', 'is_open' => true], // Martes
                ['day_of_week' => 3, 'start_time' => '15:00', 'end_time' => '19:00', 'is_open' => true], // Miércoles
                ['day_of_week' => 4, 'start_time' => '15:00', 'end_time' => '19:00', 'is_open' => true], // Jueves
                ['day_of_week' => 5, 'start_time' => '15:00', 'end_time' => '19:00', 'is_open' => true], // Viernes
            ],
            'Piscina' => [
                // Martes a Domingo 9:00 AM - 5:00 PM (según reglamento §4.2)
                ['day_of_week' => 2, 'start_time' => '09:00', 'end_time' => '17:00', 'is_open' => true], // Martes
                ['day_of_week' => 3, 'start_time' => '09:00', 'end_time' => '17:00', 'is_open' => true], // Miércoles
                ['day_of_week' => 4, 'start_time' => '09:00', 'end_time' => '17:00', 'is_open' => true], // Jueves
                ['day_of_week' => 5, 'start_time' => '09:00', 'end_time' => '17:00', 'is_open' => true], // Viernes
                ['day_of_week' => 6, 'start_time' => '09:00', 'end_time' => '17:00', 'is_open' => true], // Sábado
                ['day_of_week' => 7, 'start_time' => '09:00', 'end_time' => '17:00', 'is_open' => true], // Domingo
            ],
            'Salón Primavera', 'Salón Pradera', 'Auditorio Paramillo' => [
                // Salones para eventos: 10:00 AM - 12:00 AM (según reglamento §5)
                ['day_of_week' => 1, 'start_time' => '10:00', 'end_time' => '00:00', 'is_open' => true], // Lunes
                ['day_of_week' => 2, 'start_time' => '10:00', 'end_time' => '00:00', 'is_open' => true], // Martes
                ['day_of_week' => 3, 'start_time' => '10:00', 'end_time' => '00:00', 'is_open' => true], // Miércoles
                ['day_of_week' => 4, 'start_time' => '10:00', 'end_time' => '00:00', 'is_open' => true], // Jueves
                ['day_of_week' => 5, 'start_time' => '10:00', 'end_time' => '00:00', 'is_open' => true], // Viernes
                ['day_of_week' => 6, 'start_time' => '10:00', 'end_time' => '00:00', 'is_open' => true], // Sábado
                ['day_of_week' => 7, 'start_time' => '10:00', 'end_time' => '00:00', 'is_open' => true], // Domingo
            ],
            'Kiosco Tuquerena', 'Kiosco Morusca' => [
                // Kioscos: 10:00 AM - 12:00 AM
                ['day_of_week' => 1, 'start_time' => '10:00', 'end_time' => '00:00', 'is_open' => true], // Lunes
                ['day_of_week' => 2, 'start_time' => '10:00', 'end_time' => '00:00', 'is_open' => true], // Martes
                ['day_of_week' => 3, 'start_time' => '10:00', 'end_time' => '00:00', 'is_open' => true], // Miércoles
                ['day_of_week' => 4, 'start_time' => '10:00', 'end_time' => '00:00', 'is_open' => true], // Jueves
                ['day_of_week' => 5, 'start_time' => '10:00', 'end_time' => '00:00', 'is_open' => true], // Viernes
                ['day_of_week' => 6, 'start_time' => '10:00', 'end_time' => '00:00', 'is_open' => true], // Sábado
                ['day_of_week' => 7, 'start_time' => '10:00', 'end_time' => '00:00', 'is_open' => true], // Domingo
            ],
            'Cancha de usos múltiples', 'Cancha de bolas criollas' => [
                // Canchas deportivas: 6:00 AM - 10:00 PM
                ['day_of_week' => 1, 'start_time' => '06:00', 'end_time' => '22:00', 'is_open' => true], // Lunes
                ['day_of_week' => 2, 'start_time' => '06:00', 'end_time' => '22:00', 'is_open' => true], // Martes
                ['day_of_week' => 3, 'start_time' => '06:00', 'end_time' => '22:00', 'is_open' => true], // Miércoles
                ['day_of_week' => 4, 'start_time' => '06:00', 'end_time' => '22:00', 'is_open' => true], // Jueves
                ['day_of_week' => 5, 'start_time' => '06:00', 'end_time' => '22:00', 'is_open' => true], // Viernes
                ['day_of_week' => 6, 'start_time' => '06:00', 'end_time' => '22:00', 'is_open' => true], // Sábado
                ['day_of_week' => 7, 'start_time' => '06:00', 'end_time' => '22:00', 'is_open' => true], // Domingo
            ],
            'Mesa de pool (billar)', 'Mesa de ping pong' => [
                // Mesas de recreación: 8:00 AM - 11:00 PM
                ['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '23:00', 'is_open' => true], // Lunes
                ['day_of_week' => 2, 'start_time' => '08:00', 'end_time' => '23:00', 'is_open' => true], // Martes
                ['day_of_week' => 3, 'start_time' => '08:00', 'end_time' => '23:00', 'is_open' => true], // Miércoles
                ['day_of_week' => 4, 'start_time' => '08:00', 'end_time' => '23:00', 'is_open' => true], // Jueves
                ['day_of_week' => 5, 'start_time' => '08:00', 'end_time' => '23:00', 'is_open' => true], // Viernes
                ['day_of_week' => 6, 'start_time' => '08:00', 'end_time' => '23:00', 'is_open' => true], // Sábado
                ['day_of_week' => 7, 'start_time' => '08:00', 'end_time' => '23:00', 'is_open' => true], // Domingo
            ],
            'Peluquería (con previa cita)' => [
                // Peluquería: 8:00 AM - 6:00 PM (con cita previa)
                ['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '18:00', 'is_open' => true], // Lunes
                ['day_of_week' => 2, 'start_time' => '08:00', 'end_time' => '18:00', 'is_open' => true], // Martes
                ['day_of_week' => 3, 'start_time' => '08:00', 'end_time' => '18:00', 'is_open' => true], // Miércoles
                ['day_of_week' => 4, 'start_time' => '08:00', 'end_time' => '18:00', 'is_open' => true], // Jueves
                ['day_of_week' => 5, 'start_time' => '08:00', 'end_time' => '18:00', 'is_open' => true], // Viernes
                ['day_of_week' => 6, 'start_time' => '08:00', 'end_time' => '18:00', 'is_open' => true], // Sábado
            ],
            default => [
                // Horario general por defecto: 8:00 AM - 10:00 PM
                ['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '22:00', 'is_open' => true], // Lunes
                ['day_of_week' => 2, 'start_time' => '08:00', 'end_time' => '22:00', 'is_open' => true], // Martes
                ['day_of_week' => 3, 'start_time' => '08:00', 'end_time' => '22:00', 'is_open' => true], // Miércoles
                ['day_of_week' => 4, 'start_time' => '08:00', 'end_time' => '22:00', 'is_open' => true], // Jueves
                ['day_of_week' => 5, 'start_time' => '08:00', 'end_time' => '22:00', 'is_open' => true], // Viernes
                ['day_of_week' => 6, 'start_time' => '08:00', 'end_time' => '22:00', 'is_open' => true], // Sábado
                ['day_of_week' => 7, 'start_time' => '08:00', 'end_time' => '22:00', 'is_open' => true], // Domingo
            ],
        };
    }
}

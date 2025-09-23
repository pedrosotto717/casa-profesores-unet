<?php

namespace Database\Seeders;

use App\Models\Area;
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
        $areas = [
            'Piscina' => [
                'capacity' => null, // Variable capacity based on usage
                'description' => 'Área acuática para recreación y deportes. Requiere traje de baño y ducha previa.',
            ],
            'Salón Orquídea (Restaurant)' => [
                'capacity' => null, // Managed by concessionaire
                'description' => 'Restaurante con acceso público. Tiempo máximo de permanencia: 150 minutos.',
            ],
            'Salón Primavera' => [
                'capacity' => 100, // From cpu_reglamento_negocio.md §5
                'description' => 'Terraza techada para eventos y celebraciones.',
            ],
            'Salón Pradera' => [
                'capacity' => 150, // From cpu_reglamento_negocio.md §5
                'description' => 'Terraza techada para eventos y celebraciones.',
            ],
            'Auditorio Paramillo' => [
                'capacity' => 100, // From cpu_reglamento_negocio.md §5
                'description' => 'Auditorio para presentaciones y eventos formales.',
            ],
            'Kiosco Tuquerena' => [
                'capacity' => 30, // From cpu_reglamento_negocio.md §5
                'description' => 'Kiosco para eventos al aire libre.',
            ],
            'Kiosco Morusca' => [
                'capacity' => 30, // From cpu_reglamento_negocio.md §5
                'description' => 'Kiosco para eventos al aire libre.',
            ],
            'Sauna' => [
                'capacity' => 6, // From cpu_reglamento_negocio.md §4.1 (máx. 6 personas por turno)
                'description' => 'Área de relajación con turnos de 15 minutos. Exclusivo para docentes.',
            ],
            'Cancha de usos múltiples' => [
                'capacity' => null, // Variable based on sport
                'description' => 'Cancha deportiva para múltiples actividades.',
            ],
            'Cancha de bolas criollas' => [
                'capacity' => null, // Variable based on game
                'description' => 'Cancha especializada para bolas criollas.',
            ],
            'Parque infantil' => [
                'capacity' => null, // Variable based on supervision
                'description' => 'Área de recreación para menores de 10 años. Requiere supervisión adulta.',
            ],
            'Mesa de pool (billar)' => [
                'capacity' => 4, // Typical for billiards
                'description' => 'Mesa de billar para recreación.',
            ],
            'Mesa de ping pong' => [
                'capacity' => 4, // Typical for ping pong
                'description' => 'Mesa de ping pong para recreación.',
            ],
            'Peluquería (con previa cita)' => [
                'capacity' => 2, // Typical for salon
                'description' => 'Servicio de peluquería con cita previa.',
            ],
        ];

        foreach ($areas as $areaName => $areaData) {
            Area::firstOrCreate(
                ['slug' => Str::slug($areaName)],
                [
                    'name' => $areaName,
                    'description' => $areaData['description'],
                    'capacity' => $areaData['capacity'],
                    'hourly_rate' => null, // TODO: Define rates per area
                    'is_active' => true,
                ]
            );
        }
    }
}

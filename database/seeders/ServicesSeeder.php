<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Service;
use Illuminate\Database\Seeder;

final class ServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Services derived from cpu_reglamento_negocio.md §2 and database_structure.md §3.2
     */
    public function run(): void
    {
        // Areas that are NOT reservable according to reglamento
        $nonReservableAreas = [
            'Salón Orquídea (Restaurant)', // acceso público, sin invitación - derivado de cpu_reglamento_negocio.md
            'Parque infantil',            // uso libre con reglas, no reserva - derivado de cpu_reglamento_negocio.md
        ];

        // Get all areas and filter out non-reservable ones
        $allAreas = Area::all();
        $reservableAreas = $allAreas->filter(function ($area) use ($nonReservableAreas) {
            return !in_array($area->name, $nonReservableAreas);
        });

        // Create "Reserva [Área]" service for each reservable area
        foreach ($reservableAreas as $area) {
            Service::firstOrCreate(
                [
                    'area_id' => $area->id,
                    'name' => "Reserva {$area->name}",
                ],
                [
                    'description' => null, // TODO: Add service descriptions
                    'requires_reservation' => true,
                    'hourly_rate' => null, // TODO: Define rates per service (overrides area rate)
                    'is_active' => true,
                ]
            );
        }
    }
}

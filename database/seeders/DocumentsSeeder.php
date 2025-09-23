<?php

namespace Database\Seeders;

use App\Models\Document;
use Illuminate\Database\Seeder;

final class DocumentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Documents derived from database_structure.md §3.4 and cpu_reglamento_negocio.md
     */
    public function run(): void
    {
        // Create institutional document placeholder
        Document::firstOrCreate(
            ['title' => 'Reglamento CPU – 2017 (PDF)'],
            [
                'file_url' => 'TBD', // TODO: Update when Storage integration is implemented
                'visibility' => 'publico', // Using enum value from database_structure.md
                'uploaded_by' => 1, // Set to admin user ID
                'description' => 'Reglamento de Uso de la Casa del Profesor Universitario - Febrero 2017',
            ]
        );

        // TODO: Add more institutional documents as needed
        // - Actas de reuniones
        // - Instructivos operativos
        // - Políticas de uso
    }
}

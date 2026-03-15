<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mesa;

class MesaSeeder extends Seeder
{
    public function run(): void
    {
        // Crea 12 mesas (1..12)
        for ($i = 1; $i <= 32; $i++) {
            Mesa::updateOrCreate(
                ['numero' => (string) $i],
                ['capacidad' => 4, 'estado' => 'libre']
            );
        }
    }
}

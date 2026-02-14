<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Mesa;

class MesaSeeder extends Seeder
{
    public function run(): void
    {
        // Crea 20 mesas (1..20)
        for ($i = 1; $i <= 20; $i++) {
            Mesa::updateOrCreate(
                ['numero' => (string) $i],
                ['capacidad' => 4, 'estado' => 'libre']
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Mesero;

class MeseroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Mesero::create([
            'nombre' => 'Carlos',
            'pin' => '1234',
            'activo' => true,
        ]);
    }
}

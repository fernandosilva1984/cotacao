<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Marca;

class MarcaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Marca::create([
            'nome' => 'Marca A',
            'id_empresa' => 1,
            'status' => true,
        ]);
        Marca::create([
            'nome' => 'Marca B',
            'id_empresa' => 1,
            'status' => true,
        ]);
        Marca::create([
            'nome' => 'Marca C',
            'id_empresa' => 1,
            'status' => true,
        ]);
    }
}
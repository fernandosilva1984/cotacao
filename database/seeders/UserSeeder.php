<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'Administrador',
            'email' => 'adm@adm.com',
            'password' => bcrypt('123456'),
            'id_empresa' => 1,
            'status' => 1,
            'is_master' => 1,
            //'profile' => 'Administrador',
        ]);
    }
}
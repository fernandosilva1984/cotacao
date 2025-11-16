<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Empresa;

class EmpresaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Empresa::create([
        'razao_social' => 'Sistema de Cotações LTDA',
        'nome_fantasia' => 'Sistema de Cotações',
        'endereco' => 'Rua Exemplo, 123',
        'bairro'   => 'Centro',
        'cidade' => 'São Paulo',
        'cnpj' => '12.345.678/0001-90',
        'contato' => '(11) 98765-4321',
        'email' => 'contato@meusinvestimentos.online',
        'status' => true,
        ]);
    }
}
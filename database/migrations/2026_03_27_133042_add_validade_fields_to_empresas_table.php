<?php
// database/migrations/xxxx_xx_xx_add_validade_fields_to_empresas_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->date('data_ativacao')->nullable()->after('status');
            $table->date('data_validade')->nullable()->after('data_ativacao');
            $table->enum('plano', ['mensal', 'trimestral', 'semestral', 'anual'])->default('mensal')->after('data_validade');
            $table->decimal('valor_plano', 10, 2)->default(0)->after('plano');
            $table->string('codigo_assinatura')->nullable()->after('valor_plano');
            $table->text('observacoes_assinatura')->nullable()->after('codigo_assinatura');
        });
    }

    public function down()
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn([
                'data_ativacao',
                'data_validade',
                'plano',
                'valor_plano',
                'codigo_assinatura',
                'observacoes_assinatura'
            ]);
        });
    }
};
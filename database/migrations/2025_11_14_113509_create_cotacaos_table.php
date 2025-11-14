<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cotacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_empresa')->constrained('empresas');
            $table->foreignId('id_usuario')->constrained('users');
            $table->date('data');
            $table->string('numero')->unique();
            $table->text('observacao')->nullable();
            $table->decimal('valor_total', 15, 2)->default(0);
            $table->enum('status', ['pendente', 'enviada', 'respondida', 'finalizada', 'cancelada'])->default('pendente');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cotacoes');
    }
};
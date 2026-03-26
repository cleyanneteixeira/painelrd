<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        //draw_date agora pode ser nulo (sorteio quando todas as cotas forem vendidas)
        Schema::table('raffles', function (Blueprint $table) {
            $table->datetime('draw_date')->nullable()->change();
        });

        //Tabela de prêmios múltiplos
        Schema::create('raffle_prizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('raffle_id')->constrained()->cascadeOnDelete();
            $table->integer('position')->default(1);
            //1 = 1º lugar, 2 = 2º lugar, etc.
            $table->string('title');
            //Ex: "Pacote para os Lençóis Maranhenses"
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->unsignedBigInteger('winner_ticket_id')->nullable();
            $table->timestamps();
        });

        //Tabela de ganhadores anteriores (depoimentos/histórico)
        Schema::create('raffle_winners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('prize');
            //Descrição do prêmio ganho
            $table->string('photo')->nullable();
            $table->text('testimonial')->nullable();
            $table->date('draw_date');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('raffle_winners');
        Schema::dropIfExists('raffle_prizes');
    }
};

<?php

namespace Database\Seeders;

use App\Models\RaffleWinner;
use Illuminate\Database\Seeder;

class RaffleWinnersSeeder extends Seeder
{
    public function run(): void
    {
        $winners = [
            [
                'name'        => 'Ana Beatriz Costa',
                'city'        => 'São Luís, MA',
                'prize'       => 'Pacote 3 dias nos Lençóis Maranhenses',
                'testimonial' => 'Nunca imaginei que ia ganhar! Foi a melhor viagem da minha vida. Os Lençóis são um paraíso que eu nunca vou esquecer.',
                'draw_date'   => '2024-12-15',
                'active'      => true,
            ],
            [
                'name'        => 'Carlos Mendes',
                'city'        => 'Imperatriz, MA',
                'prize'       => 'Passeio de lancha pela Baía de São Marcos',
                'testimonial' => 'Incrível demais! O passeio foi espetacular, recomendo para todo mundo comprar as cotas. Vale muito a pena!',
                'draw_date'   => '2025-02-10',
                'active'      => true,
            ],
            [
                'name'        => 'Fernanda Oliveira',
                'city'        => 'Caxias, MA',
                'prize'       => 'Hospedagem 2 noites em Barreirinhas',
                'testimonial' => 'Recebi o voucher e viajei com minha família. O hotel era lindo e o atendimento impecável. Obrigada Reservar Destinos!',
                'draw_date'   => '2025-05-20',
                'active'      => true,
            ],
        ];

        foreach ($winners as $winner) {
            RaffleWinner::firstOrCreate(['name' => $winner['name'], 'draw_date' => $winner['draw_date']], $winner);
        }
    }
}

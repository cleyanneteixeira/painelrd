<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RafflePrize extends Model
{
    protected $fillable = [
        'raffle_id', 'position', 'title', 'description', 'image', 'winner_ticket_id',
    ];

    public function raffle(): BelongsTo
    {
        return $this->belongsTo(Raffle::class);
    }

    public function winnerTicket(): BelongsTo
    {
        return $this->belongsTo(RaffleTicket::class, 'winner_ticket_id');
    }

    public function getPositionLabelAttribute(): string
    {
        return match($this->position) {
            1 => '🥇 1º Lugar',
            2 => '🥈 2º Lugar',
            3 => '🥉 3º Lugar',
            default => $this->position . 'º Lugar',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Raffle extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'image',
        'ticket_price',
        'total_tickets',
        'draw_date',
        'status',
        'winner_ticket_id',
    ];

    protected $casts = [
        'ticket_price'  => 'decimal:2',
        'total_tickets' => 'integer',
        'draw_date'     => 'datetime',
        //draw_date pode ser null = sortear quando todas as cotas forem vendidas
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(RaffleTicket::class);
    }

    public function soldTickets(): HasMany
    {
        return $this->hasMany(RaffleTicket::class)->where('payment_status', 'paid');
    }

    public function winnerTicket(): BelongsTo
    {
        return $this->belongsTo(RaffleTicket::class, 'winner_ticket_id');
    }

    public function getSoldCountAttribute(): int
    {
        return $this->soldTickets()->count();
    }

    public function getAvailableCountAttribute(): int
    {
        return $this->total_tickets - $this->sold_count;
    }

    public function getProgressPercentageAttribute(): int
    {
        if ($this->total_tickets === 0) {
            return 0;
        }

        return (int) round(($this->sold_count / $this->total_tickets) * 100);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isFinished(): bool
    {
        return $this->status === 'finished';
    }

    public function prizes(): HasMany
    {
        return $this->hasMany(RafflePrize::class)->orderBy('position');
    }

    public function getDrawDateLabelAttribute(): string
    {
        if (!$this->draw_date) {
            return 'Quando todas as cotas forem vendidas';
        }
        return $this->draw_date->format('d/m/Y \à\s H:i');
    }

    public function isAutoDrawWhenSoldOut(): bool
    {
        return $this->draw_date === null;
    }
}

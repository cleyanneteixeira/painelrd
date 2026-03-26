<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RaffleWinner extends Model
{
    protected $fillable = [
        'name', 'city', 'prize', 'photo', 'testimonial', 'draw_date', 'active',
    ];

    protected $casts = [
        'draw_date' => 'date',
        'active'    => 'boolean',
    ];

    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo) return null;
        if (str_starts_with($this->photo, 'http')) return $this->photo;
        return asset('storage/' . $this->photo);
    }
}

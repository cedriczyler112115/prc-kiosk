<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'name',
        'prefix',
        'description',
        'is_active',
    ];

    public function queueTickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }
}

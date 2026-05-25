<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Priority extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'priority_level',
        'is_active',
    ];

    protected $casts = [
        'priority_level' => 'integer',
        'is_active' => 'boolean',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'workflow_order',
        'is_active',
        'transfer_allowed',
        'priority_enabled',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'workflow_order' => 'integer',
        'is_active' => 'boolean',
        'transfer_allowed' => 'boolean',
        'priority_enabled' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}

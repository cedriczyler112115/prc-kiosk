<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueLog extends Model
{
    protected $table = 'queue_logs';

    public $timestamps = false; // Only created_at exists, updated_at does not

    protected $fillable = [
        'queue_id',
        'action',
        'old_status',
        'new_status',
        'performed_by',
        'remarks',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'remarks' => 'array', // Sometimes JSON, sometimes text - but let's assume text if not valid JSON, or handle in accessor
    ];

    protected $appends = ['level'];

    public function queue(): BelongsTo
    {
        return $this->belongsTo(QueueTicket::class, 'queue_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // Helper to get a "level" based on action or status
    public function getLevelAttribute()
    {
        if (str_contains(strtolower($this->action), 'error') || str_contains(strtolower($this->action), 'fail')) {
            return 'error';
        }
        if (str_contains(strtolower($this->action), 'cancel')) {
            return 'warning';
        }

        return 'info';
    }
}

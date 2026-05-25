<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AccessLevel extends Model
{
    use SoftDeletes;

    protected $table = 'access_level_library';

    protected $fillable = [
        'code',
        'name',
        'description',
        'hierarchy',
        'created_by',
        'updated_by',
    ];

    public const CODE_ADMIN = 'ADMIN';
    public const CODE_STAFF = 'STAFF';
    public const CODE_GUARD = 'GUARD';
}

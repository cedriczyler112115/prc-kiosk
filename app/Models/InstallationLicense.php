<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallationLicense extends Model
{
    protected $table = 'installation_licenses';

    protected $fillable = [
        'token',
        'installed_by',
        'installed_at',
        'device_mac',
        'device_hash',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
    ];
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QrSession extends Model
{
    use HasFactory;

    protected $table = 'qr_sessions';

    protected $fillable = [
        'token', 'jadwal_id', 'tanggal', 'expires_at', 'created_by'
    ];
}

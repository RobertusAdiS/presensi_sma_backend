<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guru extends Model
{
    use HasFactory;

    protected $table = 'guru';

    protected $fillable = [
        'user_id',
        'nip',
        'no_telp',
        'alamat',
        'jenis_kelamin',
        'tanggal_lahir',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Siswa extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $table = 'siswa';

    protected $fillable = [
        'nisn',
        'nama_lengkap',
        'jenis_kelamin',
        'tanggal_lahir',
        'no_telp',
        'alamat',
        'kelas_id',
        'is_active',
    ];

    protected $hidden = [
        // no sensitive attributes hashed here
    ];

    public function kelas()
    {
        return $this->belongsTo(Kelas::class);
    }
}

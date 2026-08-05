<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\JadwalPelajaran;
use Illuminate\Http\Request;

class JadwalController extends Controller
{
    public function index()
    {
        $list = JadwalPelajaran::with(['mataPelajaran','kelas','guru.user'])->get()->map(function($j){
            return [
                'id' => $j->id,
                'kelas_id' => $j->kelas_id,
                'nama_kelas' => $j->kelas->nama_kelas ?? null,
                'mata_pelajaran' => $j->mataPelajaran->nama_mapel ?? null,
                'guru_nama' => $j->guru->user->nama_lengkap ?? null,
                'hari' => $j->hari,
                'jam_mulai' => $j->jam_mulai,
                'jam_selesai' => $j->jam_selesai,
            ];
        });
        return response()->json(['status' => 'success', 'data' => $list]);
    }
}

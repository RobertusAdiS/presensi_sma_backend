<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MataPelajaran;
use Illuminate\Http\Request;

class MataPelajaranController extends Controller
{
    public function index()
    {
        $list = MataPelajaran::with('guru.user')->orderBy('nama_mapel')->get()->map(function($m){
            return array_merge($m->toArray(), ['guru_nama' => $m->guru->user->nama_lengkap ?? null]);
        });
        return response()->json(['status' => 'success', 'data' => $list]);
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Guru;
use Illuminate\Http\Request;

class GuruController extends Controller
{
    public function index()
    {
        $list = Guru::with('user')->get()->map(function($g){
            return [
                'id' => $g->id,
                'nip' => $g->nip,
                'nama_lengkap' => $g->user->nama_lengkap ?? null,
                'email' => $g->user->email ?? null,
                'no_telp' => $g->no_telp,
            ];
        });
        return response()->json(['status' => 'success', 'data' => $list]);
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Siswa;

class StudentAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string',
            'nisn' => 'required|string',
        ]);

        $nama = $request->input('nama_lengkap');
        $nisn = $request->input('nisn');

        $siswa = Siswa::whereRaw('LOWER(TRIM(nama_lengkap)) = LOWER(TRIM(?))', [$nama])
            ->where('nisn', $nisn)
            ->where('is_active', 1)
            ->first();

        if (! $siswa) {
            return response()->json(['message' => 'Kombinasi Nama Lengkap dan NISN salah atau akun tidak aktif'], 401);
        }

        $token = $siswa->createToken('student-token')->plainTextToken;

        return response()->json(['student' => $siswa, 'token' => $token]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        // stats: count per status for current month
        $bulan = date('m');
        $tahun = date('Y');
        $start = "$tahun-$bulan-01";
        $end = date('Y-m-t', strtotime($start));

        $stats = [
            'Hadir' => Absensi::where('siswa_id', $user->id)->where('status','Hadir')->whereBetween('tanggal', [$start,$end])->count(),
            'Izin' => Absensi::where('siswa_id', $user->id)->where('status','Izin')->whereBetween('tanggal', [$start,$end])->count(),
            'Sakit' => Absensi::where('siswa_id', $user->id)->where('status','Sakit')->whereBetween('tanggal', [$start,$end])->count(),
            'Alfa' => Absensi::where('siswa_id', $user->id)->where('status','Alfa')->whereBetween('tanggal', [$start,$end])->count(),
        ];

        $history = Absensi::with(['jadwal.mataPelajaran'])
            ->where('siswa_id', $user->id)
            ->orderBy('tanggal', 'desc')
            ->limit(20)
            ->get()
            ->map(function($a){
                return [
                    'tanggal' => $a->tanggal,
                    'tanggal_indo' => formatDateIndonesia($a->tanggal),
                    'nama_mapel' => $a->jadwal->mataPelajaran->nama_mapel ?? null,
                    'status' => $a->status,
                    'keterangan' => $a->keterangan,
                ];
            });

        return response()->json(['student' => $user, 'stats' => $stats, 'history' => $history]);
    }
}

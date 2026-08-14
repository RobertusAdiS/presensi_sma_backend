<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Siswa;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\Absensi;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $totals = [
            'total_siswa' => Siswa::count(),
            'total_guru' => Guru::count(),
            'total_kelas' => Kelas::count(),
            'total_mapel' => MataPelajaran::count(),
        ];

        $today = date('Y-m-d');
        $today_attendance = [
            'Hadir' => Absensi::where('tanggal', $today)->where('status','Hadir')->count(),
            'Izin' => Absensi::where('tanggal', $today)->where('status','Izin')->count(),
            'Sakit' => Absensi::where('tanggal', $today)->where('status','Sakit')->count(),
            'Alfa' => Absensi::where('tanggal', $today)->where('status','Alfa')->count(),
        ];

        return response()->json(['status' => 'success', 'data' => ['totals' => $totals, 'today_attendance' => $today_attendance, 'today_date' => formatDateIndonesia($today)]]);
    }
}

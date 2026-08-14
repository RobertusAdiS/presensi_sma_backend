<?php
use App\Models\Guru;
use App\Models\Siswa;
use App\Models\Kelas;
use App\Models\MataPelajaran;
use App\Models\JadwalPelajaran;
use App\Models\Absensi;
use App\Models\RekapAbsensi;

function jsonResponse($status, $data = null, $message = '', $statusCode = 200) {
    return response()->json([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ], $statusCode);
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function formatDateIndonesia($date) {
    $hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    $bulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $timestamp = strtotime($date);
    if (!$timestamp) return $date;
    $day = $hari[date('w', $timestamp)];
    $day_num = date('d', $timestamp);
    $month = $bulan[date('n', $timestamp) - 1];
    $year = date('Y', $timestamp);
    return "$day, $day_num $month $year";
}

function getAllGuru() {
    return Guru::with('user')
        ->whereHas('user', function($q){ $q->where('is_active',1); })
        ->get()
        ->map(function($g){
            return [
                'id' => $g->id,
                'nama_lengkap' => $g->user->nama_lengkap ?? null,
                'nip' => $g->nip,
                'no_telp' => $g->no_telp,
                'alamat' => $g->alamat,
                'jenis_kelamin' => $g->jenis_kelamin,
                'email' => $g->user->email ?? null,
                'username' => $g->user->username ?? null,
                'is_active' => $g->user->is_active ?? null,
            ];
        })->toArray();
}

function getSiswaByKelas($kelas_id) {
    return Siswa::with('kelas')
        ->where('kelas_id', $kelas_id)
        ->where('is_active', 1)
        ->orderBy('nama_lengkap')
        ->get()
        ->toArray();
}

function getAllKelas() {
    return Kelas::with(['guru.user'])
        ->orderBy('tingkat')
        ->orderBy('nama_kelas')
        ->get()
        ->map(function($k){
            return array_merge($k->toArray(), ['guru_nama' => $k->guru->user->nama_lengkap ?? null]);
        })->toArray();
}

function getAllMataPelajaran() {
    return MataPelajaran::with(['guru.user'])
        ->orderBy('nama_mapel')
        ->get()
        ->map(function($m){
            return array_merge($m->toArray(), ['guru_nama' => $m->guru->user->nama_lengkap ?? null]);
        })->toArray();
}

function getJadwalByKelas($kelas_id) {
    $order = "FIELD(hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu')";
    return JadwalPelajaran::with(['mataPelajaran','kelas','guru.user'])
        ->where('kelas_id', $kelas_id)
        ->orderByRaw($order)
        ->orderBy('jam_mulai')
        ->get()
        ->toArray();
}

function updateRekapAbsensi($siswa_id, $bulan, $tahun) {
    $start_date = sprintf('%04d-%02d-01', $tahun, $bulan);
    $end_date = date('Y-m-t', strtotime($start_date));
    $hadir = Absensi::where('siswa_id', $siswa_id)->where('status','Hadir')->whereBetween('tanggal', [$start_date,$end_date])->count();
    $izin = Absensi::where('siswa_id', $siswa_id)->where('status','Izin')->whereBetween('tanggal', [$start_date,$end_date])->count();
    $sakit = Absensi::where('siswa_id', $siswa_id)->where('status','Sakit')->whereBetween('tanggal', [$start_date,$end_date])->count();
    $alfa = Absensi::where('siswa_id', $siswa_id)->where('status','Alfa')->whereBetween('tanggal', [$start_date,$end_date])->count();

    $rekap = RekapAbsensi::firstOrNew(['siswa_id' => $siswa_id, 'bulan' => $bulan, 'tahun' => $tahun]);
    $rekap->total_hadir = $hadir;
    $rekap->total_izin = $izin;
    $rekap->total_sakit = $sakit;
    $rekap->total_alfa = $alfa;
    $rekap->save();
    return true;
}

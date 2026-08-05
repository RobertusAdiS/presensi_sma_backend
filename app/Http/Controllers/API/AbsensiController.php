<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Absensi;
use App\Models\JadwalPelajaran;
use App\Models\Siswa;
use App\Models\Kelas;

class AbsensiController extends Controller
{
    public function index()
    {
        $query = Absensi::with('siswa','jadwal');
        if (request()->has('jadwal_id')) {
            $query->where('jadwal_id', request('jadwal_id'));
        }
        if (request()->has('tanggal')) {
            $query->where('tanggal', request('tanggal'));
        }
        return response()->json(['status' => 'success', 'data' => $query->get()]);
    }

    public function show($id)
    {
        return response()->json(Absensi::with('siswa','jadwal')->findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'siswa_id' => 'required|integer|exists:siswa,id',
            'jadwal_id' => 'required|integer|exists:jadwal_pelajaran,id',
            'tanggal' => 'required|date',
            'status' => 'required|in:Hadir,Izin,Sakit,Alfa',
            'keterangan' => 'nullable|string',
            'dicatat_oleh' => 'required|string|max:100',
        ]);
        $absensi = Absensi::create($data);
        return response()->json($absensi, 201);
    }

    public function update(Request $request, $id)
    {
        $absensi = Absensi::findOrFail($id);
        $data = $request->validate([
            'tanggal' => 'sometimes|required|date',
            'status' => 'sometimes|required|in:Hadir,Izin,Sakit,Alfa',
            'keterangan' => 'nullable|string',
            'dicatat_oleh' => 'sometimes|required|string|max:100',
        ]);
        $absensi->update($data);
        return response()->json($absensi);
    }

    public function destroy($id)
    {
        $absensi = Absensi::findOrFail($id);
        $absensi->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function input(Request $request)
    {
        $jadwal_id = $request->query('jadwal_id');
        $tanggal = $request->query('tanggal');

        $jadwal = JadwalPelajaran::with('kelas','mataPelajaran')->findOrFail($jadwal_id);

        $siswaList = Siswa::where('kelas_id', $jadwal->kelas_id)->get();

        $attendees = $siswaList->map(function($s) use ($jadwal_id, $tanggal) {
            $abs = Absensi::where('jadwal_id', $jadwal_id)
                ->where('tanggal', $tanggal)
                ->where('siswa_id', $s->id)
                ->first();

            return [
                'siswa_id' => $s->id,
                'nisn' => $s->nisn,
                'nama_lengkap' => $s->nama_lengkap,
                'status' => $abs->status ?? 'Alfa',
                'keterangan' => $abs->keterangan ?? null,
            ];
        });

        $jadwalData = [
            'id' => $jadwal->id,
            'nama_kelas' => $jadwal->kelas->nama_kelas ?? null,
            'nama_mapel' => $jadwal->mataPelajaran->nama_mapel ?? null,
            'hari' => $jadwal->hari,
            'jam_mulai' => $jadwal->jam_mulai,
            'jam_selesai' => $jadwal->jam_selesai,
        ];

        return response()->json(['status' => 'success', 'data' => ['jadwal' => $jadwalData, 'attendees' => $attendees]]);
    }

    public function laporan(Request $request)
    {
        $kelas_id = $request->query('kelas_id');
        $bulan = $request->query('bulan');
        $tahun = $request->query('tahun');

        $query = Absensi::query();
        if ($kelas_id) {
            $query->whereHas('siswa', function($q) use ($kelas_id){ $q->where('kelas_id', $kelas_id); });
        }
        if ($bulan && $tahun) {
            $start = sprintf('%04d-%02d-01', $tahun, $bulan);
            $end = date('Y-m-t', strtotime($start));
            $query->whereBetween('tanggal', [$start,$end]);
        }

        $grouped = $query->get()->groupBy('siswa_id');

        $laporan = $grouped->map(function($rows) {
            $first = $rows->first();
            $siswa = $first->siswa;
            $hadir = $rows->where('status','Hadir')->count();
            $izin = $rows->where('status','Izin')->count();
            $sakit = $rows->where('status','Sakit')->count();
            $alfa = $rows->where('status','Alfa')->count();
            $total = $hadir + $izin + $sakit + $alfa;
            $persentase = $total > 0 ? round(($hadir / $total) * 100, 2) : 0;

            return [
                'siswa_id' => $first->siswa_id,
                'nama_lengkap' => $siswa->nama_lengkap ?? null,
                'nisn' => $siswa->nisn ?? null,
                'total_hadir' => $hadir,
                'total_izin' => $izin,
                'total_sakit' => $sakit,
                'total_alfa' => $alfa,
                'persentase_hadir' => $persentase,
            ];
        })->values();

        $kelas = $kelas_id ? Kelas::find($kelas_id) : null;
        $bulan_nama_map = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
        $bulan_nama = ($bulan && isset($bulan_nama_map[intval($bulan)])) ? $bulan_nama_map[intval($bulan)] : null;

        return response()->json(['status' => 'success', 'data' => ['kelas' => $kelas, 'bulan_nama' => $bulan_nama, 'laporan' => $laporan]]);
    }
}

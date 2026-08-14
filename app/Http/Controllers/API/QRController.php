<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JadwalPelajaran;
use App\Models\QrSession;
use App\Models\Siswa;
use App\Models\Absensi;
use Illuminate\Support\Facades\Auth;

class QRController extends Controller
{
    public function generate(Request $request)
    {
        $request->validate([
            'jadwal_id' => 'required|integer|exists:jadwal_pelajaran,id',
            'tanggal' => 'required|date',
            'durasi' => 'nullable|integer',
        ]);

        $user = $request->user();
        if (!in_array($user->role, ['admin','guru'])) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $durasi = $request->input('durasi', 15);
        $token = bin2hex(random_bytes(16)) . '-' . time();
        $expires_at = now()->addMinutes($durasi);

        $qr = QrSession::create([
            'token' => $token,
            'jadwal_id' => $request->jadwal_id,
            'tanggal' => $request->tanggal,
            'expires_at' => $expires_at,
            'created_by' => $user->id,
        ]);

        return response()->json(['token' => $token, 'expires_at' => $expires_at, 'durasi_menit' => $durasi]);
    }

    public function scan(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'nama_lengkap' => 'required|string',
            'nisn' => 'required|string',
        ]);

        $token = $request->token;
        $qr = QrSession::where('token', $token)->first();
        if (!$qr) return response()->json(['message' => 'QR token not found'], 404);
        if ($qr->expires_at < now()) return response()->json(['message' => 'QR token expired'], 403);

        $jadwal = JadwalPelajaran::find($qr->jadwal_id);
        if (!$jadwal) return response()->json(['message' => 'Jadwal not found'], 404);

        $siswa = Siswa::whereRaw('LOWER(TRIM(nama_lengkap)) = LOWER(TRIM(?))', [$request->nama_lengkap])
            ->where('nisn', $request->nisn)
            ->where('is_active', 1)
            ->first();

        if (!$siswa) return response()->json(['message' => 'Student not found or inactive'], 401);

        if ($siswa->kelas_id !== $jadwal->kelas_id) {
            return response()->json(['message' => 'Jadwal bukan untuk kelas Anda'], 403);
        }

        $existing = Absensi::where('siswa_id', $siswa->id)->where('jadwal_id', $jadwal->id)->where('tanggal', $qr->tanggal)->first();
        if ($existing) {
            return response()->json(['already_recorded' => true, 'status' => $existing->status]);
        }

        $absensi = Absensi::create([
            'siswa_id' => $siswa->id,
            'jadwal_id' => $jadwal->id,
            'tanggal' => $qr->tanggal,
            'status' => 'Hadir',
            'keterangan' => 'Presensi Mandiri via QR Scan',
            'dicatat_oleh' => $siswa->nama_lengkap . ' (Self QR Scan)'
        ]);

        // update rekap
        updateRekapAbsensi($siswa->id, date('m', strtotime($qr->tanggal)), date('Y', strtotime($qr->tanggal)));

        return response()->json(['recorded' => true, 'status' => 'Hadir']);
    }
}

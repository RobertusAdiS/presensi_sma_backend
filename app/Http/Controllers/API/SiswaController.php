<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Siswa;

class SiswaController extends Controller
{
    public function index()
    {
        return response()->json(Siswa::with('kelas')->get());
    }

    public function show($id)
    {
        return response()->json(Siswa::with('kelas')->findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nisn' => 'required|string|max:20|unique:siswa,nisn',
            'nama_lengkap' => 'required|string|max:100',
            'jenis_kelamin' => 'required|in:L,P',
            'tanggal_lahir' => 'required|date',
            'no_telp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'kelas_id' => 'required|integer|exists:kelas,id',
        ]);
        $siswa = Siswa::create($data);
        return response()->json($siswa, 201);
    }

    public function update(Request $request, $id)
    {
        $siswa = Siswa::findOrFail($id);
        $data = $request->validate([
            'nisn' => 'sometimes|required|string|max:20|unique:siswa,nisn,'.$id,
            'nama_lengkap' => 'sometimes|required|string|max:100',
            'jenis_kelamin' => 'sometimes|required|in:L,P',
            'tanggal_lahir' => 'sometimes|required|date',
            'no_telp' => 'nullable|string|max:20',
            'alamat' => 'nullable|string',
            'kelas_id' => 'sometimes|required|integer|exists:kelas,id',
        ]);
        $siswa->update($data);
        return response()->json($siswa);
    }

    public function destroy($id)
    {
        $siswa = Siswa::findOrFail($id);
        $siswa->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

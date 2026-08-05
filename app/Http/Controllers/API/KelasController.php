<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kelas;

class KelasController extends Controller
{
    public function index()
    {
        return response()->json(Kelas::all());
    }

    public function show($id)
    {
        return response()->json(Kelas::findOrFail($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_kelas' => 'required|string|max:20',
            'tingkat' => 'required|string|max:10',
            'jurusan' => 'nullable|string|max:50',
            'guru_id' => 'nullable|integer|exists:guru,id',
        ]);
        $kelas = Kelas::create($data);
        return response()->json($kelas, 201);
    }

    public function update(Request $request, $id)
    {
        $kelas = Kelas::findOrFail($id);
        $data = $request->validate([
            'nama_kelas' => 'sometimes|required|string|max:20',
            'tingkat' => 'sometimes|required|string|max:10',
            'jurusan' => 'nullable|string|max:50',
            'guru_id' => 'nullable|integer|exists:guru,id',
        ]);
        $kelas->update($data);
        return response()->json($kelas);
    }

    public function destroy($id)
    {
        $kelas = Kelas::findOrFail($id);
        $kelas->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

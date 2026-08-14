<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\KelasController;
use App\Http\Controllers\API\SiswaController;
use App\Http\Controllers\API\AbsensiController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\GuruController;
use App\Http\Controllers\API\MataPelajaranController;
use App\Http\Controllers\API\JadwalController;
use App\Http\Controllers\API\StudentAuthController;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/student/login', [StudentAuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/student/logout', [StudentAuthController::class, 'logout']);
    Route::get('/student/me', [StudentAuthController::class, 'me']);
    Route::apiResource('kelas', KelasController::class);
    Route::apiResource('siswa', SiswaController::class);
    Route::apiResource('absensi', AbsensiController::class);
    Route::get('absensi/input', [AbsensiController::class, 'input']);
    Route::get('absensi/laporan', [AbsensiController::class, 'laporan']);
    Route::post('absensi/generate-qr', [\App\Http\Controllers\API\QRController::class, 'generate']);
    Route::get('dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('guru', [GuruController::class, 'index']);
    Route::get('mapel', [MataPelajaranController::class, 'index']);
    Route::get('jadwal', [JadwalController::class, 'index']);
});

// Public scan endpoint
Route::post('absensi/scan', [\App\Http\Controllers\API\QRController::class, 'scan']);

//health check
Route::get('/health', function () {
    try {
        // Memastikan Laravel benar-benar bisa terkoneksi ke database
        DB::connection()->getPdo();

        return response()->json([
            'status' => 'ok',
            'database' => 'connected',
        ], 200);
    } catch (\Exception $e) {
        // Jika DB mati/gagal konek, kembalikan HTTP Status 500
        return response()->json([
            'status' => 'error',
            'message' => 'Database connection failed',
        ], 500);
    }
});

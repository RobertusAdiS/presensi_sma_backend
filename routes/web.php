<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/{path?}', function ($path = null) {
    $frontendRoot = realpath(base_path('../../presensi_sma_frontend'));
    if (! $frontendRoot) {
        abort(404);
    }

    $target = $frontendRoot . '/' . ltrim($path ?? '', '/');

    if ($path && File::exists($target) && File::isFile($target)) {
        return response()->file($target);
    }

    return response()->file($frontendRoot . '/index.html');
})->where('path', '^(?!api|sanctum).*$');

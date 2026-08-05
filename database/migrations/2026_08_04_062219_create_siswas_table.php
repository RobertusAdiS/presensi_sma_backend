<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('siswa')) {
        Schema::create('siswa', function (Blueprint $table) {
            $table->id();
            $table->string('nisn', 20)->unique();
            $table->string('nama_lengkap', 100);
            $table->enum('jenis_kelamin', ['L','P']);
            $table->date('tanggal_lahir');
            $table->string('no_telp', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->foreignId('kelas_id')->constrained('kelas')->cascadeOnDelete();
            $table->tinyInteger('is_active')->default(1);
            $table->timestamps();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siswas');
    }
};

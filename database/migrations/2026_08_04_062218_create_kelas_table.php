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
        if (!Schema::hasTable('kelas')) {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kelas', 20);
            $table->string('tingkat', 10);
            $table->string('jurusan', 50)->nullable();
            $table->foreignId('guru_id')->nullable()->constrained('guru')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};

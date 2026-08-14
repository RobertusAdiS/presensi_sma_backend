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
        if (!Schema::hasTable('guru')) {
        Schema::create('guru', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nip', 20)->unique();
            $table->string('no_telp', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->enum('jenis_kelamin', ['L','P'])->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gurus');
    }
};

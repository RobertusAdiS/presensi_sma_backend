<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('qr_sessions')) {
            Schema::create('qr_sessions', function (Blueprint $table) {
                $table->id();
                $table->string('token')->unique();
                $table->integer('jadwal_id')->unsigned();
                $table->date('tanggal');
                $table->dateTime('expires_at');
                $table->integer('created_by');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_sessions');
    }
};

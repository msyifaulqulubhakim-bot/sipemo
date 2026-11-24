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
        Schema::create('periode_penyusunans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_periode');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->text('deskripsi')->nullable();
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamp('archived_at')->nullable();

            // Virtual column & unique constraint untuk menegakkan aturan MAKSIMAL SATU periode aktif di tingkat database
            $table->string('active_guard', 20)
                ->virtualAs("CASE WHEN status = 'active' THEN 'active' ELSE NULL END")
                ->nullable();
            $table->unique('active_guard', 'unique_active_periode');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periode_penyusunans');
    }
};


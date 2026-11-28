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
        Schema::table('jurusans', function (Blueprint $table) {
            $table->unsignedBigInteger('id_prodi_kampus')->nullable()->unique()->after('id');
            $table->foreignId('fakultas_id')->nullable()->after('id_prodi_kampus')->constrained('fakultas')->nullOnDelete();
            $table->string('jenjang', 20)->nullable()->after('nama_jurusan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jurusans', function (Blueprint $table) {
            $table->dropForeign(['fakultas_id']);
            $table->dropColumn(['id_prodi_kampus', 'fakultas_id', 'jenjang']);
        });
    }
};

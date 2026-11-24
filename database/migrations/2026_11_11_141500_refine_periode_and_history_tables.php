<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Perbarui enum status pada periode_penyusunans: tambahkan 'draft' dan jadikan default 'draft'
        DB::statement("ALTER TABLE periode_penyusunans MODIFY COLUMN status ENUM('draft', 'active', 'archived') NOT NULL DEFAULT 'draft'");

        // 2. Perbarui foreign key periode_histories.periode_id: ubah dari cascade menjadi RESTRICT untuk melindungi audit trail
        Schema::table('periode_histories', function (Blueprint $table) {
            $table->dropForeign(['periode_id']);
            $table->foreign('periode_id')->references('id')->on('periode_penyusunans')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periode_histories', function (Blueprint $table) {
            $table->dropForeign(['periode_id']);
            $table->foreign('periode_id')->references('id')->on('periode_penyusunans')->onDelete('cascade');
        });

        DB::statement("ALTER TABLE periode_penyusunans MODIFY COLUMN status ENUM('active', 'archived') NOT NULL DEFAULT 'active'");
    }
};


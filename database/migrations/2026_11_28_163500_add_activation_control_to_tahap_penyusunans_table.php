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
        Schema::table('tahap_penyusunans', function (Blueprint $table) {
            $table->enum('status', ['inactive', 'active', 'completed'])
                ->default('inactive')
                ->after('deskripsi_tahap');
            $table->timestamp('activated_at')->nullable()->after('status');
            $table->timestamp('deactivated_at')->nullable()->after('activated_at');
            $table->timestamp('completed_at')->nullable()->after('deactivated_at');
            $table->foreignId('activated_by')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->after('activated_by')->constrained('users')->nullOnDelete();

            // Virtual column & composite unique index untuk menjamin maksimal SATU tahap aktif per periode di level MySQL
            $table->string('active_guard', 20)
                ->virtualAs("CASE WHEN status = 'active' THEN 'active' ELSE NULL END")
                ->nullable()
                ->after('completed_by');
            $table->unique(['periode_id', 'active_guard'], 'unique_active_tahap_per_periode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tahap_penyusunans', function (Blueprint $table) {
            $table->dropUnique('unique_active_tahap_per_periode');
            $table->dropForeign(['activated_by']);
            $table->dropForeign(['completed_by']);
            $table->dropColumn([
                'active_guard',
                'completed_by',
                'activated_by',
                'completed_at',
                'deactivated_at',
                'activated_at',
                'status',
            ]);
        });
    }
};

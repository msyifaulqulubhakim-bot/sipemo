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
        Schema::table('users', function (Blueprint $table) {
            $table->string('nip')->nullable()->index()->after('email');
            $table->string('nidn')->nullable()->index()->after('nip');
            $table->string('auth_source', 30)->default('local')->after('remember_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['nip']);
            $table->dropIndex(['nidn']);
            $table->dropColumn(['nip', 'nidn', 'auth_source']);
        });
    }
};

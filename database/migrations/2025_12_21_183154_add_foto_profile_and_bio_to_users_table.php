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
            // ✅ Cek apakah kolom sudah ada sebelum ditambahkan
            if (!Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('password');
            }
            
            if (!Schema::hasColumn('users', 'foto_profile')) {
                $table->string('foto_profile', 255)->nullable()->after('password');
            }
            
            if (!Schema::hasColumn('users', 'fcm_token')) {
                $table->string('fcm_token', 255)->nullable()->after('password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'bio')) {
                $table->dropColumn('bio');
            }
            
            if (Schema::hasColumn('users', 'foto_profile')) {
                $table->dropColumn('foto_profile');
            }
            
            if (Schema::hasColumn('users', 'fcm_token')) {
                $table->dropColumn('fcm_token');
            }
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // =============================
        // UPDATE TABEL FORUM PERTANYAAN
        // =============================
        Schema::table('forum_pertanyaan', function (Blueprint $table) {

            if (!Schema::hasColumn('forum_pertanyaan', 'image_forum')) {
                $table->string('image_forum')->nullable();
            }

            if (!Schema::hasColumn('forum_pertanyaan', 'tanggal')) {
                $table->dateTime('tanggal')->useCurrent();
            }

            if (Schema::hasColumn('forum_pertanyaan', 'judul')) {
                $table->dropColumn('judul');
            }
        });

        // =============================
        // UPDATE TABEL FORUM JAWABAN
        // =============================
        Schema::table('forum_jawaban', function (Blueprint $table) {

            if (!Schema::hasColumn('forum_jawaban', 'image_jawaban')) {
                $table->string('image_jawaban')->nullable();
            }

            if (!Schema::hasColumn('forum_jawaban', 'tanggal')) {
                $table->dateTime('tanggal')->useCurrent();
            }
        });
    }

    public function down(): void
    {
        //
    }
};

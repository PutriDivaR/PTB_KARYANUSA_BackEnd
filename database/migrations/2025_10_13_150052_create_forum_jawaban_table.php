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
        Schema::create('forum_jawaban', function (Blueprint $table) {
            $table->id('jawaban_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->foreignId('pertanyaan_id')->constrained('forum_pertanyaan', 'pertanyaan_id')->onDelete('cascade');
            $table->string('image_jawaban')->nullable(); // ✅ Tambah field untuk gambar jawaban
            $table->text('isi');
            $table->dateTime('tanggal')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_jawaban');
    }
};
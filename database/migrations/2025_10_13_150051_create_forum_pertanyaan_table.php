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
        Schema::create('forum_pertanyaan', function (Blueprint $table) {
            $table->id('pertanyaan_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->onDelete('cascade');
            $table->string('image_forum')->nullable(); // ✅ Tambah field untuk gambar
            $table->text('isi'); // ✅ Hapus 'judul', karena tidak ada di model & controller
            $table->dateTime('tanggal')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_pertanyaan');
    }
};
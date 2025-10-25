<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materi', function (Blueprint $table) {
            $table->id('materi_id');
            $table->unsignedBigInteger('kursus_id');
            $table->string('judul', 150);
            $table->integer('durasi')->nullable(); // durasi dalam detik
            $table->string('video')->nullable(); // nama file video
            $table->timestamps();

            $table->foreign('kursus_id')->references('kursus_id')->on('kursus')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materi');
    }
};

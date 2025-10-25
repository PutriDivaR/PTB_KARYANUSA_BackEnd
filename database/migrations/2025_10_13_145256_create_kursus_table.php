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
            Schema::create('kursus', function (Blueprint $table) {
                $table->id('kursus_id');
                $table->string('judul', 150);
                $table->text('deskripsi');
                $table->string('pengrajin_nama', 100);
                $table->string('thumbnail', 255)->nullable();
                $table->timestamps();
            });
        }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kursus');
    }
};

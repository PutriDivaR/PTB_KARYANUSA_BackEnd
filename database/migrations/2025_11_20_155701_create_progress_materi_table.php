<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progress_materi', function (Blueprint $table) {
            $table->id('progress_id');
            $table->foreignId('enrollment_id')->constrained('enrollment', 'enrollment_id')->onDelete('cascade');
            $table->foreignId('materi_id')->constrained('materi', 'materi_id')->onDelete('cascade');
            $table->boolean('is_completed')->default(false);

            $table->unique(['enrollment_id', 'materi_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progress_materi');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
{
    Schema::dropIfExists('likes');
}

public function down(): void
{
    Schema::create('likes', function (Blueprint $table) {
        $table->id('like_id');
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('galeri_id');
        $table->timestamps();

        $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
        $table->foreign('galeri_id')->references('galeri_id')->on('galeri')->onDelete('cascade');

        $table->unique(['user_id', 'galeri_id']);
    });
    }
};

<?php
// database/migrations/xxxx_create_likes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id('like_id');
            $table->unsignedBigInteger('user_id'); // Yang like
            $table->unsignedBigInteger('galeri_id'); // Karya yang di-like
            $table->timestamp('created_at')->useCurrent();
            
            // ✅ Unique constraint: 1 user hanya bisa like 1x per karya
            $table->unique(['user_id', 'galeri_id']);
            
            // ✅ Index untuk query cepat
            $table->index('galeri_id');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('likes');
    }
};
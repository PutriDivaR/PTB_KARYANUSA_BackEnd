<?php
// database/migrations/xxxx_add_likes_count_to_galeri_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('galeri', function (Blueprint $table) {
            $table->integer('likes')->default(0)->after('views');
        });
    }

    public function down()
    {
        Schema::table('galeri', function (Blueprint $table) {
            $table->dropColumn('likes');
        });
    }
};
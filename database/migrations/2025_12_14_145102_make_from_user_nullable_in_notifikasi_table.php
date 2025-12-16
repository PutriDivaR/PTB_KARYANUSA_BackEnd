<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('notifikasi', function (Blueprint $table) {
            // Drop foreign key dulu
            $table->dropForeign(['from_user']);
            
            // Ubah column jadi nullable
            $table->unsignedBigInteger('from_user')->nullable()->change();
            
            // Tambah foreign key lagi
            $table->foreign('from_user')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('notifikasi', function (Blueprint $table) {
            $table->dropForeign(['from_user']);
            $table->unsignedBigInteger('from_user')->nullable(false)->change();
            $table->foreign('from_user')
                ->references('user_id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id('notif_id');

            $table->unsignedBigInteger('from_user'); 
            $table->unsignedBigInteger('to_user');  

            $table->string('type'); 
            $table->string('title'); 
            $table->text('message');

            $table->unsignedBigInteger('related_id')->nullable(); // id kursus / id forum / id galeri
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->foreign('from_user')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('to_user')->references('user_id')->on('users')->onDelete('cascade');

        });

    }

    public function down()
    {
        Schema::dropIfExists('notifikasi');
    }

};

<?php
// app/Models/Like.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    protected $primaryKey = 'like_id';
    public $timestamps = false; 
    
    protected $fillable = [
        'user_id',
        'galeri_id'
    ];

    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function galeri()
    {
        return $this->belongsTo(Galeri::class, 'galeri_id', 'galeri_id');
    }
}
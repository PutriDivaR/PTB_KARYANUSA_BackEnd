<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class ForumPertanyaan extends Model
{
    protected $table = 'forum_pertanyaan';
    protected $primaryKey = 'pertanyaan_id';
    protected $fillable = [
        'user_id',
        'image_forum',
        'isi',
        'tanggal'
    ];
    // Relasi ke tabel jawaban
    public function jawaban()
    {
        return $this->hasMany(ForumJawaban::class, 'pertanyaan_id', 'pertanyaan_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
    public $timestamps = true;
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class ForumJawaban extends Model
{
    protected $table = 'forum_jawaban';
    protected $primaryKey = 'jawaban_id';
    protected $fillable = [
        'user_id',
        'pertanyaan_id',
        'image_jawaban',
        'isi',
        'tanggal'
    ];
   
    public function pertanyaan()
    {
        return $this->belongsTo(ForumPertanyaan::class, 'pertanyaan_id', 'pertanyaan_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
    public $timestamps = true;
}
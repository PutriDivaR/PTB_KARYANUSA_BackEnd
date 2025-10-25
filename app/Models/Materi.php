<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Materi extends Model
{
    use HasFactory;

    protected $table = 'materi';
    protected $primaryKey = 'materi_id';
    protected $fillable = [
        'kursus_id',
        'judul',
        'durasi',
        'video'
    ];

    // Relasi ke Kursus
    public function kursus()
    {
        return $this->belongsTo(Kursus::class, 'kursus_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kursus extends Model
{
    use HasFactory;

    protected $table = 'kursus';
    protected $primaryKey = 'kursus_id';
    public $timestamps = true;

    protected $fillable = [
        'judul',
        'deskripsi',
        'pengrajin_nama',
        'thumbnail'
    ];
protected $appends = ['thumbnail_url'];

public function getThumbnailUrlAttribute()
{
    return asset('storage/thumbnails/' . $this->thumbnail);
}


public function materi()
{
    return $this->hasMany(Materi::class, 'kursus_id');
}


}



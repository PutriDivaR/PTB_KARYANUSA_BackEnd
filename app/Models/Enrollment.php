<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $table = 'enrollment';
    protected $primaryKey = 'enrollment_id';

    protected $fillable = [
        'user_id',
        'kursus_id',
        'progress',
        'status',
    ];

    // Relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke kursus
    public function kursus()
    {
        return $this->belongsTo(Kursus::class, 'kursus_id');
    }

    public function materiProgress()
    {
        return $this->hasMany(ProgressMateri::class, 'enrollment_id', 'enrollment_id');
    }
    
}

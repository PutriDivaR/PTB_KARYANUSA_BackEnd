<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgressMateri extends Model
{
    protected $table = 'progress_materi';

    protected $primaryKey = 'progress_id';

    protected $fillable = [
        'enrollment_id',
        'materi_id',
        'is_completed'
    ];

    public function enrollment()
    {
        return $this->belongsTo(Enrollment::class, 'enrollment_id', 'enrollment_id');
    }

    public function materi()
    {
        return $this->belongsTo(Materi::class, 'materi_id', 'materi_id');
    }
}

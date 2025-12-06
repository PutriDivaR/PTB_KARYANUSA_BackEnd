<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    protected $table = 'notifikasi';
    protected $primaryKey = 'notif_id';

    protected $fillable = [
        'from_user',
        'to_user',
        'type',
        'title',
        'message',
        'related_id',
        'is_read'
    ];

    public function fromUser() {
        return $this->belongsTo(User::class, 'from_user');
    }

    public function toUser() {
        return $this->belongsTo(User::class, 'to_user');
    }
}



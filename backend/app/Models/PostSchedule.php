<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostSchedule extends Model
{
    protected $fillable = [
        'judul', 'konten_html', 'image', 'tag', 'tanggal_publish', 'posted'
    ];

    protected $casts = [
        'tanggal_publish' => 'datetime',
        'posted' => 'boolean',
    ];
}


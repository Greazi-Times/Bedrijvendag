<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Edition extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'date',
        'images',
        'thumbnail',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}

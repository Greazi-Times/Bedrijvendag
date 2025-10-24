<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'logo',
        'href',
        'visible',
        'editions',
        'sectors',
    ];

    protected $casts = [
        'visible' => 'boolean',
        'editions' => 'array',
        'sectors' => 'array',
    ];
}

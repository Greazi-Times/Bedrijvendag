<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventMapPoint extends Model
{
    protected $fillable = [
        'event_id',
        'label',
        'type',
        'description',
        'x_percent',
        'y_percent',
        'sort_order',
    ];

    protected $casts = [
        'x_percent' => 'float',
        'y_percent' => 'float',
        'sort_order' => 'integer',
    ];
}

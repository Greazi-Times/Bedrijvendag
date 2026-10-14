<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;

class EventMapPoint extends Model
{
    use Audited;

    protected string $auditCategory = 'event-stands';

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

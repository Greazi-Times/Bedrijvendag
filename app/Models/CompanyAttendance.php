<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyAttendance extends Model
{
    use Audited;

    protected $auditCategory = 'attendance';

    protected $fillable = ['event_id', 'company_id', 'checked_in_at', 'checked_in_by', 'lunch_checked_in'];

    protected $casts = ['checked_in_at' => 'datetime'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}

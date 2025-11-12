<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stand extends Model
{
    protected $fillable = ['company_id'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}

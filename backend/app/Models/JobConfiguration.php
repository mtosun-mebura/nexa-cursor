<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'type','value','company_id'
    ];
}



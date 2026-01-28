<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'subject', 'type', 'html_content', 'text_content', 'description', 'is_active', 'company_id'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns the email template.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the notifications that use this email template.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }
}



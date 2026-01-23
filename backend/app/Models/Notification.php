<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'company_id', 'type', 'category', 'title', 'message', 'priority', 'read_at', 'action_url', 'data', 'scheduled_at', 'location_id', 'file_path', 'file_name', 'file_size', 'original_notification_id'
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'scheduled_at' => 'datetime',
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company that owns the notification.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}



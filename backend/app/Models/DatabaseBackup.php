<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class DatabaseBackup extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const TRIGGER_MANUAL = 'manual';

    public const TRIGGER_SCHEDULED = 'scheduled';

    protected $fillable = [
        'filename',
        'disk_path',
        'connection',
        'database_name',
        'size_bytes',
        'status',
        'trigger',
        'error_message',
        'completed_at',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function localCreatedAt(): ?Carbon
    {
        if ($this->created_at === null) {
            return null;
        }

        $timezone = (string) config('database_backup.display_timezone', 'Europe/Amsterdam');

        return $this->created_at->copy()->timezone($timezone !== '' ? $timezone : 'Europe/Amsterdam');
    }

    public function humanSize(): string
    {
        $bytes = max(0, (int) $this->size_bytes);
        if ($bytes < 1024) {
            return $bytes.' B';
        }
        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }
        if ($bytes < 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1).' MB';
        }

        return round($bytes / (1024 * 1024 * 1024), 2).' GB';
    }

    public function humanSizeMegabytes(): string
    {
        $mb = max(0, (int) $this->size_bytes) / (1024 * 1024);
        $decimals = $mb >= 10 ? 1 : 2;

        return round($mb, $decimals).' MB';
    }

    public function absolutePath(): string
    {
        return Storage::disk('local')->path($this->disk_path);
    }

    public function fileExists(): bool
    {
        return Storage::disk('local')->exists($this->disk_path);
    }
}

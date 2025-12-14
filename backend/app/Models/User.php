<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The guard name for the permissions.
     */
    protected $guard_name = 'web';

    /**
     * Get the favorites for the user.
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Get the CV files for the user.
     */
    public function cvFiles()
    {
        return $this->hasMany(CVFile::class);
    }

    /**
     * Get the favorited vacancies for the user.
     */
    public function favoriteVacancies()
    {
        return $this->belongsToMany(Vacancy::class, 'favorites', 'user_id', 'vacancy_id');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'password',
        'date_of_birth',
        'email_verified_at',
        'phone_verified_at',
        'phone',
        'company_id',
        'is_active',
        'cv_path',
        'cv_original_name',
        'location',
        'bio',
        'photo',
        'photo_blob',
        'photo_mime_type',
        'preferred_location',
        'max_distance',
        'contract_type',
        'work_hours',
        'min_salary',
        'email_notifications',
        'sms_notifications',
        'push_notifications',
        'profile_visible',
        'cv_downloadable',
        'function',
        'job_title_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Generate a secure token for photo access
     */
    public function getPhotoToken(): string
    {
        // Include updated_at timestamp to ensure token changes when photo is updated
        $hash = hash('sha256', $this->id . $this->updated_at . config('app.key'));
        $token = base64_encode($this->id . '|' . $hash);
        return $token;
    }

    /**
     * Generate a secure token for company photo access
     */
    public function getCompanyPhotoToken($companyId): string
    {
        $hash = hash('sha256', $this->id . $companyId . config('app.key'));
        $token = base64_encode($this->id . '|' . $companyId . '|' . $hash);
        return $token;
    }

    /**
     * Get the company that owns the user.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the job title for the user
     */
    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class);
    }

    /**
     * Get the skills for the user.
     */
    public function skills()
    {
        return $this->hasMany(Skill::class);
    }

    /**
     * Get the experiences for the user.
     */
    public function experiences()
    {
        return $this->hasMany(Experience::class);
    }

    /**
     * Get the custom notifications for the user.
     * This overrides Laravel's default notifications() method from Notifiable trait.
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }
}

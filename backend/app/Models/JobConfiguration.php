<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'type','value','company_id','type_id'
    ];

    /**
     * Get the company that owns this configuration.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the type that owns this configuration.
     */
    public function configurationType()
    {
        return $this->belongsTo(JobConfigurationType::class, 'type_id');
    }

    /**
     * Get display name for type
     */
    public function getTypeDisplayAttribute()
    {
        if ($this->configurationType) {
            return $this->configurationType->display_name;
        }
        
        // Fallback to old method for backward compatibility
        return match($this->type) {
            'employment_type' => 'Dienstverband Type',
            'working_hours' => 'Werkuren',
            'status' => 'Status',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }

    /**
     * Scope for global configurations
     */
    public function scopeGlobal($query)
    {
        return $query->whereNull('company_id');
    }

    /**
     * Scope for company-specific configurations
     */
    public function scopeCompanySpecific($query, $companyId = null)
    {
        if ($companyId) {
            return $query->where('company_id', $companyId);
        }
        return $query->whereNotNull('company_id');
    }

    /**
     * Scope for type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }
}



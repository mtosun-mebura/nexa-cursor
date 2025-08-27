<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vacancy extends Model
{
    use HasFactory; // per-tenant scoping optional: add TenantScoped if needed

    protected $fillable = [
        'company_id','title','location','employment_type','description','requirements','offer',
        'application_instructions','category_id','reference_number','logo','salary_range','start_date',
        'working_hours','travel_expenses','remote_work','status','language','publication_date','closing_date',
        'meta_title','meta_description','meta_keywords'
    ];
}



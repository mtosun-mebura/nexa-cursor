<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Vacancy extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id','title','location','employment_type','description','requirements','offer',
        'application_instructions','category_id','reference_number','logo','salary_range','start_date',
        'working_hours','travel_expenses','remote_work','status','language','publication_date','closing_date',
        'meta_title','meta_description','meta_keywords','is_active','published_at','salary_min','salary_max',
        'experience_level','benefits'
    ];

    protected $casts = [
        'travel_expenses' => 'boolean',
        'remote_work' => 'boolean',
        'is_active' => 'boolean',
        'publication_date' => 'datetime',
        'published_at' => 'datetime',
        'closing_date' => 'datetime',
        'start_date' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vacancy) {
            // Automatisch publication_date instellen als deze niet is opgegeven
            if (empty($vacancy->publication_date)) {
                $vacancy->publication_date = now();
            }
            
            // Automatisch SEO velden genereren als deze niet zijn opgegeven
            if (empty($vacancy->meta_title)) {
                $vacancy->meta_title = $vacancy->title;
            }
            
            if (empty($vacancy->meta_description)) {
                $vacancy->meta_description = $this->generateMetaDescription($vacancy);
            }
            
            if (empty($vacancy->meta_keywords)) {
                $vacancy->meta_keywords = $this->generateMetaKeywords($vacancy);
            }
        });

        static::updating(function ($vacancy) {
            // SEO velden bijwerken als titel of beschrijving verandert
            if ($vacancy->isDirty('title') && empty($vacancy->meta_title)) {
                $vacancy->meta_title = $vacancy->title;
            }
            
            if ($vacancy->isDirty(['title', 'description', 'location', 'employment_type']) && empty($vacancy->meta_description)) {
                $vacancy->meta_description = $this->generateMetaDescription($vacancy);
            }
            
            if ($vacancy->isDirty(['title', 'description', 'location', 'employment_type', 'category_id']) && empty($vacancy->meta_keywords)) {
                $vacancy->meta_keywords = $this->generateMetaKeywords($vacancy);
            }
        });
    }

    /**
     * Relatie naar het bedrijf
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relatie naar de categorie
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }



    /**
     * Scope voor actieve vacatures
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('published_at', '<=', now());
    }

    /**
     * Scope voor vacatures met status
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope voor nieuwste vacatures eerst
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('published_at', 'desc');
    }

    /**
     * Genereer meta description voor SEO
     */
    private function generateMetaDescription($vacancy)
    {
        $description = $vacancy->title;
        
        if ($vacancy->location) {
            $description .= ' in ' . $vacancy->location;
        }
        
        if ($vacancy->employment_type) {
            $description .= ' - ' . $vacancy->employment_type;
        }
        
        if ($vacancy->description) {
            $description .= '. ' . Str::limit(strip_tags($vacancy->description), 120);
        }
        
        return Str::limit($description, 160);
    }

    /**
     * Genereer meta keywords voor SEO
     */
    private function generateMetaKeywords($vacancy)
    {
        $keywords = [];
        
        // Basis keywords
        $keywords[] = 'vacature';
        $keywords[] = 'werk';
        $keywords[] = 'baan';
        $keywords[] = 'sollicitatie';
        
        // Titel keywords
        $titleWords = explode(' ', strtolower($vacancy->title));
        $keywords = array_merge($keywords, array_slice($titleWords, 0, 5));
        
        // Locatie
        if ($vacancy->location) {
            $keywords[] = strtolower($vacancy->location);
        }
        
        // Werktype
        if ($vacancy->employment_type) {
            $keywords[] = strtolower($vacancy->employment_type);
        }
        
        // Categorie
        if ($vacancy->category) {
            $keywords[] = strtolower($vacancy->category->name);
        }
        
        // Bedrijf
        if ($vacancy->company) {
            $keywords[] = strtolower($vacancy->company->name);
        }
        
        // Remote werk
        if ($vacancy->remote_work) {
            $keywords[] = 'remote';
            $keywords[] = 'thuiswerken';
        }
        
        return implode(', ', array_unique($keywords));
    }

    /**
     * Krijg de status kleur voor de frontend
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'Open' => '#90EE90', // licht groen
            'Gesloten' => '#FFB6C1', // licht rood
            'In behandeling' => '#FFD700', // licht oranje
            default => '#E0E0E0', // licht grijs
        };
    }

    /**
     * Krijg de status badge kleur voor de frontend
     */
    public function getStatusBadgeColorAttribute()
    {
        return match($this->status) {
            'Open' => 'success',
            'Gesloten' => 'danger',
            'In behandeling' => 'warning',
            default => 'secondary',
        };
    }

    /**
     * Krijg de volledige URL voor de vacature
     */
    public function getUrlAttribute()
    {
        return route('vacancies.show', [
            'company' => $this->company->slug ?? $this->company_id,
            'vacancy' => $this->id
        ]);
    }

    /**
     * Krijg gestructureerde data voor SEO
     */
    public function getStructuredDataAttribute()
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $this->title,
            'description' => $this->description,
            'datePosted' => $this->publication_date?->toISOString(),
            'validThrough' => $this->closing_date?->toISOString(),
            'employmentType' => $this->employment_type,
            'jobLocation' => [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $this->location,
                ]
            ],
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => $this->company->name,
                'sameAs' => $this->company->website,
            ]
        ];

        if ($this->salary_range) {
            $data['baseSalary'] = [
                '@type' => 'MonetaryAmount',
                'currency' => 'EUR',
                'value' => $this->salary_range
            ];
        }

        return $data;
    }
}



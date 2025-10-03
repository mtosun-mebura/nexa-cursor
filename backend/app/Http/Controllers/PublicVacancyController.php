<?php

namespace App\Http\Controllers;

use App\Models\Vacancy;
use App\Models\Category;
use App\Models\Company;
use Illuminate\Http\Request;

class PublicVacancyController extends Controller
{
    /**
     * Publieke vacatures overzicht pagina
     */
    public function index(Request $request)
    {
        $query = Vacancy::with(['company', 'category'])
            ->active()
            ->latest();
        
        // Filtering
        if ($request->filled('category')) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('slug', $request->string('category'));
            });
        }
        
        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $request->string('location') . '%');
        }
        
        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->string('employment_type'));
        }
        
        if ($request->filled('remote_work')) {
            $query->where('remote_work', $request->boolean('remote_work'));
        }
        
        // Sortering
        $sortBy = $request->get('sort_by', 'publication_date');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortFields = ['publication_date', 'title', 'location'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        }
        
        $vacancies = $query->paginate(12);
        
        // Categorieën voor filter
        $categories = Category::orderBy('name')->get();
        
        // SEO data
        $seoData = [
            'title' => 'Vacatures - Vind jouw droombaan | Skillmatching AI',
            'description' => 'Bekijk alle beschikbare vacatures. Filter op locatie, categorie en werktype. Direct solliciteren via onze AI-powered matching.',
            'keywords' => 'vacatures, banen, werk, sollicitatie, carrière, job matching, AI vacatures',
            'canonical' => route('vacancies.index'),
        ];
        
        return view('public.vacancies.index', compact('vacancies', 'categories', 'seoData'));
    }

    /**
     * Publieke vacature detail pagina
     */
    public function show($companySlug, $vacancyId)
    {
        $vacancy = Vacancy::with(['company', 'category'])
            ->whereHas('company', function($query) use ($companySlug) {
                $query->where('slug', $companySlug);
            })
            ->where('id', $vacancyId)
            ->active()
            ->firstOrFail();
        
        // Gerelateerde vacatures
        $relatedVacancies = Vacancy::with(['company', 'category'])
            ->where('id', '!=', $vacancy->id)
            ->where(function($query) use ($vacancy) {
                $query->where('category_id', $vacancy->category_id)
                      ->orWhere('location', $vacancy->location)
                      ->orWhere('company_id', $vacancy->company_id);
            })
            ->active()
            ->latest()
            ->limit(5)
            ->get();
        
        // SEO data
        $seoData = [
            'title' => $vacancy->meta_title ?: $vacancy->title . ' - ' . $vacancy->company->name,
            'description' => $vacancy->meta_description ?: $this->generateMetaDescription($vacancy),
            'keywords' => $vacancy->meta_keywords ?: $this->generateMetaKeywords($vacancy),
            'canonical' => route('vacancies.show', ['company' => $companySlug, 'vacancy' => $vacancyId]),
            'structured_data' => $vacancy->structured_data,
            'og_image' => $vacancy->logo ?: $vacancy->company->logo ?? null,
        ];
        
        return view('public.vacancies.show', compact('vacancy', 'relatedVacancies', 'seoData'));
    }

    /**
     * Frontend vacature detail pagina
     */
    public function frontendShow($companySlug, $vacancyId)
    {
        $vacancy = Vacancy::with(['company', 'category'])
            ->whereHas('company', function($query) use ($companySlug) {
                $query->where('slug', $companySlug);
            })
            ->where('id', $vacancyId)
            ->active()
            ->firstOrFail();
        
        // Gerelateerde vacatures
        $relatedVacancies = Vacancy::with(['company', 'category'])
            ->where('id', '!=', $vacancy->id)
            ->where(function($query) use ($vacancy) {
                $query->where('category_id', $vacancy->category_id)
                      ->orWhere('location', $vacancy->location)
                      ->orWhere('company_id', $vacancy->company_id);
            })
            ->active()
            ->latest()
            ->limit(6)
            ->get();
        
        return view('frontend.pages.vacancy-details', compact('vacancy', 'relatedVacancies'));
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
            $description .= '. ' . \Illuminate\Support\Str::limit(strip_tags($vacancy->description), 120);
        }
        
        return \Illuminate\Support\Str::limit($description, 160);
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
}

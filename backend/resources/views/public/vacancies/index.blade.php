<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- SEO Meta Tags -->
    <title>{{ $seoData['title'] }}</title>
    <meta name="description" content="{{ $seoData['description'] }}">
    <meta name="keywords" content="{{ $seoData['keywords'] }}">
    <link rel="canonical" href="{{ $seoData['canonical'] }}">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="{{ $seoData['title'] }}">
    <meta property="og:description" content="{{ $seoData['description'] }}">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ $seoData['canonical'] }}">
    <meta property="og:site_name" content="Skillmatching AI">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoData['title'] }}">
    <meta name="twitter:description" content="{{ $seoData['description'] }}">
    
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Skillmatching AI Vacatures",
        "description": "{{ $seoData['description'] }}",
        "url": "{{ url('/') }}",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "{{ url('/vacatures') }}?search={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #9c27b0;
            --secondary-color: #ba68c8;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --light-bg: #f8f9fa;
            --dark-text: #495057;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .main-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
            max-width: 1200px;
            overflow: hidden;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 60px 40px;
            text-align: center;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        
        .search-section {
            background: var(--light-bg);
            padding: 40px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(156, 39, 176, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(156, 39, 176, 0.3);
        }
        
        .vacancies-section {
            padding: 40px;
        }
        
        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .vacancy-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .vacancy-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .vacancy-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .vacancy-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 10px;
            line-height: 1.3;
        }
        
        .vacancy-company {
            background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
            color: #2e7d32;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .vacancy-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .meta-item i {
            color: var(--primary-color);
        }
        
        .vacancy-description {
            color: #6c757d;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .vacancy-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .tag {
            background: #f8f9fa;
            color: var(--dark-text);
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .tag.category {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            color: #f57c00;
        }
        
        .tag.type {
            background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
            color: #7b1fa2;
        }
        
        .tag.remote {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            color: #1976d2;
        }
        
        .vacancy-actions {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .btn-apply {
            background: linear-gradient(135deg, var(--success-color) 0%, #66bb6a 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(76, 175, 80, 0.3);
            color: white;
        }
        
        .btn-details {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-details:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .pagination-wrapper {
            display: flex;
            justify-content: center;
            margin-top: 40px;
        }
        
        .page-link {
            color: var(--primary-color);
            border: 1px solid #dee2e6;
            padding: 12px 16px;
            margin: 0 4px;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .page-link:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .search-form {
                grid-template-columns: 1fr;
            }
            
            .vacancy-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .vacancy-meta {
                flex-direction: column;
                gap: 10px;
            }
            
            .vacancy-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-apply, .btn-details {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Hero Section -->
        <div class="hero-section">
            <h1 class="hero-title">Vind jouw droombaan</h1>
            <p class="hero-subtitle">Ontdek duizenden vacatures en laat AI je helpen bij het vinden van de perfecte match</p>
        </div>
        
        <!-- Search Section -->
        <div class="search-section">
            <form method="GET" action="{{ route('vacancies.index') }}" class="search-form">
                <div class="form-group">
                    <label class="form-label">Locatie</label>
                    <input type="text" name="location" class="form-control" placeholder="Amsterdam, Rotterdam..." value="{{ request('location') }}">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Categorie</label>
                    <select name="category" class="form-select">
                        <option value="">Alle categorieën</option>
                        @foreach($categories ?? [] as $category)
                            <option value="{{ $category->slug }}" {{ request('category') == $category->slug ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Werktype</label>
                    <select name="employment_type" class="form-select">
                        <option value="">Alle types</option>
                        <option value="Fulltime" {{ request('employment_type') == 'Fulltime' ? 'selected' : '' }}>Fulltime</option>
                        <option value="Parttime" {{ request('employment_type') == 'Parttime' ? 'selected' : '' }}>Parttime</option>
                        <option value="Contract" {{ request('employment_type') == 'Contract' ? 'selected' : '' }}>Contract</option>
                        <option value="Freelance" {{ request('employment_type') == 'Freelance' ? 'selected' : '' }}>Freelance</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Remote werk</label>
                    <select name="remote_work" class="form-select">
                        <option value="">Alle opties</option>
                        <option value="1" {{ request('remote_work') == '1' ? 'selected' : '' }}>Alleen remote</option>
                        <option value="0" {{ request('remote_work') == '0' ? 'selected' : '' }}>Alleen op kantoor</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Zoeken
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Vacancies Section -->
        <div class="vacancies-section">
            <h2 class="section-title">
                @if(request()->hasAny(['location', 'category', 'employment_type', 'remote_work']))
                    Zoekresultaten ({{ $vacancies->total() }} vacatures)
                @else
                    Nieuwste vacatures
                @endif
            </h2>
            
            @forelse($vacancies as $vacancy)
                <div class="vacancy-card">
                    <div class="vacancy-header">
                        <div class="flex-grow-1">
                            <h3 class="vacancy-title">{{ $vacancy->title }}</h3>
                            <span class="vacancy-company">{{ $vacancy->company->name }}</span>
                        </div>
                    </div>
                    
                    <div class="vacancy-meta">
                        @if($vacancy->location)
                            <div class="meta-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>{{ $vacancy->location }}</span>
                            </div>
                        @endif
                        
                        @if($vacancy->employment_type)
                            <div class="meta-item">
                                <i class="fas fa-clock"></i>
                                <span>{{ $vacancy->employment_type }}</span>
                            </div>
                        @endif
                        
                        @if($vacancy->salary_range)
                            <div class="meta-item">
                                <i class="fas fa-euro-sign"></i>
                                <span>{{ $vacancy->salary_range }}</span>
                            </div>
                        @endif
                        
                        <div class="meta-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>{{ $vacancy->publication_date?->format('d-m-Y') }}</span>
                        </div>
                    </div>
                    
                    @if($vacancy->description)
                        <div class="vacancy-description">
                            {{ \Illuminate\Support\Str::limit(strip_tags($vacancy->description), 200) }}
                        </div>
                    @endif
                    
                    <div class="vacancy-tags">
                        @if($vacancy->category)
                            <span class="tag category">{{ $vacancy->category->name }}</span>
                        @endif
                        
                        @if($vacancy->employment_type)
                            <span class="tag type">{{ $vacancy->employment_type }}</span>
                        @endif
                        
                        @if($vacancy->remote_work)
                            <span class="tag remote">
                                <i class="fas fa-home me-1"></i>Remote mogelijk
                            </span>
                        @endif
                    </div>
                    
                    <div class="vacancy-actions">
                        <a href="{{ route('vacancies.show', ['company' => $vacancy->company->slug, 'vacancy' => $vacancy->id]) }}" class="btn-apply">
                            <i class="fas fa-paper-plane me-2"></i>Direct Solliciteren
                        </a>
                        <a href="{{ route('vacancies.show', ['company' => $vacancy->company->slug, 'vacancy' => $vacancy->id]) }}" class="btn-details">
                            <i class="fas fa-eye me-2"></i>Details Bekijken
                        </a>
                    </div>
                </div>
            @empty
                <div class="empty-state">
                    <i class="fas fa-search"></i>
                    <h3>Geen vacatures gevonden</h3>
                    <p>Probeer je zoekcriteria aan te passen of bekijk alle beschikbare vacatures.</p>
                    <a href="{{ route('vacancies.index') }}" class="btn btn-primary">
                        <i class="fas fa-list me-2"></i>Alle Vacatures Bekijken
                    </a>
                </div>
            @endforelse
            
            @if($vacancies->hasPages())
                <div class="pagination-wrapper">
                    {{ $vacancies->links() }}
                </div>
            @endif
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


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
    <meta property="og:type" content="article">
    <meta property="og:url" content="{{ $seoData['canonical'] }}">
    <meta property="og:site_name" content="Skillmatching AI">
    @if($seoData['og_image'])
        <meta property="og:image" content="{{ $seoData['og_image'] }}">
    @endif
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoData['title'] }}">
    <meta name="twitter:description" content="{{ $seoData['description'] }}">
    @if($seoData['og_image'])
        <meta name="twitter:image" content="{{ $seoData['og_image'] }}">
    @endif
    
    <!-- Structured Data -->
    @if($seoData['structured_data'])
        <script type="application/ld+json">
        {!! json_encode($seoData['structured_data']) !!}
        </script>
    @endif
    
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
        
        .vacancy-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 60px 40px;
        }
        
        .breadcrumb-nav {
            margin-bottom: 30px;
        }
        
        .breadcrumb-item a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
        }
        
        .breadcrumb-item.active {
            color: white;
        }
        
        .vacancy-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .vacancy-company {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        
        .vacancy-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
        }
        
        .meta-item i {
            font-size: 1.3rem;
        }
        
        .vacancy-content {
            padding: 40px;
        }
        
        .content-section {
            margin-bottom: 40px;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--primary-color);
        }
        
        .content-text {
            line-height: 1.8;
            color: #6c757d;
            font-size: 1.1rem;
        }
        
        .vacancy-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .tag {
            background: #f8f9fa;
            color: var(--dark-text);
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
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
        
        .tag.salary {
            background: linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%);
            color: #2e7d32;
        }
        
        .action-buttons {
            display: flex;
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .btn-apply {
            background: linear-gradient(135deg, var(--success-color) 0%, #66bb6a 100%);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 15px 40px;
            font-weight: 700;
            font-size: 1.1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-apply:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(76, 175, 80, 0.4);
            color: white;
        }
        
        .btn-save {
            background: transparent;
            color: var(--primary-color);
            border: 3px solid var(--primary-color);
            border-radius: 15px;
            padding: 12px 30px;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-save:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .company-info {
            background: var(--light-bg);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 40px;
        }
        
        .company-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 15px;
        }
        
        .company-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .company-detail {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #6c757d;
        }
        
        .company-detail i {
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        .related-vacancies {
            background: var(--light-bg);
            padding: 40px;
            border-radius: 15px;
        }
        
        .related-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark-text);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .related-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .related-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        
        .related-title-small {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-text);
            margin-bottom: 10px;
        }
        
        .related-company {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .related-meta {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .related-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .related-link:hover {
            color: var(--secondary-color);
        }
        
        @media (max-width: 768px) {
            .vacancy-title {
                font-size: 2rem;
            }
            
            .vacancy-meta {
                flex-direction: column;
                gap: 15px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-apply, .btn-save {
                width: 100%;
                text-align: center;
            }
            
            .company-details {
                grid-template-columns: 1fr;
            }
            
            .related-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- Vacancy Header -->
        <div class="vacancy-header">
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="{{ route('vacancies.index') }}">
                            <i class="fas fa-home me-1"></i>Vacatures
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('vacancies.index') }}?category={{ $vacancy->category->slug ?? '' }}">
                            {{ $vacancy->category->name ?? 'Alle categorieën' }}
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $vacancy->title }}</li>
                </ol>
            </nav>
            
            <h1 class="vacancy-title">{{ $vacancy->title }}</h1>
            <div class="vacancy-company">{{ $vacancy->company->name }}</div>
            
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
                    <span>Gepubliceerd op {{ $vacancy->publication_date?->format('d-m-Y') }}</span>
                </div>
                
                @if($vacancy->closing_date)
                    <div class="meta-item">
                        <i class="fas fa-calendar-times"></i>
                        <span>Sluit op {{ $vacancy->closing_date->format('d-m-Y') }}</span>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Vacancy Content -->
        <div class="vacancy-content">
            <!-- Tags -->
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
                
                @if($vacancy->salary_range)
                    <span class="tag salary">{{ $vacancy->salary_range }}</span>
                @endif
                
                @if($vacancy->travel_expenses)
                    <span class="tag">Reiskosten vergoed</span>
                @endif
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="#" class="btn-apply" onclick="startApplication()">
                    <i class="fas fa-paper-plane me-2"></i>Direct Solliciteren
                </a>
                <a href="#" class="btn-save" onclick="saveVacancy()">
                    <i class="fas fa-bookmark me-2"></i>Vacature Opslaan
                </a>
            </div>
            
            <!-- Company Information -->
            <div class="company-info">
                <h3 class="company-name">{{ $vacancy->company->name }}</h3>
                <div class="company-details">
                    @if($vacancy->company->website)
                        <div class="company-detail">
                            <i class="fas fa-globe"></i>
                            <a href="{{ $vacancy->company->website }}" target="_blank" class="text-decoration-none">
                                {{ $vacancy->company->website }}
                            </a>
                        </div>
                    @endif
                    
                    @if($vacancy->company->email)
                        <div class="company-detail">
                            <i class="fas fa-envelope"></i>
                            <span>{{ $vacancy->company->email }}</span>
                        </div>
                    @endif
                    
                    @if($vacancy->company->phone)
                        <div class="company-detail">
                            <i class="fas fa-phone"></i>
                            <span>{{ $vacancy->company->phone }}</span>
                        </div>
                    @endif
                    
                    @if($vacancy->company->city)
                        <div class="company-detail">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>{{ $vacancy->company->city }}</span>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Job Description -->
            @if($vacancy->description)
                <div class="content-section">
                    <h2 class="section-title">
                        <i class="fas fa-file-alt me-2"></i>Functieomschrijving
                    </h2>
                    <div class="content-text">
                        {!! nl2br(e($vacancy->description)) !!}
                    </div>
                </div>
            @endif
            
            <!-- Requirements -->
            @if($vacancy->requirements)
                <div class="content-section">
                    <h2 class="section-title">
                        <i class="fas fa-list-check me-2"></i>Eisen & Kwalificaties
                    </h2>
                    <div class="content-text">
                        {!! nl2br(e($vacancy->requirements)) !!}
                    </div>
                </div>
            @endif
            
            <!-- Offer -->
            @if($vacancy->offer)
                <div class="content-section">
                    <h2 class="section-title">
                        <i class="fas fa-gift me-2"></i>Wat Wij Bieden
                    </h2>
                    <div class="content-text">
                        {!! nl2br(e($vacancy->offer)) !!}
                    </div>
                </div>
            @endif
            
            <!-- Application Instructions -->
            @if($vacancy->application_instructions)
                <div class="content-section">
                    <h2 class="section-title">
                        <i class="fas fa-info-circle me-2"></i>Sollicitatie Instructies
                    </h2>
                    <div class="content-text">
                        {!! nl2br(e($vacancy->application_instructions)) !!}
                    </div>
                </div>
            @endif
            
            <!-- Related Vacancies -->
            @if($relatedVacancies->count() > 0)
                <div class="related-vacancies">
                    <h3 class="related-title">
                        <i class="fas fa-briefcase me-2"></i>Gerelateerde Vacatures
                    </h3>
                    <div class="related-grid">
                        @foreach($relatedVacancies as $related)
                            <div class="related-card">
                                <h4 class="related-title-small">{{ $related->title }}</h4>
                                <div class="related-company">{{ $related->company->name }}</div>
                                <div class="related-meta">
                                    @if($related->location)
                                        <span><i class="fas fa-map-marker-alt me-1"></i>{{ $related->location }}</span>
                                    @endif
                                    @if($related->employment_type)
                                        <span><i class="fas fa-clock me-1"></i>{{ $related->employment_type }}</span>
                                    @endif
                                </div>
                                <a href="{{ route('vacancies.show', ['company' => $related->company->slug, 'vacancy' => $related->id]) }}" class="related-link">
                                    Bekijk vacature <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function startApplication() {
            // Hier zou de AI chatbot flow gestart worden
            alert('AI Sollicitatie Assistant wordt gestart...');
        }
        
        function saveVacancy() {
            // Hier zou de vacature opgeslagen worden
            alert('Vacature opgeslagen in je favorieten!');
        }
    </script>
</body>
</html>


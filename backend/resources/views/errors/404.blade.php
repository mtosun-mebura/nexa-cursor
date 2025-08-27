<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Pagina Niet Gevonden</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }
        .error-icon {
            font-size: 4rem;
            color: #ffc107;
            margin-bottom: 1rem;
        }
        .error-title {
            color: #333;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        .error-message {
            color: #666;
            margin-bottom: 2rem;
        }
        .btn-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 600;
            margin-right: 1rem;
        }
        .btn-home {
            background: #28a745;
            border: none;
            padding: 0.75rem 2rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">
            <i class="fas fa-search"></i>
        </div>
        
        <h1 class="error-title">404 - Pagina Niet Gevonden</h1>
        
        <p class="error-message">
            De pagina die je zoekt bestaat niet of is verplaatst. 
            Controleer de URL of ga terug naar de hoofdpagina.
        </p>

        <div class="d-flex justify-content-center">
            <a href="{{ url()->previous() }}" class="btn btn-primary btn-back">
                <i class="fas fa-arrow-left"></i> Terug
            </a>
            
            @if(auth()->check())
                <a href="{{ route('admin.dashboard') }}" class="btn btn-success btn-home">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            @else
                <a href="{{ route('admin.login') }}" class="btn btn-success btn-home">
                    <i class="fas fa-sign-in-alt"></i> Inloggen
                </a>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

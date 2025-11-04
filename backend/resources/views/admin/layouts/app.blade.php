<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - Nexa Skillmatching</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Dark Mode Initial State -->
    <script>
    (() => {
      const el = document.documentElement
      const saved = localStorage.getItem('theme')
      const prefersDark = matchMedia('(prefers-color-scheme: dark)').matches
      el.classList.toggle('dark', saved ? saved === 'dark' : prefersDark)
      if (saved) {
        el.setAttribute('data-theme', saved)
      } else {
        el.setAttribute('data-theme', prefersDark ? 'dark' : 'light')
      }
    })()
    </script>
    
    <style>
        :root {
            /* Material Design Colors */
            --primary-color: #42A5F5;
            --primary-light: #E3F2FD;
            --primary-dark: #1976D2;
            --secondary-color: #1976D2;
            --secondary-light: #BBDEFB;
            --secondary-dark: #0D47A1;
            --tenant-color: #9E9E9E;
            --tenant-light: #BDBDBD;
            --tenant-dark: #757575;
            --surface-color: #FFFBFE;
            --surface-variant: #E7E0EC;
            --on-surface: #1C1B1F;
            --on-surface-variant: #49454F;
            --outline: #79747E;
            --outline-variant: #CAC4D0;
            --error: #BA1A1A;
            --success: #4CAF50;
            --warning: #FF9800;
            --info: #2196F3;
            
            /* Notification Colors */
            --notification-normal: #666666;
            --notification-high: #ff9800;
            --notification-urgent: #f44336;
            
            /* Elevation */
            --elevation-1: 0px 1px 3px 1px rgba(0, 0, 0, 0.15), 0px 1px 2px 0px rgba(0, 0, 0, 0.30);
            --elevation-2: 0px 2px 6px 2px rgba(0, 0, 0, 0.15), 0px 1px 2px 0px rgba(0, 0, 0, 0.30);
            --elevation-3: 0px 4px 8px 3px rgba(0, 0, 0, 0.15), 0px 1px 3px 0px rgba(0, 0, 0, 0.30);
            
            /* Spacing */
            --spacing-xs: 4px;
            --spacing-sm: 8px;
            --spacing-md: 16px;
            --spacing-lg: 24px;
            --spacing-xl: 32px;
            
            /* Border Radius */
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 24px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb; /* bg-gray-50 */
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: var(--on-surface);
            overflow-x: hidden;
        }
        
        /* Dark mode background */
        [data-theme="dark"] body,
        .dark body {
            background-color: #111827; /* bg-gray-900 */
        }

        html, body {
            height: 100%;
            width: 100%;
        }

        /* Layout */
        .admin-layout {
            display: flex;
            min-height: 100vh;
            width: 100%;
            position: relative;
        }

        /* Sidebar */
        .admin-sidebar {
            width: 280px;
            background: #f9fafb; /* bg-gray-50 - same as frontend */
            color: #111827; /* text-gray-900 */
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: var(--elevation-3);
            border-right: 1px solid #e5e7eb; /* border-gray-200 */
        }
        
        /* Dark mode sidebar */
        [data-theme="dark"] .admin-sidebar,
        .dark .admin-sidebar {
            background: #111827; /* bg-gray-900 - same as frontend */
            color: #f9fafb; /* text-gray-50 */
            border-right-color: #374151; /* border-gray-700 */
        }

        /* Logo Styling */
        .sidebar-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-lg);
            border-bottom: 1px solid #e5e7eb; /* border-gray-200 */
            text-align: center;
            background: #ffffff; /* bg-white */
            border-radius: var(--radius-md);
            margin: var(--spacing-md);
            box-shadow: var(--elevation-1);
        }
        
        /* Dark mode sidebar logo */
        [data-theme="dark"] .sidebar-logo,
        .dark .sidebar-logo {
            background: #1f2937; /* bg-gray-800 */
            border-bottom-color: #374151; /* border-gray-700 */
        }

        .logo-icon {
            width: 250px;
            height: auto;
            min-height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.3));
        }
        
        .responsive-logo {
            width: 100%;
            height: auto;
            max-width: 250px;
            padding: 15px;
            transition: all 0.3s ease;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
        }
        
        /* Responsive logo sizing */
        @media (max-width: 1200px) {
            .responsive-logo {
                max-width: 220px;
                padding: 12px;
            }
        }
        
        @media (max-width: 992px) {
            .responsive-logo {
                max-width: 200px;
                padding: 12px;
            }
        }
        
        @media (max-width: 768px) {
            .responsive-logo {
                max-width: 180px;
                padding: 10px;
            }
        }
        
        @media (max-width: 576px) {
            .responsive-logo {
                max-width: 160px;
                padding: 10px;
            }
        }

        .logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
            align-items: flex-start;
        }

        .logo-text .nexa {
            font-size: 1.6rem;
            font-weight: 700;
            font-style: italic;
            color: #ff6b35;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5), 0 0 20px rgba(255, 107, 53, 0.3);
            font-family: 'Brush Script MT', 'Segoe Script', cursive;
            letter-spacing: 0.5px;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
        }

        .logo-text .skillmatching {
            font-size: 0.85rem;
            font-weight: 500;
            color: #ff6b35;
            margin-left: 6px;
            font-family: 'Brush Script MT', 'Segoe Script', cursive;
            letter-spacing: 0.3px;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.4), 0 0 15px rgba(255, 107, 53, 0.2);
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.3));
        }

        .admin-sidebar.collapsed .logo-text {
            display: none;
        }

        .admin-sidebar.collapsed .logo-icon {
            width: 100px;
            height: 100px;
            margin: 0 auto;
        }

        .admin-sidebar.collapsed .responsive-logo {
            max-width: 100px;
            padding: 8px;
        }

        .admin-sidebar.collapsed {
            width: 80px;
        }

        .admin-sidebar.collapsed .tenant-selector {
            margin: var(--spacing-sm);
            padding: var(--spacing-sm);
            text-align: center;
        }

        .admin-sidebar.collapsed .tenant-selector label,
        .admin-sidebar.collapsed .tenant-selector select {
            display: none;
        }

        .admin-sidebar.collapsed .tenant-selector::after {
            content: '\f1ad';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            font-size: 1.2rem;
            color: #8B6914;
            text-shadow: 0 0 6px rgba(139, 105, 20, 0.45);
            filter: drop-shadow(0 1px 1px rgba(255, 255, 255, 0.45));
        }

        .admin-sidebar.collapsed .sidebar-header h5,
        .admin-sidebar.collapsed .sidebar-header small,
        .admin-sidebar.collapsed .nav-link span {
            display: none;
        }

        .admin-sidebar.collapsed .nav-link {
            justify-content: center;
            padding: var(--spacing-md);
        }

        .admin-sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 1.3rem;
        }

        .admin-sidebar.collapsed .nav-link.active::before {
            width: 3px;
        }

        .sidebar-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            text-align: center;
            background: rgba(0, 0, 0, 0.1);
        }

        .sidebar-header h5 {
            margin: 0;
            font-weight: 600;
            font-size: 1.25rem;
        }

        .sidebar-header small {
            opacity: 0.8;
            font-size: 0.875rem;
        }

        .sidebar-nav {
            padding: var(--spacing-md);
        }

        .nav-item {
            margin-bottom: var(--spacing-xs);
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: var(--spacing-md);
            color: #374151; /* text-gray-700 */
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            font-weight: 500;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }
        
        /* Dark mode nav link */
        [data-theme="dark"] .nav-link,
        .dark .nav-link {
            color: #d1d5db; /* text-gray-300 */
        }

        .nav-link:hover {
            background-color: #f3f4f6; /* bg-gray-100 */
            color: #111827; /* text-gray-900 */
            transform: translateX(4px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        /* Dark mode nav link hover */
        [data-theme="dark"] .nav-link:hover,
        .dark .nav-link:hover {
            background-color: #1f2937; /* bg-gray-800 */
            color: #f9fafb; /* text-gray-50 */
        }

        .nav-link.active {
            background-color: #e5e7eb; /* bg-gray-200 */
            color: #111827; /* text-gray-900 */
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #3b82f6; /* border-blue-500 */
        }
        
        /* Dark mode nav link active */
        [data-theme="dark"] .nav-link.active,
        .dark .nav-link.active {
            background-color: #374151; /* bg-gray-700 */
            color: #f9fafb; /* text-gray-50 */
            border-left-color: #60a5fa; /* border-blue-400 */
        }

        .nav-link.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background-color: white;
            border-radius: 0 2px 2px 0;
        }

        .nav-link i {
            margin-right: var(--spacing-md);
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        /* Tenant Selector - Material Design Light Gold Theme */
        .tenant-selector {
            margin: var(--spacing-md);
            padding: var(--spacing-lg);
            background: #ffffff; /* bg-white */
            border-radius: var(--radius-md);
            border: 1px solid #e5e7eb; /* border-gray-200 */
            box-shadow: var(--elevation-1);
            transition: box-shadow 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        /* Dark mode tenant selector */
        [data-theme="dark"] .tenant-selector,
        .dark .tenant-selector {
            background: #1f2937; /* bg-gray-800 */
            border-color: #374151; /* border-gray-700 */
        }

        /* Removed hover animation to prevent arrow display issues */

        .tenant-selector:hover {
            /* Subtle hover effect without interfering with dropdown */
            box-shadow: var(--elevation-2);
        }

        .tenant-selector label {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-md);
            font-size: 0.875rem;
            font-weight: 700; /* Extra bold voor betere leesbaarheid */
            color: #111827 !important; /* text-gray-900 - donkere kleur voor goede leesbaarheid */
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Dark mode tenant selector label */
        [data-theme="dark"] .tenant-selector label,
        .dark .tenant-selector label {
            color: #f9fafb !important; /* text-gray-50 */
        }

        .tenant-selector label i {
            margin-right: var(--spacing-sm);
            font-size: 0.875rem;
            color: #111827 !important; /* text-gray-900 - donkere kleur voor goede leesbaarheid */
        }
        
        /* Dark mode tenant selector label icon */
        [data-theme="dark"] .tenant-selector label i,
        .dark .tenant-selector label i {
            color: #f9fafb !important; /* text-gray-50 */
        }

        .tenant-selector select {
            width: 100%;
            padding: var(--spacing-md) var(--spacing-lg);
            border: 2px solid #9ca3af; /* border-gray-400 - donkerdere border voor duidelijk contrast */
            border-radius: var(--radius-md);
            background-color: #ffffff; /* bg-white - witte achtergrond voor duidelijk contrast */
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23111827' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right var(--spacing-md) center;
            background-size: 18px;
            color: #111827 !important; /* text-gray-900 - donkere tekst voor goede leesbaarheid */
            font-size: 0.875rem;
            font-weight: 600; /* vettere tekst voor betere leesbaarheid */
            transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: calc(var(--spacing-lg) + 24px);
            position: relative;
            z-index: 2;
            cursor: pointer;
        }
        
        /* Light mode hover and focus states */
        .tenant-selector select:hover {
            border-color: #6b7280; /* border-gray-500 - donkerdere border bij hover */
            background-color: #f9fafb; /* bg-gray-50 - subtiele achtergrond verandering */
            color: #111827 !important; /* text-gray-900 - behoud donkere tekst */
        }
        
        .tenant-selector select:focus {
            outline: none;
            border-color: #3b82f6; /* border-blue-500 */
            background-color: #ffffff; /* bg-white */
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); /* focus ring */
            color: #111827 !important; /* text-gray-900 - behoud donkere tekst */
        }
        
        /* Ensure placeholder text is also dark */
        .tenant-selector select::placeholder {
            color: #6b7280 !important; /* text-gray-500 */
            opacity: 1;
        }
        
        /* Dark mode tenant selector select */
        [data-theme="dark"] .tenant-selector select,
        .dark .tenant-selector select {
            background-color: #374151; /* bg-gray-700 */
            border-color: #4b5563; /* border-gray-600 */
            color: #f9fafb; /* text-gray-50 */
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23f9fafb' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        }
        
        [data-theme="dark"] .tenant-selector select:hover,
        .dark .tenant-selector select:hover {
            border-color: #6b7280; /* border-gray-500 */
            background-color: #4b5563; /* bg-gray-600 */
        }
        
        [data-theme="dark"] .tenant-selector select:focus,
        .dark .tenant-selector select:focus {
            outline: none;
            border-color: #60a5fa; /* border-blue-400 */
            background-color: #374151; /* bg-gray-700 */
            box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.25); /* focus ring */
        }


        /* Light mode options */
        .tenant-selector select option {
            background-color: #ffffff; /* bg-white */
            color: #111827; /* text-gray-900 */
            padding: var(--spacing-md);
            font-weight: 500;
            border: none;
        }

        .tenant-selector select option:hover {
            background-color: #f3f4f6; /* bg-gray-100 */
        }

        .tenant-selector select option:checked {
            background: #3b82f6; /* bg-blue-500 */
            color: #ffffff;
        }
        
        /* Dark mode options */
        [data-theme="dark"] .tenant-selector select option,
        .dark .tenant-selector select option {
            background-color: #374151; /* bg-gray-700 */
            color: #f9fafb; /* text-gray-50 */
        }

        [data-theme="dark"] .tenant-selector select option:hover,
        .dark .tenant-selector select option:hover {
            background-color: #4b5563; /* bg-gray-600 */
        }

        [data-theme="dark"] .tenant-selector select option:checked,
        .dark .tenant-selector select option:checked {
            background: #60a5fa; /* bg-blue-400 */
            color: #ffffff;
        }

        /* Dark mode support */
        [data-theme="dark"] .tenant-selector label {
            color: #FFFFFF;
        }

        [data-theme="dark"] .tenant-selector label i {
            color: #FFFFFF;
        }

        [data-theme="dark"] .tenant-selector select {
            color: #FFFFFF;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23FFFFFF' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right var(--spacing-md) center;
            background-size: 16px;
        }

        [data-theme="dark"] .tenant-selector select:hover {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23FFFFFF' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right var(--spacing-md) center;
            background-size: 16px;
        }

        [data-theme="dark"] .tenant-selector select:focus {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23FFFFFF' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right var(--spacing-md) center;
            background-size: 16px;
        }

        @media (prefers-color-scheme: dark) {
            .tenant-selector label {
                color: #FFFFFF;
            }

            .tenant-selector label i {
                color: #FFFFFF;
            }

            .tenant-selector select {
                color: #FFFFFF;
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23FFFFFF' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
                background-repeat: no-repeat;
                background-position: right var(--spacing-md) center;
                background-size: 16px;
            }

            .tenant-selector select:hover {
                background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23FFFFFF' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
                background-repeat: no-repeat;
                background-position: right var(--spacing-md) center;
                background-size: 16px;
            }
        }



        /* Main Content */
        .admin-main {
            flex: 1;
            margin-left: 280px;
            transition: all 0.3s ease;
            width: calc(100% - 280px);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
            background-color: #f9fafb; /* bg-gray-50 - same as frontend */
        }
        
        /* Dark mode main content */
        [data-theme="dark"] .admin-main,
        .dark .admin-main {
            background-color: #111827; /* bg-gray-900 - same as frontend */
        }

        .admin-main.expanded {
            margin-left: 80px;
            width: calc(100% - 80px);
        }

        /* Header */
        .admin-header {
            background-color: #f9fafb; /* bg-gray-50 - same as frontend */
            border-bottom: 1px solid #e5e7eb; /* border-gray-200 */
            padding: var(--spacing-md) var(--spacing-lg);
            box-shadow: var(--elevation-1);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        /* Dark mode header */
        [data-theme="dark"] .admin-header,
        .dark .admin-header {
            background-color: #111827; /* bg-gray-900 - same as frontend */
            border-bottom-color: #374151; /* border-gray-700 */
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--spacing-md);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .menu-toggle {
            background: none;
            border: none;
            color: #111827; /* text-gray-900 */
            font-size: 1.5rem;
            cursor: pointer;
            padding: var(--spacing-sm);
            border-radius: var(--radius-sm);
            transition: all 0.2s ease;
            display: block;
        }
        
        /* Dark mode menu toggle */
        [data-theme="dark"] .menu-toggle,
        .dark .menu-toggle {
            color: #f9fafb; /* text-gray-50 */
        }

        .menu-toggle:hover {
            background-color: #f3f4f6; /* bg-gray-100 */
        }
        
        /* Dark mode menu toggle hover */
        [data-theme="dark"] .menu-toggle:hover,
        .dark .menu-toggle:hover {
            background-color: #374151; /* bg-gray-700 */
        }

        .page-title {
            margin: 0;
            font-weight: 600;
            font-size: 1.5rem;
            color: #111827; /* text-gray-900 */
        }
        
        /* Dark mode page title */
        [data-theme="dark"] .page-title,
        .dark .page-title {
            color: #f9fafb; /* text-gray-50 */
        }

        .user-menu {
            position: relative;
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .user-button {
            background: transparent; /* Transparent to match header background */
            border: 1px solid #e5e7eb; /* border-gray-200 */
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            transition: all 0.2s ease;
            font-weight: 500;
            color: #111827; /* text-gray-900 */
        }

        .user-button:hover {
            background-color: #f3f4f6; /* bg-gray-100 */
        }
        
        /* Dark mode user button */
        [data-theme="dark"] .user-button,
        .dark .user-button {
            background: transparent !important; /* Transparent to match header background */
            border-color: #374151 !important; /* border-gray-700 */
            color: #f9fafb !important; /* text-gray-50 */
        }
        
        [data-theme="dark"] .user-button:hover,
        .dark .user-button:hover {
            background-color: #374151 !important; /* bg-gray-700 */
        }

        .user-button span {
            color: #111827; /* text-gray-900 */
            font-weight: 500;
        }
        
        /* Dark mode user button span */
        [data-theme="dark"] .user-button span,
        .dark .user-button span {
            color: #f9fafb !important; /* text-gray-50 */
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        /* Theme Toggle Button */
        .theme-toggle-button {
            position: relative;
            background: transparent;
            border: 1px solid #e5e7eb; /* border-gray-200 */
            padding: var(--spacing-sm);
            border-radius: var(--radius-md);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            color: #111827; /* text-gray-900 */
            width: 40px;
            height: 40px;
            margin-right: var(--spacing-sm);
        }
        
        .theme-toggle-button:hover {
            background-color: #f3f4f6; /* bg-gray-100 */
            border-color: #d1d5db; /* border-gray-300 */
        }
        
        .theme-icon-light,
        .theme-icon-dark {
            font-size: 1.5rem; /* Groter icoon */
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: absolute;
        }
        
        .theme-icon-light {
            display: flex;
            opacity: 1;
        }
        
        .theme-icon-dark {
            display: none;
            opacity: 0;
        }
        
        /* Dark mode theme toggle */
        [data-theme="dark"] .theme-toggle-button,
        .dark .theme-toggle-button {
            border-color: #374151; /* border-gray-700 */
            color: #f9fafb; /* text-gray-50 */
        }
        
        [data-theme="dark"] .theme-toggle-button:hover,
        .dark .theme-toggle-button:hover {
            background-color: #374151; /* bg-gray-700 */
        }
        
        [data-theme="dark"] .theme-icon-light,
        .dark .theme-icon-light {
            display: none;
            opacity: 0;
        }
        
        [data-theme="dark"] .theme-icon-dark,
        .dark .theme-icon-dark {
            display: flex;
            opacity: 1;
        }

        /* User Dropdown Menu */
        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background-color: white;
            border: 1px solid #e0e0e0;
            border-radius: var(--radius-md);
            box-shadow: var(--elevation-2);
            z-index: 1000;
            min-width: 200px;
            margin-top: var(--spacing-sm);
            /* Ensure dropdown doesn't overlap with trigger button */
            transform: translateY(8px);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            padding: var(--spacing-md);
            color: var(--on-surface);
            text-decoration: none;
            transition: background-color 0.2s ease;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
        }

        .dropdown-item:hover {
            background-color: #f5f5f5;
        }

        .dropdown-item i {
            margin-right: var(--spacing-sm);
            width: 16px;
            text-align: center;
        }

        .dropdown-divider {
            height: 1px;
            background-color: #e0e0e0;
            margin: var(--spacing-xs) 0;
        }

        /* Header Actions */
        .header-actions {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        /* Notification Bell */
        .notification-bell-container {
            position: relative;
            /* Ensure container doesn't overflow viewport */
            max-width: 100%;
        }

        .notification-bell {
            background: none;
            border: 1px solid #e0e0e0;
            padding: var(--spacing-sm);
            border-radius: var(--radius-md);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            color: var(--on-surface);
            position: relative;
            width: 40px;
            height: 40px;
        }

        .notification-bell:hover {
            background-color: #f5f5f5;
            transform: translateY(-1px);
        }

        .notification-bell i {
            font-size: 1.2rem;
            color: #666;
            transition: color 0.3s ease;
        }

        .notification-bell .notification-icon-normal {
            color: #666;
        }

        .notification-bell .notification-icon-high {
            color: #ff9800;
        }

        .notification-bell .notification-icon-urgent {
            color: #f44336;
        }

        .notification-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #ff4757;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 600;
            border: 2px solid white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .notification-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            width: 400px;
            max-height: 500px;
            background-color: #ffffff; /* bg-white */
            border: 1px solid #e5e7eb; /* border-gray-200 */
            border-radius: var(--radius-lg);
            box-shadow: var(--elevation-3);
            z-index: 1000;
            margin-top: var(--spacing-sm);
            overflow: hidden;
            /* Ensure dropdown stays within viewport */
            max-width: calc(100vw - 40px);
            max-height: calc(100vh - 100px);
        }
        
        /* Dark mode notification dropdown */
        [data-theme="dark"] .notification-dropdown,
        .dark .notification-dropdown {
            background-color: #1f2937; /* bg-gray-800 */
            border-color: #374151; /* border-gray-700 */
        }

        .notification-header {
            padding: var(--spacing-md);
            border-bottom: 1px solid #e5e7eb; /* border-gray-200 */
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f9fafb; /* bg-gray-50 */
        }
        
        /* Dark mode notification header */
        [data-theme="dark"] .notification-header,
        .dark .notification-header {
            background-color: #1f2937; /* bg-gray-800 */
            border-bottom-color: #374151; /* border-gray-700 */
        }

        .notification-header h4 {
            margin: 0;
            font-weight: 600;
            color: #111827; /* text-gray-900 */
        }
        
        /* Dark mode notification header h4 */
        [data-theme="dark"] .notification-header h4,
        .dark .notification-header h4 {
            color: #f9fafb; /* text-gray-50 */
        }

        .mark-all-read {
            background: none;
            border: none;
            color: var(--primary-color);
            font-size: 0.875rem;
            cursor: pointer;
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            transition: all 0.2s ease;
        }

        .mark-all-read:hover {
            background-color: rgba(var(--primary-color-rgb), 0.1);
        }

        .notification-list {
            max-height: 350px;
            overflow-y: auto;
        }

        .notification-item {
            padding: var(--spacing-md);
            border-bottom: 1px solid #e5e7eb; /* border-gray-200 */
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-sm);
            background-color: #ffffff; /* bg-white */
        }
        
        /* Dark mode notification item */
        [data-theme="dark"] .notification-item,
        .dark .notification-item {
            background-color: #1f2937; /* bg-gray-800 */
            border-bottom-color: #374151; /* border-gray-700 */
        }

        .notification-item:hover {
            background-color: #f3f4f6; /* bg-gray-100 */
        }
        
        /* Dark mode notification item hover */
        [data-theme="dark"] .notification-item:hover,
        .dark .notification-item:hover {
            background-color: #374151; /* bg-gray-700 */
        }

        .notification-item.read {
            opacity: 0.7;
        }

        .notification-item.unread {
            background-color: #eff6ff; /* bg-blue-50 */
        }
        
        /* Dark mode notification item unread */
        [data-theme="dark"] .notification-item.unread,
        .dark .notification-item.unread {
            background-color: #1e3a8a; /* bg-blue-900 */
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .notification-icon.priority-normal {
            background-color: #f5f5f5;
            color: var(--notification-normal);
        }

        .notification-icon.priority-high {
            background-color: #fff3e0;
            color: var(--notification-high);
        }

        .notification-icon.priority-urgent {
            background-color: #ffebee;
            color: var(--notification-urgent);
        }

        .notification-content {
            flex: 1;
            min-width: 0;
        }

        .notification-title {
            font-weight: 600;
            color: #111827; /* text-gray-900 */
            margin-bottom: var(--spacing-xs);
            font-size: 0.875rem;
        }
        
        /* Dark mode notification title */
        [data-theme="dark"] .notification-title,
        .dark .notification-title {
            color: #f9fafb; /* text-gray-50 */
        }

        .notification-message {
            color: #6b7280; /* text-gray-500 */
            font-size: 0.8rem;
            margin-bottom: var(--spacing-xs);
            line-height: 1.4;
        }
        
        /* Dark mode notification message */
        [data-theme="dark"] .notification-message,
        .dark .notification-message {
            color: #d1d5db; /* text-gray-300 */
        }

        .notification-time {
            color: #9ca3af; /* text-gray-400 */
            font-size: 0.75rem;
        }
        
        /* Dark mode notification time */
        [data-theme="dark"] .notification-time,
        .dark .notification-time {
            color: #6b7280; /* text-gray-500 */
        }

        .notification-status {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            flex-shrink: 0;
        }

        .notification-status i {
            color: var(--primary-color);
            font-size: 0.5rem;
        }

        .notification-empty {
            padding: var(--spacing-lg);
            text-align: center;
            color: #9ca3af; /* text-gray-400 */
        }
        
        /* Dark mode notification empty */
        [data-theme="dark"] .notification-empty,
        .dark .notification-empty {
            color: #6b7280; /* text-gray-500 */
        }

        .notification-empty i {
            font-size: 2rem;
            margin-bottom: var(--spacing-sm);
        }

        .notification-footer {
            padding: var(--spacing-md);
            border-top: 1px solid #e5e7eb; /* border-gray-200 */
            background-color: #f9fafb; /* bg-gray-50 */
        }
        
        /* Dark mode notification footer */
        [data-theme="dark"] .notification-footer,
        .dark .notification-footer {
            background-color: #1f2937; /* bg-gray-800 */
            border-top-color: #374151; /* border-gray-700 */
        }

        .view-all-notifications {
            display: block;
            text-align: center;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            padding: var(--spacing-sm);
            border-radius: var(--radius-sm);
            transition: all 0.2s ease;
        }

        .view-all-notifications:hover {
            background-color: rgba(var(--primary-color-rgb), 0.1);
        }

        /* Responsive Notification Bell */
        @media (max-width: 768px) {
            .notification-dropdown {
                width: 320px;
                max-width: calc(100vw - 20px);
            }
            
            .dropdown-menu {
                min-width: 180px;
            }
            
            .user-menu {
                gap: var(--spacing-sm);
            }
        }

        @media (max-width: 480px) {
            .notification-dropdown {
                width: 280px;
                max-width: calc(100vw - 20px);
            }
            
            .dropdown-menu {
                min-width: 160px;
            }
            
            .notification-item {
                padding: var(--spacing-sm);
            }
            
            .notification-icon {
                width: 32px;
                height: 32px;
                font-size: 0.875rem;
            }
            
            .dropdown-item {
                padding: var(--spacing-sm);
                font-size: 0.875rem;
            }
        }

        /* Content Area */
        .admin-content {
            padding: var(--spacing-lg);
            background-color: #f9fafb; /* bg-gray-50 - same as frontend */
            flex: 1;
            width: 100%;
            min-height: calc(100vh - 80px);
            overflow-x: hidden;
        }

        /* Material Design Components */
        .material-card {
            background: #ffffff; /* bg-white */
            border-radius: var(--radius-lg);
            box-shadow: var(--elevation-1);
            border: none;
            margin-bottom: var(--spacing-lg);
            transition: box-shadow 0.3s ease;
            color: #111827; /* text-gray-900 */
        }
        
        /* Dark mode material card */
        [data-theme="dark"] .material-card,
        .dark .material-card {
            background: #1f2937 !important; /* bg-gray-800 */
            color: #f9fafb !important; /* text-gray-50 */
        }

        .material-card:hover {
            box-shadow: var(--elevation-2);
        }

        .material-card .card-body {
            padding: 0;
        }

        .material-btn {
            border-radius: var(--radius-md);
            text-transform: uppercase;
            font-weight: 500;
            letter-spacing: 0.5px;
            padding: var(--spacing-md) var(--spacing-lg);
            border: none;
            transition: all 0.3s ease;
            box-shadow: var(--elevation-1);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-sm);
            text-decoration: none;
        }

        .material-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--elevation-2);
        }

        .material-btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .material-btn-secondary {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--secondary-dark) 100%);
            color: white;
        }

        .material-btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .material-btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Alerts */
        .alert {
            border-radius: var(--radius-md);
            border: none;
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }

        .alert-success {
            background-color: #e8f5e8;
            color: #1b5e20;
        }

        .alert-danger {
            background-color: #ffebee;
            color: #c62828;
        }

        .alert-warning {
            background-color: #fff3e0;
            color: #e65100;
        }

        .alert-info {
            background-color: #e3f2fd;
            color: #1565c0;
        }

        /* Settings Modal */
        .settings-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
        }

        .settings-modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .settings-content {
            background-color: #ffffff; /* bg-white */
            border-radius: var(--radius-lg);
            box-shadow: var(--elevation-4);
            width: 90%;
            color: #111827; /* text-gray-900 */
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .settings-header {
            padding: var(--spacing-lg);
            border-bottom: 1px solid #e5e7eb; /* border-gray-200 */
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #ffffff; /* bg-white */
        }
        
        /* Dark mode settings header */
        [data-theme="dark"] .settings-header,
        .dark .settings-header {
            background-color: #1f2937; /* bg-gray-800 */
            border-bottom-color: #374151; /* border-gray-700 */
            color: #f9fafb; /* text-gray-50 */
        }

        .settings-header h3 {
            margin: 0;
            font-weight: 600;
            color: #111827; /* text-gray-900 */
        }
        
        /* Dark mode settings header h3 */
        [data-theme="dark"] .settings-header h3,
        .dark .settings-header h3 {
            color: #f9fafb; /* text-gray-50 */
        }

        .settings-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #666;
            padding: var(--spacing-sm);
            border-radius: var(--radius-sm);
            transition: all 0.2s ease;
        }

        .settings-close:hover {
            background-color: #f5f5f5;
            color: #333;
        }

        .settings-body {
            padding: var(--spacing-lg);
            background-color: #ffffff; /* bg-white */
            color: #111827; /* text-gray-900 */
        }
        
        /* Dark mode settings body */
        [data-theme="dark"] .settings-body,
        .dark .settings-body {
            background-color: #1f2937; /* bg-gray-800 */
            color: #f9fafb; /* text-gray-50 */
        }

        .setting-group {
            margin-bottom: var(--spacing-lg);
        }

        .setting-group:last-child {
            margin-bottom: 0;
        }

        .setting-label {
            font-weight: 600;
            color: #111827; /* text-gray-900 */
            margin-bottom: var(--spacing-sm);
            display: block;
        }
        
        /* Dark mode setting label */
        [data-theme="dark"] .setting-label,
        .dark .setting-label {
            color: #f9fafb; /* text-gray-50 */
        }

        .setting-description {
            color: #6b7280; /* text-gray-500 */
            font-size: 0.875rem;
            margin-bottom: var(--spacing-md);
        }
        
        /* Dark mode setting description */
        [data-theme="dark"] .setting-description,
        .dark .setting-description {
            color: #9ca3af; /* text-gray-400 */
        }

        .theme-toggle {
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
            padding: var(--spacing-md);
            border: 2px solid #e0e0e0;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .theme-toggle:hover {
            border-color: var(--primary-color);
            background-color: #f8f9fa;
        }

        .theme-toggle.active {
            border-color: var(--primary-color);
            background-color: rgba(76, 175, 80, 0.1);
        }

        .theme-icon {
            font-size: 1.5rem;
            width: 40px;
            text-align: center;
        }

        .theme-info {
            flex: 1;
        }

        .theme-name {
            font-weight: 600;
            color: var(--on-surface);
            margin-bottom: 2px;
        }

        .theme-description {
            font-size: 0.875rem;
            color: #666;
        }

        .theme-check {
            color: var(--primary-color);
            font-size: 1.25rem;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .theme-toggle.active .theme-check {
            opacity: 1;
        }

        /* Dark Mode Styles */
        [data-theme="dark"] {
            --surface-color: #121212;
            --on-surface: #ffffff;
            --background-color: #1e1e1e;
            --card-background: #2d2d2d;
            --border-color: #404040;
            --text-muted: #b0b0b0;
        }

        /* Dark mode admin-header - already defined above, keeping for backwards compatibility */
        [data-theme="dark"] .admin-header {
            background-color: #111827 !important; /* bg-gray-900 - same as frontend */
            border-bottom-color: #374151 !important; /* border-gray-700 */
        }

        [data-theme="dark"] .admin-content,
        .dark .admin-content {
            background-color: #111827; /* bg-gray-900 - same as frontend */
        }

        /* Dark mode material card - already defined above with correct colors */
        [data-theme="dark"] .material-card {
            background-color: #1f2937 !important; /* bg-gray-800 */
            color: #f9fafb !important; /* text-gray-50 */
        }

        [data-theme="dark"] .settings-content,
        .dark .settings-content {
            background-color: #1f2937 !important; /* bg-gray-800 */
            color: #f9fafb !important; /* text-gray-50 */
        }

        /* Tables - Dark Mode */
        [data-theme="dark"] table,
        [data-theme="dark"] .table,
        [data-theme="dark"] .material-table,
        .dark table,
        .dark .table,
        .dark .material-table {
            background-color: #1f2937 !important; /* bg-gray-800 */
            color: #f9fafb !important; /* text-gray-50 */
        }

        [data-theme="dark"] table thead th,
        [data-theme="dark"] .table thead th,
        [data-theme="dark"] .material-table thead th,
        .dark table thead th,
        .dark .table thead th,
        .dark .material-table thead th {
            background-color: #374151 !important; /* bg-gray-700 */
            color: #f9fafb !important; /* text-gray-50 */
            border-color: #4b5563 !important; /* border-gray-600 */
        }

        [data-theme="dark"] table tbody tr,
        [data-theme="dark"] .table tbody tr,
        [data-theme="dark"] .material-table tbody tr,
        .dark table tbody tr,
        .dark .table tbody tr,
        .dark .material-table tbody tr {
            background-color: #1f2937 !important; /* bg-gray-800 */
            color: #f9fafb !important; /* text-gray-50 */
        }

        [data-theme="dark"] table tbody tr:hover,
        [data-theme="dark"] .table tbody tr:hover,
        [data-theme="dark"] .material-table tbody tr:hover,
        [data-theme="dark"] .table-hover tbody tr:hover,
        .dark table tbody tr:hover,
        .dark .table tbody tr:hover,
        .dark .material-table tbody tr:hover,
        .dark .table-hover tbody tr:hover {
            background-color: #374151 !important; /* bg-gray-700 */
        }

        [data-theme="dark"] table tbody td,
        [data-theme="dark"] .table tbody td,
        [data-theme="dark"] .material-table tbody td,
        [data-theme="dark"] table tbody th,
        [data-theme="dark"] .table tbody th,
        [data-theme="dark"] .material-table tbody th,
        .dark table tbody td,
        .dark .table tbody td,
        .dark .material-table tbody td,
        .dark table tbody th,
        .dark .table tbody th,
        .dark .material-table tbody th {
            background-color: transparent !important;
            color: #f9fafb !important; /* text-gray-50 */
            border-color: #374151 !important; /* border-gray-700 */
        }

        /* Forms - Dark Mode */
        [data-theme="dark"] .form-control,
        [data-theme="dark"] .form-select,
        [data-theme="dark"] input,
        [data-theme="dark"] textarea,
        [data-theme="dark"] select,
        .dark .form-control,
        .dark .form-select,
        .dark input,
        .dark textarea,
        .dark select {
            background-color: #374151 !important; /* bg-gray-700 */
            color: #f9fafb !important; /* text-gray-50 */
            border-color: #4b5563 !important; /* border-gray-600 */
        }

        [data-theme="dark"] .form-control:focus,
        [data-theme="dark"] .form-select:focus,
        [data-theme="dark"] input:focus,
        [data-theme="dark"] textarea:focus,
        [data-theme="dark"] select:focus,
        .dark .form-control:focus,
        .dark .form-select:focus,
        .dark input:focus,
        .dark textarea:focus,
        .dark select:focus {
            background-color: #374151 !important; /* bg-gray-700 */
            color: #f9fafb !important; /* text-gray-50 */
            border-color: #60a5fa !important; /* border-blue-400 */
            box-shadow: 0 0 0 0.2rem rgba(96, 165, 250, 0.25) !important;
        }

        [data-theme="dark"] .form-label,
        [data-theme="dark"] label,
        .dark .form-label,
        .dark label {
            color: #f9fafb !important; /* text-gray-50 */
        }

        /* Cards and Content Blocks - Dark Mode */
        [data-theme="dark"] .card,
        [data-theme="dark"] .card-body,
        [data-theme="dark"] .card-header,
        .dark .card,
        .dark .card-body,
        .dark .card-header {
            background-color: #1f2937 !important; /* bg-gray-800 */
            color: #f9fafb !important; /* text-gray-50 */
            border-color: #374151 !important; /* border-gray-700 */
        }

        [data-theme="dark"] .card-header,
        .dark .card-header {
            background-color: #374151 !important; /* bg-gray-700 */
            border-bottom-color: #4b5563 !important; /* border-gray-600 */
        }

        /* All text elements - Dark Mode */
        [data-theme="dark"] p,
        [data-theme="dark"] span,
        [data-theme="dark"] div,
        [data-theme="dark"] h1,
        [data-theme="dark"] h2,
        [data-theme="dark"] h3,
        [data-theme="dark"] h4,
        [data-theme="dark"] h5,
        [data-theme="dark"] h6,
        .dark p,
        .dark span,
        .dark div,
        .dark h1,
        .dark h2,
        .dark h3,
        .dark h4,
        .dark h5,
        .dark h6 {
            color: #f9fafb !important; /* text-gray-50 */
        }

        /* Override for specific text colors that should remain */
        [data-theme="dark"] .text-muted,
        [data-theme="dark"] .text-secondary,
        .dark .text-muted,
        .dark .text-secondary {
            color: #9ca3af !important; /* text-gray-400 */
        }

        /* Badges and Status - Dark Mode */
        [data-theme="dark"] .badge,
        [data-theme="dark"] .status-badge,
        [data-theme="dark"] .material-badge,
        .dark .badge,
        .dark .status-badge,
        .dark .material-badge {
            color: #ffffff !important; /* White text for better readability */
        }
        
        /* Material Badge variants - Dark Mode - ensure text is readable */
        [data-theme="dark"] .material-badge-info,
        [data-theme="dark"] .material-badge-success,
        [data-theme="dark"] .material-badge-warning,
        [data-theme="dark"] .material-badge-danger,
        [data-theme="dark"] .material-badge-secondary,
        .dark .material-badge-info,
        .dark .material-badge-success,
        .dark .material-badge-warning,
        .dark .material-badge-danger,
        .dark .material-badge-secondary {
            color: #ffffff !important; /* White text for all badges */
        }
        
        /* Status Badge variants - Dark Mode */
        [data-theme="dark"] .status-active,
        [data-theme="dark"] .status-badge.active,
        .dark .status-active,
        .dark .status-badge.active {
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%) !important; /* Darker green for better contrast */
            color: #ffffff !important; /* White text */
            border-color: #22c55e !important;
        }
        
        [data-theme="dark"] .status-inactive,
        [data-theme="dark"] .status-badge.inactive,
        .dark .status-inactive,
        .dark .status-badge.inactive {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%) !important; /* Darker red for better contrast */
            color: #ffffff !important; /* White text */
            border-color: #ef4444 !important;
        }
        
        /* Override light backgrounds for badges in dark mode */
        [data-theme="dark"] .status-badge[style*="background: linear-gradient"][style*="#4caf50"],
        [data-theme="dark"] .status-badge[style*="background: linear-gradient"][style*="#e8f5e8"],
        [data-theme="dark"] .material-badge[style*="background: linear-gradient"][style*="#4caf50"],
        [data-theme="dark"] .material-badge[style*="background: linear-gradient"][style*="#e8f5e8"],
        .dark .status-badge[style*="background: linear-gradient"][style*="#4caf50"],
        .dark .status-badge[style*="background: linear-gradient"][style*="#e8f5e8"],
        .dark .material-badge[style*="background: linear-gradient"][style*="#4caf50"],
        .dark .material-badge[style*="background: linear-gradient"][style*="#e8f5e8"] {
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%) !important; /* Darker green */
            color: #ffffff !important;
        }
        
        /* Template badges - Dark Mode */
        [data-theme="dark"] .template-type,
        [data-theme="dark"] .template-company,
        .dark .template-type,
        .dark .template-company {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%) !important; /* Dark gray */
            color: #f9fafb !important; /* White text */
        }
        
        /* Override all light gradient backgrounds for badges in dark mode */
        [data-theme="dark"] [class*="badge"][style*="background: linear-gradient"][style*="#e8f5e8"],
        [data-theme="dark"] [class*="badge"][style*="background: linear-gradient"][style*="#c8e6c9"],
        [data-theme="dark"] [class*="badge"][style*="background: linear-gradient"][style*="#e3f2fd"],
        [data-theme="dark"] [class*="badge"][style*="background: linear-gradient"][style*="#bbdefb"],
        [data-theme="dark"] [class*="badge"][style*="background: linear-gradient"][style*="#fff3e0"],
        [data-theme="dark"] [class*="badge"][style*="background: linear-gradient"][style*="#f5f5f5"],
        [data-theme="dark"] [class*="badge"][style*="background: linear-gradient"][style*="#e0e0e0"],
        [data-theme="dark"] [class*="badge"][style*="background: linear-gradient"][style*="#ffe0b2"],
        .dark [class*="badge"][style*="background: linear-gradient"][style*="#e8f5e8"],
        .dark [class*="badge"][style*="background: linear-gradient"][style*="#c8e6c9"],
        .dark [class*="badge"][style*="background: linear-gradient"][style*="#e3f2fd"],
        .dark [class*="badge"][style*="background: linear-gradient"][style*="#bbdefb"],
        .dark [class*="badge"][style*="background: linear-gradient"][style*="#fff3e0"],
        .dark [class*="badge"][style*="background: linear-gradient"][style*="#f5f5f5"],
        .dark [class*="badge"][style*="background: linear-gradient"][style*="#e0e0e0"],
        .dark [class*="badge"][style*="background: linear-gradient"][style*="#ffe0b2"] {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%) !important; /* Dark gray */
            color: #f9fafb !important; /* White text */
        }
        
        /* Material badge variants - Dark Mode - force dark backgrounds */
        [data-theme="dark"] .material-badge-success,
        [data-theme="dark"] .material-badge-info,
        [data-theme="dark"] .material-badge-primary,
        [data-theme="dark"] .material-badge-warning,
        [data-theme="dark"] .material-badge-secondary,
        .dark .material-badge-success,
        .dark .material-badge-info,
        .dark .material-badge-primary,
        .dark .material-badge-warning,
        .dark .material-badge-secondary {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%) !important; /* Dark gray for all badges */
            color: #ffffff !important; /* White text */
            border: 1px solid #4b5563 !important;
        }
        
        /* Success badges should be darker green in dark mode */
        [data-theme="dark"] .material-badge-success,
        .dark .material-badge-success {
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%) !important; /* Darker green */
            color: #ffffff !important;
        }
        
        /* Info/Primary badges should be darker blue in dark mode */
        [data-theme="dark"] .material-badge-info,
        [data-theme="dark"] .material-badge-primary,
        [data-theme="dark"] .material-badge-info[style*="background"],
        [data-theme="dark"] .material-badge-primary[style*="background"],
        .dark .material-badge-info,
        .dark .material-badge-primary,
        .dark .material-badge-info[style*="background"],
        .dark .material-badge-primary[style*="background"] {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%) !important; /* Darker blue */
            color: #ffffff !important;
        }
        
        /* Force all badges with light blue backgrounds to be darker in dark mode */
        [data-theme="dark"] [class*="badge"][style*="#e3f2fd"],
        [data-theme="dark"] [class*="badge"][style*="#bbdefb"],
        [data-theme="dark"] [class*="badge"][style*="#1565c0"],
        [data-theme="dark"] [class*="badge"][style*="#0c5460"],
        [data-theme="dark"] [class*="badge"][style*="#d1ecf1"],
        .dark [class*="badge"][style*="#e3f2fd"],
        .dark [class*="badge"][style*="#bbdefb"],
        .dark [class*="badge"][style*="#1565c0"],
        .dark [class*="badge"][style*="#0c5460"],
        .dark [class*="badge"][style*="#d1ecf1"] {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%) !important; /* Darker blue */
            color: #ffffff !important;
        }
        
        /* Force all badges with light gray backgrounds to be darker in dark mode */
        [data-theme="dark"] [class*="badge"][style*="#f5f5f5"],
        [data-theme="dark"] [class*="badge"][style*="#e0e0e0"],
        [data-theme="dark"] [class*="badge"][style*="#757575"],
        [data-theme="dark"] [class*="badge"][style*="#6c757d"],
        .dark [class*="badge"][style*="#f5f5f5"],
        .dark [class*="badge"][style*="#e0e0e0"],
        .dark [class*="badge"][style*="#757575"],
        .dark [class*="badge"][style*="#6c757d"] {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%) !important; /* Dark gray */
            color: #ffffff !important;
        }
        
        /* Override any color property in badges for dark mode */
        [data-theme="dark"] [class*="badge"][style*="color: #1565c0"],
        [data-theme="dark"] [class*="badge"][style*="color: #0c5460"],
        [data-theme="dark"] [class*="badge"][style*="color: #2e7d32"],
        [data-theme="dark"] [class*="badge"][style*="color: #155724"],
        [data-theme="dark"] [class*="badge"][style*="color: #f57c00"],
        [data-theme="dark"] [class*="badge"][style*="color: #856404"],
        [data-theme="dark"] [class*="badge"][style*="color: #757575"],
        [data-theme="dark"] [class*="badge"][style*="color: #6c757d"],
        .dark [class*="badge"][style*="color: #1565c0"],
        .dark [class*="badge"][style*="color: #0c5460"],
        .dark [class*="badge"][style*="color: #2e7d32"],
        .dark [class*="badge"][style*="color: #155724"],
        .dark [class*="badge"][style*="color: #f57c00"],
        .dark [class*="badge"][style*="color: #856404"],
        .dark [class*="badge"][style*="color: #757575"],
        .dark [class*="badge"][style*="color: #6c757d"] {
            color: #ffffff !important; /* Force white text */
        }
        
        /* Warning badges should be darker orange in dark mode */
        [data-theme="dark"] .material-badge-warning,
        .dark .material-badge-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%) !important; /* Darker orange */
            color: #ffffff !important;
        }
        
        /* Specific badge color overrides for dark mode */
        [data-theme="dark"] .status-active[style*="#e8f5e8"],
        [data-theme="dark"] .status-badge[style*="#e8f5e8"],
        .dark .status-active[style*="#e8f5e8"],
        .dark .status-badge[style*="#e8f5e8"] {
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%) !important; /* Darker green */
            color: #ffffff !important;
            border-color: #22c55e !important;
        }
        
        [data-theme="dark"] .status-inactive[style*="#fff3e0"],
        [data-theme="dark"] .status-badge[style*="#fff3e0"],
        .dark .status-inactive[style*="#fff3e0"],
        .dark .status-badge[style*="#fff3e0"] {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%) !important; /* Darker red */
            color: #ffffff !important;
            border-color: #ef4444 !important;
        }
        
        /* Action Buttons - Dark Mode */
        [data-theme="dark"] .action-btn,
        [data-theme="dark"] .action-btn-info,
        [data-theme="dark"] .action-btn-warning,
        [data-theme="dark"] .action-btn-danger,
        [data-theme="dark"] .action-btn-success,
        .dark .action-btn,
        .dark .action-btn-info,
        .dark .action-btn-warning,
        .dark .action-btn-danger,
        .dark .action-btn-success {
            color: #ffffff !important; /* White text for action buttons */
        }
        
        /* Material Buttons - Dark Mode */
        [data-theme="dark"] .material-btn,
        [data-theme="dark"] .material-btn-primary,
        [data-theme="dark"] .material-btn-success,
        [data-theme="dark"] .material-btn-warning,
        [data-theme="dark"] .material-btn-danger,
        .dark .material-btn,
        .dark .material-btn-primary,
        .dark .material-btn-success,
        .dark .material-btn-warning,
        .dark .material-btn-danger {
            color: #ffffff !important; /* White text for material buttons */
        }
        
        /* Ensure all button text in tables is readable */
        [data-theme="dark"] table .btn,
        [data-theme="dark"] table .material-btn,
        [data-theme="dark"] table .material-badge,
        [data-theme="dark"] table .status-badge,
        [data-theme="dark"] table .badge,
        [data-theme="dark"] table .template-type,
        [data-theme="dark"] table .template-company,
        .dark table .btn,
        .dark table .material-btn,
        .dark table .material-badge,
        .dark table .status-badge,
        .dark table .badge,
        .dark table .template-type,
        .dark table .template-company {
            color: #ffffff !important; /* White text for all buttons/badges in tables */
        }
        
        /* Override any light colored text in badges/buttons in dark mode */
        [data-theme="dark"] [class*="badge"][style*="color: #388e3c"],
        [data-theme="dark"] [class*="badge"][style*="color: #2e7d32"],
        [data-theme="dark"] [class*="badge"][style*="color: #1976d2"],
        [data-theme="dark"] [class*="badge"][style*="color: #f57c00"],
        [data-theme="dark"] [class*="badge"][style*="color: #757575"],
        [data-theme="dark"] [class*="badge"][style*="color: #212121"],
        .dark [class*="badge"][style*="color: #388e3c"],
        .dark [class*="badge"][style*="color: #2e7d32"],
        .dark [class*="badge"][style*="color: #1976d2"],
        .dark [class*="badge"][style*="color: #f57c00"],
        .dark [class*="badge"][style*="color: #757575"],
        .dark [class*="badge"][style*="color: #212121"] {
            color: #ffffff !important; /* Force white text */
        }
        
        /* Override text-muted class in badges for dark mode */
        [data-theme="dark"] .text-muted.badge,
        [data-theme="dark"] .badge.text-muted,
        [data-theme="dark"] .text-muted[class*="badge"],
        .dark .text-muted.badge,
        .dark .badge.text-muted,
        .dark .text-muted[class*="badge"] {
            color: #ffffff !important; /* White text instead of muted */
        }
        
        /* Force ALL badges to have white text in dark mode ONLY - highest priority */
        [data-theme="dark"] [class*="badge"]:not([data-theme="light"]),
        [data-theme="dark"] span[class*="badge"]:not([data-theme="light"]),
        [data-theme="dark"] div[class*="badge"]:not([data-theme="light"]),
        [data-theme="dark"] td [class*="badge"]:not([data-theme="light"]),
        [data-theme="dark"] table [class*="badge"]:not([data-theme="light"]),
        [data-theme="dark"] [class*="badge"][style]:not([data-theme="light"]),
        [data-theme="dark"] span[class*="badge"][style]:not([data-theme="light"]),
        [data-theme="dark"] div[class*="badge"][style]:not([data-theme="light"]),
        .dark [class*="badge"]:not(.light),
        .dark span[class*="badge"]:not(.light),
        .dark div[class*="badge"]:not(.light),
        .dark td [class*="badge"]:not(.light),
        .dark table [class*="badge"]:not(.light),
        .dark [class*="badge"][style]:not(.light),
        .dark span[class*="badge"][style]:not(.light),
        .dark div[class*="badge"][style]:not(.light) {
            color: #ffffff !important; /* Force white text on all badges in dark mode only */
        }
        
        /* Light mode - ensure badges have proper contrast */
        [data-theme="light"] [class*="badge"]:not([data-theme="dark"]),
        [data-theme="light"] .material-badge-info:not([data-theme="dark"]),
        [data-theme="light"] .material-badge-primary:not([data-theme="dark"]),
        body:not([data-theme="dark"]):not(.dark) [class*="badge"] {
            /* Don't override - let original styles work */
        }
        
        /* Force ALL material-badge-info and material-badge-primary to have dark blue background */
        [data-theme="dark"] .material-badge-info,
        [data-theme="dark"] .material-badge-primary,
        [data-theme="dark"] .material-badge-info[style],
        [data-theme="dark"] .material-badge-primary[style],
        .dark .material-badge-info,
        .dark .material-badge-primary,
        .dark .material-badge-info[style],
        .dark .material-badge-primary[style] {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%) !important;
            background-image: none !important;
            color: #ffffff !important;
        }
        
        /* Force ALL material-badge-secondary to have dark gray background */
        [data-theme="dark"] .material-badge-secondary,
        [data-theme="dark"] .material-badge-secondary[style],
        .dark .material-badge-secondary,
        .dark .material-badge-secondary[style] {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%) !important;
            background-image: none !important;
            color: #ffffff !important;
        }
        
        /* Force ALL material-badge-success to have dark green background */
        [data-theme="dark"] .material-badge-success,
        [data-theme="dark"] .material-badge-success[style],
        .dark .material-badge-success,
        .dark .material-badge-success[style] {
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%) !important;
            background-image: none !important;
            color: #ffffff !important;
        }
        
        /* Force ALL material-badge-warning to have dark orange background */
        [data-theme="dark"] .material-badge-warning,
        [data-theme="dark"] .material-badge-warning[style],
        .dark .material-badge-warning,
        .dark .material-badge-warning[style] {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%) !important;
            background-image: none !important;
            color: #ffffff !important;
        }
        
        /* User Company - Dark Mode */
        [data-theme="dark"] .user-company,
        [data-theme="dark"] .user-company[style],
        .dark .user-company,
        .dark .user-company[style] {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%) !important; /* Darker blue */
            background-image: none !important;
            color: #ffffff !important; /* White text */
        }
        
        /* Company Location - Dark Mode */
        [data-theme="dark"] .company-location,
        [data-theme="dark"] .company-location[style],
        .dark .company-location,
        .dark .company-location[style] {
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%) !important; /* Darker green */
            background-image: none !important;
            color: #ffffff !important; /* White text */
        }
        
        /* Detail Pages - Dark Mode Styling */
        [data-theme="dark"] .user-header,
        [data-theme="dark"] .user-header[style],
        [data-theme="dark"] .company-header,
        [data-theme="dark"] .company-header[style],
        [data-theme="dark"] .candidate-header,
        [data-theme="dark"] .candidate-header[style],
        [data-theme="dark"] .role-header,
        [data-theme="dark"] .role-header[style],
        [data-theme="dark"] .vacancy-header,
        [data-theme="dark"] .vacancy-header[style],
        [data-theme="dark"] .notification-header,
        [data-theme="dark"] .notification-header[style],
        [data-theme="dark"] .permission-header,
        [data-theme="dark"] .permission-header[style],
        [data-theme="dark"] .category-header,
        [data-theme="dark"] .category-header[style],
        [data-theme="dark"] .email-template-header,
        [data-theme="dark"] .email-template-header[style],
        [data-theme="dark"] .interview-header,
        [data-theme="dark"] .interview-header[style],
        [data-theme="dark"] .match-header,
        [data-theme="dark"] .match-header[style],
        [data-theme="dark"] .payment-provider-header,
        [data-theme="dark"] .payment-provider-header[style],
        .dark .user-header,
        .dark .user-header[style],
        .dark .company-header,
        .dark .company-header[style],
        .dark .candidate-header,
        .dark .candidate-header[style],
        .dark .role-header,
        .dark .role-header[style],
        .dark .vacancy-header,
        .dark .vacancy-header[style],
        .dark .notification-header,
        .dark .notification-header[style],
        .dark .permission-header,
        .dark .permission-header[style],
        .dark .category-header,
        .dark .category-header[style],
        .dark .email-template-header,
        .dark .email-template-header[style],
        .dark .interview-header,
        .dark .interview-header[style],
        .dark .match-header,
        .dark .match-header[style],
        .dark .payment-provider-header,
        .dark .payment-provider-header[style] {
            background: linear-gradient(135deg, #374151 0%, #4b5563 100%) !important;
            background-image: none !important;
            border-left-color: #60a5fa !important;
        }
        
        [data-theme="dark"] .user-title,
        [data-theme="dark"] .user-title[style],
        [data-theme="dark"] .company-title,
        [data-theme="dark"] .company-title[style],
        [data-theme="dark"] .candidate-title,
        [data-theme="dark"] .candidate-title[style],
        [data-theme="dark"] .role-title,
        [data-theme="dark"] .role-title[style],
        [data-theme="dark"] .vacancy-title,
        [data-theme="dark"] .vacancy-title[style],
        [data-theme="dark"] .notification-title,
        [data-theme="dark"] .notification-title[style],
        [data-theme="dark"] .permission-title,
        [data-theme="dark"] .permission-title[style],
        [data-theme="dark"] .category-title,
        [data-theme="dark"] .category-title[style],
        [data-theme="dark"] .email-template-title,
        [data-theme="dark"] .email-template-title[style],
        [data-theme="dark"] .interview-title,
        [data-theme="dark"] .interview-title[style],
        [data-theme="dark"] .match-title,
        [data-theme="dark"] .match-title[style],
        [data-theme="dark"] .payment-provider-title,
        [data-theme="dark"] .payment-provider-title[style],
        .dark .user-title,
        .dark .user-title[style],
        .dark .company-title,
        .dark .company-title[style],
        .dark .candidate-title,
        .dark .candidate-title[style],
        .dark .role-title,
        .dark .role-title[style],
        .dark .vacancy-title,
        .dark .vacancy-title[style],
        .dark .notification-title,
        .dark .notification-title[style],
        .dark .permission-title,
        .dark .permission-title[style],
        .dark .category-title,
        .dark .category-title[style],
        .dark .email-template-title,
        .dark .email-template-title[style],
        .dark .interview-title,
        .dark .interview-title[style],
        .dark .match-title,
        .dark .match-title[style],
        .dark .payment-provider-title,
        .dark .payment-provider-title[style] {
            color: #ffffff !important; /* Pure white for maximum contrast */
        }
        
        /* Light mode - ensure titles are dark and readable */
        body:not([data-theme="dark"]):not(.dark) .user-title,
        body:not([data-theme="dark"]):not(.dark) .company-title,
        body:not([data-theme="dark"]):not(.dark) .candidate-title,
        body:not([data-theme="dark"]):not(.dark) .role-title,
        body:not([data-theme="dark"]):not(.dark) .vacancy-title,
        body:not([data-theme="dark"]):not(.dark) .notification-title,
        body:not([data-theme="dark"]):not(.dark) .permission-title,
        body:not([data-theme="dark"]):not(.dark) .category-title,
        body:not([data-theme="dark"]):not(.dark) .email-template-title,
        body:not([data-theme="dark"]):not(.dark) .interview-title,
        body:not([data-theme="dark"]):not(.dark) .match-title,
        body:not([data-theme="dark"]):not(.dark) .payment-provider-title {
            color: #212121 !important; /* Force dark text in light mode */
        }
        
        [data-theme="dark"] .meta-item,
        [data-theme="dark"] .meta-item span,
        [data-theme="dark"] .meta-item[style],
        [data-theme="dark"] .role-meta,
        [data-theme="dark"] .role-meta span,
        [data-theme="dark"] .role-meta[style],
        [data-theme="dark"] .vacancy-meta,
        [data-theme="dark"] .vacancy-meta span,
        [data-theme="dark"] .vacancy-meta[style],
        [data-theme="dark"] .user-meta,
        [data-theme="dark"] .user-meta span,
        [data-theme="dark"] .user-meta[style],
        [data-theme="dark"] .company-meta,
        [data-theme="dark"] .company-meta span,
        [data-theme="dark"] .company-meta[style],
        [data-theme="dark"] .candidate-meta,
        [data-theme="dark"] .candidate-meta span,
        [data-theme="dark"] .candidate-meta[style],
        .dark .meta-item,
        .dark .meta-item span,
        .dark .meta-item[style],
        .dark .role-meta,
        .dark .role-meta span,
        .dark .role-meta[style],
        .dark .vacancy-meta,
        .dark .vacancy-meta span,
        .dark .vacancy-meta[style],
        .dark .user-meta,
        .dark .user-meta span,
        .dark .user-meta[style],
        .dark .company-meta,
        .dark .company-meta span,
        .dark .company-meta[style],
        .dark .candidate-meta,
        .dark .candidate-meta span,
        .dark .candidate-meta[style] {
            color: #ffffff !important; /* Pure white for maximum contrast and readability */
        }
        
        [data-theme="dark"] .meta-item i,
        [data-theme="dark"] .role-meta i,
        [data-theme="dark"] .vacancy-meta i,
        [data-theme="dark"] .user-meta i,
        [data-theme="dark"] .company-meta i,
        [data-theme="dark"] .candidate-meta i,
        .dark .meta-item i,
        .dark .role-meta i,
        .dark .vacancy-meta i,
        .dark .user-meta i,
        .dark .company-meta i,
        .dark .candidate-meta i {
            color: #93c5fd !important; /* text-blue-300 - lichtere blauw voor betere zichtbaarheid */
        }
        
        /* Light mode - ensure meta items are dark and readable */
        body:not([data-theme="dark"]):not(.dark) .meta-item,
        body:not([data-theme="dark"]):not(.dark) .meta-item span,
        body:not([data-theme="dark"]):not(.dark) .role-meta,
        body:not([data-theme="dark"]):not(.dark) .role-meta span,
        body:not([data-theme="dark"]):not(.dark) .vacancy-meta,
        body:not([data-theme="dark"]):not(.dark) .vacancy-meta span {
            color: #424242 !important; /* Force darker text in light mode */
        }
        
        [data-theme="dark"] .info-section,
        .dark .info-section {
            background: #1f2937 !important; /* bg-gray-800 */
            border-color: #374151 !important; /* border-gray-700 */
        }
        
        [data-theme="dark"] .section-title,
        .dark .section-title {
            color: #f9fafb !important; /* text-gray-50 */
            border-bottom-color: #60a5fa !important; /* border-blue-400 */
        }
        
        [data-theme="dark"] .section-title i,
        .dark .section-title i {
            color: #60a5fa !important; /* text-blue-400 */
        }
        
        [data-theme="dark"] .info-table td:first-child,
        .dark .info-table td:first-child {
            color: #e5e7eb !important; /* text-gray-200 */
        }
        
        [data-theme="dark"] .info-table td:last-child,
        .dark .info-table td:last-child {
            color: #d1d5db !important; /* text-gray-300 */
        }
        
        [data-theme="dark"] .info-table tr,
        .dark .info-table tr {
            border-bottom-color: #374151 !important; /* border-gray-700 */
        }
        
        [data-theme="dark"] .material-text-muted,
        .dark .material-text-muted {
            color: #9ca3af !important; /* text-gray-400 */
        }
        
        [data-theme="dark"] .material-link,
        .dark .material-link {
            color: #60a5fa !important; /* text-blue-400 */
        }
        
        [data-theme="dark"] .material-link:hover,
        .dark .material-link:hover {
            color: #93c5fd !important; /* text-blue-300 */
        }
        
        /* Material Buttons - Dark Mode */
        [data-theme="dark"] .material-btn-secondary,
        [data-theme="dark"] .material-btn-secondary[style],
        .dark .material-btn-secondary,
        .dark .material-btn-secondary[style] {
            background: #374151 !important; /* bg-gray-700 */
            background-color: #374151 !important;
            background-image: none !important;
            color: #ffffff !important; /* White text */
            border-color: #4b5563 !important;
        }
        
        [data-theme="dark"] .material-btn-secondary:hover,
        [data-theme="dark"] .material-btn-secondary:hover[style],
        .dark .material-btn-secondary:hover,
        .dark .material-btn-secondary:hover[style] {
            background: #4b5563 !important; /* bg-gray-600 */
            background-color: #4b5563 !important;
            background-image: none !important;
            color: #ffffff !important;
            border-color: #6b7280 !important;
        }
        
        [data-theme="dark"] .material-btn-secondary i,
        .dark .material-btn-secondary i {
            color: #ffffff !important;
        }

        /* Material Table Card - Dark Mode */
        [data-theme="dark"] .material-table-card,
        [data-theme="dark"] .material-table-card .card-body,
        .dark .material-table-card,
        .dark .material-table-card .card-body {
            background-color: #1f2937 !important; /* bg-gray-800 */
            color: #f9fafb !important; /* text-gray-50 */
        }

        /* Stat Cards - Dark Mode */
        [data-theme="dark"] .stat-card,
        [data-theme="dark"] .material-stat-card,
        [data-theme="dark"] .stat-card[style*="background: white"],
        [data-theme="dark"] .material-stat-card[style*="background: white"],
        .dark .stat-card,
        .dark .material-stat-card,
        .dark .stat-card[style*="background: white"],
        .dark .material-stat-card[style*="background: white"] {
            background-color: #1f2937 !important; /* bg-gray-800 */
            background: #1f2937 !important; /* bg-gray-800 */
            color: #f9fafb !important; /* text-gray-50 */
        }

        [data-theme="dark"] .stat-number,
        .dark .stat-number {
            color: #f9fafb !important; /* text-gray-50 */
        }

        [data-theme="dark"] .stat-label,
        .dark .stat-label {
            color: #d1d5db !important; /* text-gray-300 */
        }

        /* Override gradient text for stat-number in dark mode */
        [data-theme="dark"] .stat-number[style*="background-clip"],
        [data-theme="dark"] .stat-number[style*="-webkit-background-clip"],
        .dark .stat-number[style*="background-clip"],
        .dark .stat-number[style*="-webkit-background-clip"] {
            -webkit-text-fill-color: #f9fafb !important;
            color: #f9fafb !important;
            background: transparent !important;
        }

        [data-theme="dark"] .stat-icon,
        .dark .stat-icon {
            opacity: 0.6 !important;
            color: #d1d5db !important; /* text-gray-300 */
        }

        /* Override inline styles in individual pages */
        [data-theme="dark"] .material-table tbody tr[style*="background-color: white"],
        [data-theme="dark"] .material-table tbody tr[style*="background: white"],
        [data-theme="dark"] table tbody tr[style*="background-color: white"],
        [data-theme="dark"] table tbody tr[style*="background: white"],
        .dark .material-table tbody tr[style*="background-color: white"],
        .dark .material-table tbody tr[style*="background: white"],
        .dark table tbody tr[style*="background-color: white"],
        .dark table tbody tr[style*="background: white"] {
            background-color: #1f2937 !important; /* bg-gray-800 */
        }

        /* Override material-table-card backgrounds */
        [data-theme="dark"] .material-table-card[style*="background: white"],
        [data-theme="dark"] .material-table-card[style*="background-color: white"],
        .dark .material-table-card[style*="background: white"],
        .dark .material-table-card[style*="background-color: white"] {
            background: #1f2937 !important; /* bg-gray-800 */
            background-color: #1f2937 !important; /* bg-gray-800 */
        }

        /* Bootstrap table overrides */
        [data-theme="dark"] .table-striped tbody tr:nth-of-type(odd),
        .dark .table-striped tbody tr:nth-of-type(odd) {
            background-color: #374151 !important; /* bg-gray-700 */
        }

        [data-theme="dark"] .table-striped tbody tr:nth-of-type(even),
        .dark .table-striped tbody tr:nth-of-type(even) {
            background-color: #1f2937 !important; /* bg-gray-800 */
        }

        /* Pagination - Dark Mode */
        [data-theme="dark"] .pagination .page-link,
        .dark .pagination .page-link {
            background-color: #374151 !important; /* bg-gray-700 */
            color: #f9fafb !important; /* text-gray-50 */
            border-color: #4b5563 !important; /* border-gray-600 */
        }

        [data-theme="dark"] .pagination .page-link:hover,
        .dark .pagination .page-link:hover {
            background-color: #4b5563 !important; /* bg-gray-600 */
            color: #f9fafb !important; /* text-gray-50 */
        }

        [data-theme="dark"] .pagination .page-item.active .page-link,
        .dark .pagination .page-item.active .page-link {
            background-color: #60a5fa !important; /* bg-blue-400 */
            border-color: #60a5fa !important; /* border-blue-400 */
            color: #ffffff !important;
        }

        /* Buttons - Dark Mode */
        [data-theme="dark"] .btn-secondary,
        [data-theme="dark"] .btn-outline-secondary,
        .dark .btn-secondary,
        .dark .btn-outline-secondary {
            background-color: #374151 !important; /* bg-gray-700 */
            color: #f9fafb !important; /* text-gray-50 */
            border-color: #4b5563 !important; /* border-gray-600 */
        }

        [data-theme="dark"] .btn-secondary:hover,
        [data-theme="dark"] .btn-outline-secondary:hover,
        .dark .btn-secondary:hover,
        .dark .btn-outline-secondary:hover {
            background-color: #4b5563 !important; /* bg-gray-600 */
            border-color: #6b7280 !important; /* border-gray-500 */
        }

        /* Filters Section - Dark Mode */
        [data-theme="dark"] .filters-section,
        [data-theme="dark"] .filters-section[style*="background: white"],
        [data-theme="dark"] .filters-section[style*="background-color: white"],
        [data-theme="dark"] .filters-section[style*="background: var(--light-bg)"],
        .dark .filters-section,
        .dark .filters-section[style*="background: white"],
        .dark .filters-section[style*="background-color: white"],
        .dark .filters-section[style*="background: var(--light-bg)"] {
            background-color: #1f2937 !important; /* bg-gray-800 */
            background: #1f2937 !important; /* bg-gray-800 */
            border-color: #374151 !important; /* border-gray-700 */
        }

        [data-theme="dark"] .filter-label,
        .dark .filter-label {
            color: #d1d5db !important; /* text-gray-300 */
        }

        /* Filter Select - Dark Mode */
        [data-theme="dark"] .filter-select,
        [data-theme="dark"] .filter-select[style*="background-color: white"],
        [data-theme="dark"] .filter-select[style*="background: white"],
        .dark .filter-select,
        .dark .filter-select[style*="background-color: white"],
        .dark .filter-select[style*="background: white"] {
            background-color: #374151 !important; /* bg-gray-700 */
            background: #374151 !important; /* bg-gray-700 */
            color: #f9fafb !important; /* text-gray-50 */
            border-color: #4b5563 !important; /* border-gray-600 */
        }

        [data-theme="dark"] .filter-select:focus,
        .dark .filter-select:focus {
            background-color: #374151 !important; /* bg-gray-700 */
            border-color: #60a5fa !important; /* border-blue-400 */
            color: #f9fafb !important; /* text-gray-50 */
        }

        [data-theme="dark"] .filter-select option,
        .dark .filter-select option {
            background-color: #374151 !important; /* bg-gray-700 */
            color: #f9fafb !important; /* text-gray-50 */
        }

        [data-theme="dark"] .filter-select option:checked,
        .dark .filter-select option:checked {
            background-color: #60a5fa !important; /* bg-blue-400 */
            color: #ffffff !important;
        }

        /* Results Info - Dark Mode */
        [data-theme="dark"] .results-info-wrapper,
        [data-theme="dark"] .pagination-wrapper,
        .dark .results-info-wrapper,
        .dark .pagination-wrapper {
            background-color: #1f2937 !important; /* bg-gray-800 */
            background: #1f2937 !important; /* bg-gray-800 */
            border-color: #374151 !important; /* border-gray-700 */
        }

        [data-theme="dark"] .results-text,
        .dark .results-text {
            color: #d1d5db !important; /* text-gray-300 */
        }

        /* Page Link - Dark Mode */
        [data-theme="dark"] .page-link,
        [data-theme="dark"] .page-link[style*="background: white"],
        .dark .page-link,
        .dark .page-link[style*="background: white"] {
            background-color: #374151 !important; /* bg-gray-700 */
            background: #374151 !important; /* bg-gray-700 */
            color: #f9fafb !important; /* text-gray-50 */
            border-color: #4b5563 !important; /* border-gray-600 */
        }

        [data-theme="dark"] .page-link:hover,
        .dark .page-link:hover {
            background-color: #4b5563 !important; /* bg-gray-600 */
            color: #f9fafb !important; /* text-gray-50 */
        }

        [data-theme="dark"] .settings-header {
            border-bottom-color: var(--border-color);
        }

        [data-theme="dark"] .theme-toggle {
            border-color: var(--border-color);
            background-color: var(--card-background);
        }

        [data-theme="dark"] .theme-toggle:hover {
            background-color: var(--surface-color);
        }

        /* Dark mode user button - already defined above with correct colors */
        [data-theme="dark"] .user-button {
            border-color: #374151 !important; /* border-gray-700 */
            background-color: transparent !important; /* Match header background */
            color: #f9fafb !important; /* text-gray-50 */
        }

        [data-theme="dark"] .user-button:hover {
            background-color: #374151 !important; /* bg-gray-700 */
        }

        /* Dark mode user button span - already defined above */
        [data-theme="dark"] .user-button span {
            color: #f9fafb !important; /* text-gray-50 */
        }

        [data-theme="dark"] .user-button i {
            color: #f9fafb !important; /* text-gray-50 */
        }

        /* Dark mode dropdown menu */
        [data-theme="dark"] .dropdown-menu {
            background-color: #1f2937;
            border-color: #4b5563;
            color: #f9fafb;
        }

        [data-theme="dark"] .dropdown-item {
            color: #f9fafb;
        }

        [data-theme="dark"] .dropdown-item:hover {
            background-color: #374151;
        }

        [data-theme="dark"] .dropdown-divider {
            background-color: #4b5563;
        }

        /* Dark mode notification bell border consistency */
        [data-theme="dark"] .notification-bell {
            border-color: #4b5563;
            background: #1f2937;
        }

        [data-theme="dark"] .notification-bell:hover {
            background-color: #374151;
        }

        [data-theme="dark"] .notification-bell i {
            color: #9ca3af;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                width: 280px;
            }

            .admin-sidebar.open {
                transform: translateX(0);
            }

            .admin-sidebar.collapsed {
                transform: translateX(-100%);
                width: 280px;
            }

            .admin-main {
                margin-left: 0;
                width: 100%;
            }

            .admin-main.expanded {
                margin-left: 0;
                width: 100%;
            }

            .admin-content {
                padding: var(--spacing-md);
                min-height: calc(100vh - 70px);
            }

            .page-title {
                font-size: 1.25rem;
            }
        }

        /* Overlay for mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .sidebar-overlay.show {
            display: block;
        }

        /* Utility Classes */
        .d-flex { display: flex; }
        .justify-content-between { justify-content: space-between; }
        .align-items-center { align-items: center; }
        .text-center { text-align: center; }
        .mb-0 { margin-bottom: 0; }
        .mb-1 { margin-bottom: var(--spacing-sm); }
        .mb-2 { margin-bottom: var(--spacing-md); }
        .mb-3 { margin-bottom: var(--spacing-lg); }
        .mt-0 { margin-top: 0; }
        .mt-1 { margin-top: var(--spacing-sm); }
        .mt-2 { margin-top: var(--spacing-md); }
        .mt-3 { margin-top: var(--spacing-lg); }
        .p-0 { padding: 0; }
        .p-1 { padding: var(--spacing-sm); }
        .p-2 { padding: var(--spacing-md); }
        .p-3 { padding: var(--spacing-lg); }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar Overlay for Mobile -->
        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        
        <!-- Sidebar -->
        <div class="admin-sidebar" id="sidebar">
            <div class="sidebar-logo">
                <div class="logo-icon">
                    <img src="{{ asset('images/nexa-skillmatching-logo.png') }}" alt="Nexa Skillmatching" class="responsive-logo">
                </div>
            </div>
            
            <!-- Tenant Selector -->
            @if(auth()->user()->hasRole('super-admin'))
                <div class="tenant-selector">
                    <label>
                        <i class="fas fa-building"></i>
                        <span>Selecteer Tenant</span>
                    </label>
                    <select id="tenant-selector" onchange="changeTenant(this.value)">
                        <option value="">Alle Tenants</option>
                        @foreach(\App\Models\Company::all() as $company)
                            <option value="{{ $company->id }}" {{ session('selected_tenant') == $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-companies'))
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.companies.*') ? 'active' : '' }}" href="{{ route('admin.companies.index') }}">
                        <i class="fas fa-building"></i>
                        <span>Bedrijven</span>
                    </a>
                </div>
                @endif
                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-users'))
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                        <i class="fas fa-users"></i>
                        <span>Gebruikers</span>
                    </a>
                </div>
                @endif
                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-categories'))
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}">
                        <i class="fas fa-tags"></i>
                        <span>Categorieën</span>
                    </a>
                </div>
                @endif
                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-vacancies'))
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.vacancies.*') ? 'active' : '' }}" href="{{ route('admin.vacancies.index') }}">
                        <i class="fas fa-briefcase"></i>
                        <span>Vacatures</span>
                    </a>
                </div>
                @endif
                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-matches'))
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.matches.*') ? 'active' : '' }}" href="{{ route('admin.matches.index') }}">
                        <i class="fas fa-handshake"></i>
                        <span>Matches</span>
                    </a>
                </div>
                @endif
                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-interviews'))
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.interviews.*') ? 'active' : '' }}" href="{{ route('admin.interviews.index') }}">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Interviews</span>
                    </a>
                </div>
                @endif
                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-agenda'))
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.agenda.*') ? 'active' : '' }}" href="{{ route('admin.agenda.index') }}">
                        <i class="fas fa-calendar"></i>
                        <span>Agenda</span>
                    </a>
                </div>
                @endif
                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-notifications'))
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}" href="{{ route('admin.notifications.index') }}">
                        <i class="fas fa-bell"></i>
                        <span>Notificaties</span>
                    </a>
                </div>
                @endif
                @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('view-email-templates'))
                <div class="nav-item">
                    <a class="nav-link {{ request()->routeIs('admin.email-templates.*') ? 'active' : '' }}" href="{{ route('admin.email-templates.index') }}">
                        <i class="fas fa-envelope"></i>
                        <span>E-mail Templates</span>
                    </a>
                </div>
                @endif
                
                <!-- Roles & Permissions (Super Admin only) -->
                @if(auth()->user()->hasRole('super-admin'))
                    <div class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}">
                            <i class="fas fa-user-shield"></i>
                            <span>Rollen</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}" href="{{ route('admin.permissions.index') }}">
                            <i class="fas fa-key"></i>
                            <span>Rechten</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.payment-providers.*') ? 'active' : '' }}" href="{{ route('admin.payment-providers.index') }}">
                            <i class="fas fa-credit-card"></i>
                            <span>Betalingsproviders</span>
                        </a>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" href="{{ route('admin.settings.index') }}">
                            <i class="fas fa-cog"></i>
                            <span>Instellingen</span>
                        </a>
                    </div>
                @endif
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main" id="mainContent">
            <!-- Header -->
            <div class="admin-header">
                <div class="header-content">
                    <div class="header-left">
                        <button class="menu-toggle" onclick="toggleSidebar()">
                            <i class="fas fa-bars"></i>
                        </button>
                        <h1 class="page-title">@yield('title', 'Dashboard')</h1>
                    </div>
                    <div class="user-menu">
                        <!-- Theme Toggle -->
                        <button class="theme-toggle-button" onclick="toggleTheme()" aria-label="Toggle theme">
                            <i class="fas fa-moon theme-icon-light"></i>
                            <i class="fas fa-sun theme-icon-dark"></i>
                        </button>
                        
                        <!-- Notification Bell -->
                        <div class="notification-bell-container">
                            <button class="notification-bell" onclick="toggleNotificationDropdown()">
                                @php
                                    $user = auth()->user();
                                    $unreadCount = \App\Models\Notification::where('user_id', $user->id)
                                        ->where('company_id', $user->company_id)
                                        ->whereNull('read_at')
                                        ->count();
                                    $highestPriority = \App\Models\Notification::where('user_id', $user->id)
                                        ->where('company_id', $user->company_id)
                                        ->whereNull('read_at')
                                        ->orderByRaw("CASE 
                                            WHEN priority = 'urgent' THEN 1 
                                            WHEN priority = 'high' THEN 2 
                                            ELSE 3 
                                        END")
                                        ->value('priority') ?? 'normal';
                                @endphp
                                <i class="fas fa-bell notification-icon-{{ $highestPriority }}"></i>
                                @if($unreadCount > 0)
                                    <span class="notification-badge">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                                @endif
                            </button>
                            <div class="notification-dropdown" id="notificationDropdown" style="display: none;">
                                <div class="notification-header">
                                    <h4>Notificaties</h4>
                                    @if($unreadCount > 0)
                                        <button class="mark-all-read" onclick="markAllNotificationsAsRead()">
                                            <i class="fas fa-check-double"></i> Alles als gelezen markeren
                                        </button>
                                    @endif
                                </div>
                                <div class="notification-list">
                                    @php
                                        $notifications = \App\Models\Notification::where('user_id', $user->id)
                                            ->where('company_id', $user->company_id)
                                            ->orderBy('created_at', 'desc')
                                            ->limit(10)
                                            ->get();
                                    @endphp
                                    @forelse($notifications as $notification)
                                        <div class="notification-item {{ $notification->read_at ? 'read' : 'unread' }}" 
                                             data-notification-id="{{ $notification->id }}"
                                             onclick="markNotificationAsRead({{ $notification->id }})">
                                            <div class="notification-icon priority-{{ $notification->priority ?? 'normal' }}">
                                                @switch($notification->priority ?? 'normal')
                                                    @case('high')
                                                        <i class="fas fa-exclamation-triangle"></i>
                                                        @break
                                                    @case('urgent')
                                                        <i class="fas fa-exclamation-circle"></i>
                                                        @break
                                                    @default
                                                        <i class="fas fa-info-circle"></i>
                                                @endswitch
                                            </div>
                                            <div class="notification-content">
                                                <div class="notification-title">{{ $notification->title }}</div>
                                                <div class="notification-message">{{ Str::limit($notification->message, 60) }}</div>
                                                <div class="notification-time">{{ $notification->created_at->diffForHumans() }}</div>
                                            </div>
                                            @if(!$notification->read_at)
                                                <div class="notification-status">
                                                    <i class="fas fa-circle"></i>
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="notification-empty">
                                            <i class="fas fa-bell-slash"></i>
                                            <p>Geen notificaties</p>
                                        </div>
                                    @endforelse
                                </div>
                                @if($notifications->count() > 0)
                                    <div class="notification-footer">
                                        <a href="{{ route('admin.notifications.index') }}" class="view-all-notifications">
                                            <i class="fas fa-list"></i> Alle notificaties bekijken
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <button class="user-button" onclick="toggleUserMenu()">
                            <div class="user-avatar">
                                {{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}{{ strtoupper(substr(auth()->user()->last_name, 0, 1)) }}
                            </div>
                            <span>{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu" id="userDropdown" style="display: none;">
                            <a class="dropdown-item" href="#" onclick="toggleThemeSettings()">
                                <i class="fas fa-cog me-2"></i> Instellingen
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-sign-out-alt me-2"></i> Uitloggen
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Page Content -->
            <div class="admin-content">
                @if(session('error'))
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        {{ session('error') }}
                    </div>
                @endif



                @yield('content')
            </div>
        </div>

        <!-- Settings Modal -->
        <div class="settings-modal" id="settingsModal">
            <div class="settings-content">
                <div class="settings-header">
                    <h3>
                        <i class="fas fa-cog me-2"></i> Instellingen
                    </h3>
                    <button class="settings-close" onclick="closeSettingsModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="settings-body">
                    <!-- Theme settings moved to header toggle button -->
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Logout Form -->
    <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
        @csrf
    </form>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Tenant switching
        function changeTenant(tenantId) {
            fetch('{{ route("admin.tenant.switch") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ tenant_id: tenantId })
            }).then(() => {
                window.location.reload();
            });
        }

        // Sidebar toggle
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('open');
                overlay.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        }

        // User menu toggle
        function toggleUserMenu() {
            const dropdown = document.getElementById('userDropdown');
            const notificationDropdown = document.getElementById('notificationDropdown');
            const userButton = document.querySelector('.user-button');
            
            // Close notification dropdown if open
            notificationDropdown.style.display = 'none';
            
            // Toggle user dropdown
            const isVisible = dropdown.style.display === 'block';
            dropdown.style.display = isVisible ? 'none' : 'block';
            
            if (!isVisible) {
                // Position dropdown to stay within viewport
                positionUserDropdown(dropdown, userButton);
            }
        }

        function positionUserDropdown(dropdown, userButton) {
            const rect = userButton.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const dropdownWidth = 200;
            const dropdownHeight = 120; // Approximate height of the dropdown
            
            // Reset any previous positioning
            dropdown.style.left = '';
            dropdown.style.right = '';
            dropdown.style.top = '';
            dropdown.style.bottom = '';
            dropdown.style.transform = '';
            
            // Calculate horizontal position
            if (rect.right - dropdownWidth < 20) {
                // Dropdown would overflow left, position from left edge
                dropdown.style.left = '0';
                dropdown.style.right = 'auto';
            } else {
                // Position from right edge
                dropdown.style.right = '0';
                dropdown.style.left = 'auto';
            }
            
            // Calculate vertical position
            const spaceBelow = viewportHeight - rect.bottom - 20;
            const spaceAbove = rect.top - 20;
            
            if (spaceBelow >= dropdownHeight || spaceBelow > spaceAbove) {
                // Position below the button
                dropdown.style.top = '100%';
                dropdown.style.bottom = 'auto';
                dropdown.style.transform = 'translateY(8px)';
            } else {
                // Position above the button
                dropdown.style.bottom = '100%';
                dropdown.style.top = 'auto';
                dropdown.style.transform = 'translateY(-8px)';
            }
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            const userDropdown = document.getElementById('userDropdown');
            const notificationContainer = document.querySelector('.notification-bell-container');
            const notificationDropdown = document.getElementById('notificationDropdown');
            
            // Close user dropdown if clicking outside
            if (!userMenu.contains(event.target)) {
                userDropdown.style.display = 'none';
            }
            
            // Close notification dropdown if clicking outside
            if (!notificationContainer.contains(event.target)) {
                notificationDropdown.style.display = 'none';
            }
        });

        // Handle window resize
        window.addEventListener('resize', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const overlay = document.getElementById('sidebarOverlay');
            
            if (window.innerWidth > 768) {
                sidebar.classList.remove('open');
                overlay.classList.remove('show');
                // Reset classes for desktop
                if (sidebar.classList.contains('collapsed')) {
                    mainContent.classList.add('expanded');
                } else {
                    mainContent.classList.remove('expanded');
                }
            } else {
                // Reset for mobile
                mainContent.classList.remove('expanded');
            }
        });

        // Theme Management
        function initializeTheme() {
            const savedTheme = localStorage.getItem('theme') || localStorage.getItem('admin-theme') || 'light';
            setTheme(savedTheme, false);
        }
        
        function toggleTheme() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 
                                 (document.documentElement.classList.contains('dark') ? 'dark' : 'light');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            setTheme(newTheme, true);
        }

        function setTheme(theme, updateLocalStorage = true) {
            // Set data-theme attribute
            document.documentElement.setAttribute('data-theme', theme);
            
            // Set dark class for Tailwind CSS
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            
            // Save to localStorage
            if (updateLocalStorage) {
                localStorage.setItem('theme', theme);
                localStorage.setItem('admin-theme', theme); // Keep for backwards compatibility
            }
            
            // Update badges after theme change
            setTimeout(updateBadgesForDarkMode, 100);
            
            // Update theme toggles in settings modal (if they exist)
            const lightTheme = document.getElementById('lightTheme');
            const darkTheme = document.getElementById('darkTheme');
            
            if (lightTheme && darkTheme) {
                if (theme === 'light') {
                    lightTheme.classList.add('active');
                    darkTheme.classList.remove('active');
                } else {
                    darkTheme.classList.add('active');
                    lightTheme.classList.remove('active');
                }
            }
        }

        function toggleThemeSettings() {
            const modal = document.getElementById('settingsModal');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeSettingsModal() {
            const modal = document.getElementById('settingsModal');
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }

        // Close modal when clicking outside
        document.getElementById('settingsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSettingsModal();
            }
        });

        // Notification Functions
        function toggleNotificationDropdown() {
            const dropdown = document.getElementById('notificationDropdown');
            const userDropdown = document.getElementById('userDropdown');
            const bellButton = document.querySelector('.notification-bell');
            
            // Close user dropdown if open
            userDropdown.style.display = 'none';
            
            // Toggle notification dropdown
            const isVisible = dropdown.style.display === 'block';
            dropdown.style.display = isVisible ? 'none' : 'block';
            
            if (!isVisible) {
                // Position dropdown to stay within viewport
                positionNotificationDropdown(dropdown, bellButton);
            }
        }

        function positionNotificationDropdown(dropdown, bellButton) {
            const rect = bellButton.getBoundingClientRect();
            const viewportWidth = window.innerWidth;
            const viewportHeight = window.innerHeight;
            const dropdownWidth = 400;
            const dropdownHeight = Math.min(500, viewportHeight - 100);
            
            // Reset any previous positioning
            dropdown.style.left = '';
            dropdown.style.right = '';
            dropdown.style.top = '';
            dropdown.style.bottom = '';
            dropdown.style.maxHeight = dropdownHeight + 'px';
            
            // Check if sidebar is collapsed
            const sidebar = document.getElementById('sidebar');
            const isSidebarCollapsed = sidebar && sidebar.classList.contains('collapsed');
            
            // Calculate horizontal position
            if (rect.left + dropdownWidth > viewportWidth - 20) {
                // Dropdown would overflow right, position from right edge
                dropdown.style.right = '0';
                dropdown.style.left = 'auto';
            } else {
                // Position from left edge
                dropdown.style.left = '0';
                dropdown.style.right = 'auto';
            }
            
            // Calculate vertical position
            const spaceBelow = viewportHeight - rect.bottom - 20;
            const spaceAbove = rect.top - 20;
            
            if (spaceBelow >= dropdownHeight || spaceBelow > spaceAbove) {
                // Position below the bell
                dropdown.style.top = '100%';
                dropdown.style.bottom = 'auto';
            } else {
                // Position above the bell
                dropdown.style.bottom = '100%';
                dropdown.style.top = 'auto';
            }
            
            // Ensure dropdown doesn't go off-screen on mobile
            if (viewportWidth <= 768) {
                const dropdownRect = dropdown.getBoundingClientRect();
                
                // Adjust horizontal position if needed
                if (dropdownRect.right > viewportWidth - 10) {
                    dropdown.style.right = '10px';
                    dropdown.style.left = 'auto';
                }
                
                if (dropdownRect.left < 10) {
                    dropdown.style.left = '10px';
                    dropdown.style.right = 'auto';
                }
            }
        }

        function markNotificationAsRead(notificationId) {
            fetch(`/admin/notifications/${notificationId}/mark-read`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the notification item
                    const notificationItem = document.querySelector(`[data-notification-id="${notificationId}"]`);
                    if (notificationItem) {
                        notificationItem.classList.remove('unread');
                        notificationItem.classList.add('read');
                        
                        // Remove the status indicator
                        const statusIndicator = notificationItem.querySelector('.notification-status');
                        if (statusIndicator) {
                            statusIndicator.remove();
                        }
                        
                        // Update badge count and bell color
                        updateNotificationBadge();
                    }
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });
        }

        function markAllNotificationsAsRead() {
            fetch('/admin/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update all notification items
                    const unreadItems = document.querySelectorAll('.notification-item.unread');
                    unreadItems.forEach(item => {
                        item.classList.remove('unread');
                        item.classList.add('read');
                        
                        // Remove status indicators
                        const statusIndicator = item.querySelector('.notification-status');
                        if (statusIndicator) {
                            statusIndicator.remove();
                        }
                    });
                    
                    // Update badge count and bell color
                    updateNotificationBadge();
                    
                    // Hide the mark all read button
                    const markAllButton = document.querySelector('.mark-all-read');
                    if (markAllButton) {
                        markAllButton.style.display = 'none';
                    }
                }
            })
            .catch(error => {
                console.error('Error marking all notifications as read:', error);
            });
        }

        function updateNotificationBadge() {
            const badge = document.querySelector('.notification-badge');
            const unreadItems = document.querySelectorAll('.notification-item.unread');
            const bellIcon = document.querySelector('.notification-bell i');
            
            if (unreadItems.length === 0) {
                if (badge) {
                    badge.remove();
                }
                // Reset bell color to normal
                if (bellIcon) {
                    bellIcon.className = 'fas fa-bell notification-icon-normal';
                }
            } else {
                if (badge) {
                    badge.textContent = unreadItems.length > 99 ? '99+' : unreadItems.length;
                } else {
                    // Create new badge
                    const newBadge = document.createElement('span');
                    newBadge.className = 'notification-badge';
                    newBadge.textContent = unreadItems.length > 99 ? '99+' : unreadItems.length;
                    document.querySelector('.notification-bell').appendChild(newBadge);
                }
                
                // Update bell color based on highest priority
                updateBellColor();
            }
        }

        function updateBellColor() {
            const unreadItems = document.querySelectorAll('.notification-item.unread');
            const bellIcon = document.querySelector('.notification-bell i');
            
            if (!bellIcon) return;
            
            let highestPriority = 'normal';
            
            unreadItems.forEach(item => {
                const iconElement = item.querySelector('.notification-icon');
                if (iconElement) {
                    if (iconElement.classList.contains('priority-urgent')) {
                        highestPriority = 'urgent';
                    } else if (iconElement.classList.contains('priority-high') && highestPriority !== 'urgent') {
                        highestPriority = 'high';
                    }
                }
            });
            
            // Update bell icon class
            bellIcon.className = `fas fa-bell notification-icon-${highestPriority}`;
        }



        // Reposition dropdowns on window resize
        window.addEventListener('resize', function() {
            const notificationDropdown = document.getElementById('notificationDropdown');
            const bellButton = document.querySelector('.notification-bell');
            const userDropdown = document.getElementById('userDropdown');
            const userButton = document.querySelector('.user-button');
            
            if (notificationDropdown.style.display === 'block' && bellButton) {
                positionNotificationDropdown(notificationDropdown, bellButton);
            }
            
            if (userDropdown.style.display === 'block' && userButton) {
                positionUserDropdown(userDropdown, userButton);
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSettingsModal();
            }
        });

        // Initialize theme on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeTheme();
            updateBadgesForDarkMode();
        });
        
        // Function to update badges for dark/light mode
        function updateBadgesForDarkMode() {
            const isDarkMode = document.documentElement.getAttribute('data-theme') === 'dark' || 
                             document.documentElement.classList.contains('dark');
            
            // Find all badges
            const badges = document.querySelectorAll('[class*="badge"]');
            badges.forEach(function(badge) {
                if (isDarkMode) {
                    // Force white text in dark mode
                    badge.style.setProperty('color', '#ffffff', 'important');
                    
                    // Update backgrounds based on badge type
                    if (badge.classList.contains('material-badge-info') || badge.classList.contains('material-badge-primary')) {
                        badge.style.setProperty('background', 'linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%)', 'important');
                        badge.style.setProperty('background-image', 'none', 'important');
                    } else if (badge.classList.contains('material-badge-secondary')) {
                        badge.style.setProperty('background', 'linear-gradient(135deg, #374151 0%, #4b5563 100%)', 'important');
                        badge.style.setProperty('background-image', 'none', 'important');
                    } else if (badge.classList.contains('material-badge-success')) {
                        badge.style.setProperty('background', 'linear-gradient(135deg, #16a34a 0%, #22c55e 100%)', 'important');
                        badge.style.setProperty('background-image', 'none', 'important');
                    } else if (badge.classList.contains('material-badge-warning')) {
                        badge.style.setProperty('background', 'linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%)', 'important');
                        badge.style.setProperty('background-image', 'none', 'important');
                    } else if (badge.classList.contains('status-active')) {
                        badge.style.setProperty('background', 'linear-gradient(135deg, #16a34a 0%, #22c55e 100%)', 'important');
                        badge.style.setProperty('background-image', 'none', 'important');
                    } else if (badge.classList.contains('status-inactive')) {
                        badge.style.setProperty('background', 'linear-gradient(135deg, #dc2626 0%, #ef4444 100%)', 'important');
                        badge.style.setProperty('background-image', 'none', 'important');
                    } else {
                        // For any other badge, use dark gray
                        const bgColor = window.getComputedStyle(badge).backgroundColor;
                        if (bgColor && (bgColor.includes('rgb(232, 245, 232)') || bgColor.includes('rgb(227, 242, 253)') || 
                            bgColor.includes('rgb(245, 245, 245)') || bgColor.includes('rgb(255, 243, 224)'))) {
                            badge.style.setProperty('background', 'linear-gradient(135deg, #374151 0%, #4b5563 100%)', 'important');
                            badge.style.setProperty('background-image', 'none', 'important');
                        }
                    }
                } else {
                    // Light mode - remove forced dark mode styles to restore original
                    // Get computed style to restore original values
                    const computedStyle = window.getComputedStyle(badge);
                    
                    // Remove inline styles completely
                    badge.style.removeProperty('color');
                    badge.style.removeProperty('background');
                    badge.style.removeProperty('background-image');
                    
                    // For badges with classes, restore based on their class
                    if (badge.classList.contains('material-badge-info') || badge.classList.contains('material-badge-primary')) {
                        // These should have light blue background with dark blue text in light mode
                        badge.style.setProperty('color', '#1565c0', 'important');
                        badge.style.setProperty('background', 'linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%)', 'important');
                    } else if (badge.classList.contains('material-badge-secondary')) {
                        // Light gray background with gray text in light mode
                        badge.style.setProperty('color', '#757575', 'important');
                        badge.style.setProperty('background', 'linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%)', 'important');
                    } else if (badge.classList.contains('material-badge-success')) {
                        // Light green background with dark green text in light mode
                        badge.style.setProperty('color', '#2e7d32', 'important');
                        badge.style.setProperty('background', 'linear-gradient(135deg, #e8f5e8 0%, #c8e6c9 100%)', 'important');
                    } else if (badge.classList.contains('material-badge-warning')) {
                        // Light orange background with dark orange text in light mode
                        badge.style.setProperty('color', '#f57c00', 'important');
                        badge.style.setProperty('background', 'linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%)', 'important');
                    } else if (badge.classList.contains('status-active')) {
                        // Green background with white text in light mode
                        badge.style.setProperty('color', '#ffffff', 'important');
                        badge.style.setProperty('background', 'linear-gradient(135deg, #4caf50 0%, #66bb6a 100%)', 'important');
                    } else if (badge.classList.contains('status-inactive')) {
                        // Red background with white text in light mode
                        badge.style.setProperty('color', '#ffffff', 'important');
                        badge.style.setProperty('background', 'linear-gradient(135deg, #f44336 0%, #ef5350 100%)', 'important');
                    }
                }
            });
            
            // Also update user-company elements
            const userCompanies = document.querySelectorAll('.user-company');
            userCompanies.forEach(function(element) {
                if (isDarkMode) {
                    element.style.setProperty('background', 'linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%)', 'important');
                    element.style.setProperty('background-image', 'none', 'important');
                    element.style.setProperty('color', '#ffffff', 'important');
                } else {
                    // Light mode - restore original light blue with dark blue text
                    element.style.setProperty('background', '#e3f2fd', 'important');
                    element.style.setProperty('color', '#1976d2', 'important');
                }
            });
            
            // Also update company-location elements
            const companyLocations = document.querySelectorAll('.company-location');
            companyLocations.forEach(function(element) {
                if (isDarkMode) {
                    element.style.setProperty('background', 'linear-gradient(135deg, #16a34a 0%, #22c55e 100%)', 'important');
                    element.style.setProperty('background-image', 'none', 'important');
                    element.style.setProperty('color', '#ffffff', 'important');
                } else {
                    // Light mode - restore original light green with dark green text
                    element.style.setProperty('background', '#e8f5e8', 'important');
                    element.style.setProperty('color', '#2e7d32', 'important');
                }
            });
            
            // Update detail page headers (user-header, company-header, etc.)
            const detailHeaders = document.querySelectorAll('.user-header, .company-header, .candidate-header, .role-header, .vacancy-header, .notification-header, .permission-header, .category-header, .email-template-header, .interview-header, .match-header, .payment-provider-header');
            detailHeaders.forEach(function(header) {
                if (isDarkMode) {
                    header.style.setProperty('background', 'linear-gradient(135deg, #374151 0%, #4b5563 100%)', 'important');
                    header.style.setProperty('background-image', 'none', 'important');
                    header.style.setProperty('border-left-color', '#60a5fa', 'important');
                } else {
                    // Light mode - restore original light gradient
                    header.style.setProperty('background', 'linear-gradient(135deg, #f8f9fa, #e9ecef)', 'important');
                    header.style.setProperty('border-left-color', 'var(--primary-color)', 'important');
                }
            });
            
            // Update detail page titles for better contrast
            const detailTitles = document.querySelectorAll('.user-title, .company-title, .candidate-title, .role-title, .vacancy-title, .notification-title, .permission-title, .category-title, .email-template-title, .interview-title, .match-title, .payment-provider-title');
            detailTitles.forEach(function(title) {
                if (isDarkMode) {
                    title.style.setProperty('color', '#ffffff', 'important');
                } else {
                    title.style.setProperty('color', '#212121', 'important');
                }
            });
            
            // Update meta items for better contrast
            const metaItems = document.querySelectorAll('.meta-item, .meta-item span, .user-meta, .user-meta span, .company-meta, .company-meta span, .candidate-meta, .candidate-meta span, .role-meta, .role-meta span, .vacancy-meta, .vacancy-meta span');
            metaItems.forEach(function(item) {
                if (isDarkMode) {
                    item.style.setProperty('color', '#ffffff', 'important');
                } else {
                    item.style.setProperty('color', '#424242', 'important');
                }
            });
            
            // Update material-btn-secondary buttons for visibility
            const secondaryButtons = document.querySelectorAll('.material-btn-secondary');
            secondaryButtons.forEach(function(button) {
                if (isDarkMode) {
                    button.style.setProperty('background', '#374151', 'important');
                    button.style.setProperty('background-color', '#374151', 'important');
                    button.style.setProperty('background-image', 'none', 'important');
                    button.style.setProperty('color', '#ffffff', 'important');
                    // Update icon colors
                    const icons = button.querySelectorAll('i');
                    icons.forEach(function(icon) {
                        icon.style.setProperty('color', '#ffffff', 'important');
                    });
                } else {
                    button.style.setProperty('background', '#f5f5f5', 'important');
                    button.style.setProperty('background-color', '#f5f5f5', 'important');
                    button.style.setProperty('color', '#212121', 'important');
                    // Update icon colors
                    const icons = button.querySelectorAll('i');
                    icons.forEach(function(icon) {
                        icon.style.setProperty('color', '#212121', 'important');
                    });
                }
            });
            
            // Update payment provider badges
            const providerTypes = document.querySelectorAll('.provider-type');
            providerTypes.forEach(function(badge) {
                if (isDarkMode) {
                    badge.style.setProperty('background', 'linear-gradient(135deg, #0d9488 0%, #14b8a6 100%)', 'important');
                    badge.style.setProperty('background-image', 'none', 'important');
                    badge.style.setProperty('color', '#ffffff', 'important');
                } else {
                    badge.style.setProperty('background', 'linear-gradient(135deg, #e0f2f1 0%, #b2dfdb 100%)', 'important');
                    badge.style.setProperty('color', '#00695c', 'important');
                }
            });
            
            const providerModes = document.querySelectorAll('.provider-mode');
            providerModes.forEach(function(badge) {
                if (isDarkMode) {
                    badge.style.setProperty('background', 'linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%)', 'important');
                    badge.style.setProperty('background-image', 'none', 'important');
                    badge.style.setProperty('color', '#ffffff', 'important');
                } else {
                    badge.style.setProperty('background', 'linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%)', 'important');
                    badge.style.setProperty('color', '#f57c00', 'important');
                }
            });
        }
        
        // Watch for theme changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                    setTimeout(updateBadgesForDarkMode, 100);
                }
            });
        });
        
        observer.observe(document.documentElement, {
            attributes: true,
            attributeFilter: ['data-theme', 'class']
        });
    </script>
</body>
</html>

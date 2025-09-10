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
    
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: var(--on-surface);
            overflow-x: hidden;
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
            background: #2C3E50;
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s ease;
            box-shadow: var(--elevation-3);
        }

        /* Logo Styling */
        .sidebar-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-lg);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            border-radius: var(--radius-md);
            margin: var(--spacing-md);
            box-shadow: var(--elevation-1);
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
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: all 0.2s ease;
            font-weight: 500;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(4px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }

        .nav-link.active {
            background-color: rgba(255, 255, 255, 0.25);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            border-left: 4px solid #3498db;
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
            background: linear-gradient(135deg, #FFE55C 0%, #FFF2CC 50%, #FFE55C 100%);
            border-radius: var(--radius-lg);
            border: 2px solid #FFD700;
            box-shadow: var(--elevation-3), inset 0 1px 0 rgba(255, 255, 255, 0.35);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .tenant-selector::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #FFF8DC 0%, #FFFFFF 50%, #FFF8DC 100%);
            box-shadow: 0 0 8px rgba(255, 248, 220, 0.65);
        }

        .tenant-selector::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .tenant-selector:hover::after {
            left: 100%;
        }

        .tenant-selector:hover {
            background: linear-gradient(135deg, #FFF2CC 0%, #FFF8DC 50%, #FFF2CC 100%);
            transform: translateY(-2px);
            box-shadow: var(--elevation-3), 0 8px 25px rgba(255, 242, 204, 0.35), inset 0 1px 0 rgba(255, 255, 255, 0.45);
            border-color: #FFC107;
        }

        .tenant-selector label {
            display: flex;
            align-items: center;
            margin-bottom: var(--spacing-md);
            font-size: 0.875rem;
            font-weight: 600;
            color: #8B6914;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.75);
        }

        .tenant-selector label i {
            margin-right: var(--spacing-sm);
            font-size: 0.875rem;
            color: #B8860B;
            text-shadow: 0 0 6px rgba(184, 134, 11, 0.45);
            filter: drop-shadow(0 1px 1px rgba(255, 255, 255, 0.45));
        }

        .tenant-selector select {
            width: 100%;
            padding: var(--spacing-md) var(--spacing-lg);
            border: 2px solid #FFD700;
            border-radius: var(--radius-md);
            background: linear-gradient(135deg, #DAA520 0%, #FFE55C 100%);
            color: #8B6914;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%238B6914' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right var(--spacing-md) center;
            background-size: 20px;
            padding-right: calc(var(--spacing-lg) + 24px);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.08), 0 1px 0 rgba(255, 255, 255, 0.35);
        }

        .tenant-selector select:focus {
            outline: none;
            border-color: #FFC107;
            box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.35), inset 0 2px 4px rgba(0, 0, 0, 0.08);
            background: linear-gradient(135deg, #FFE55C 0%, #FFF2CC 100%);
        }

        .tenant-selector select:hover {
            border-color: #FFC107;
            background: linear-gradient(135deg, #FFE55C 0%, #FFF2CC 100%);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.08), 0 1px 0 rgba(255, 255, 255, 0.4);
        }

        .tenant-selector select option {
            background-color: #757575;
            color: #FFFFFF;
            padding: var(--spacing-md);
            font-weight: 500;
            border: none;
        }

        .tenant-selector select option:hover {
            background-color: #9E9E9E;
        }

        .tenant-selector select option:checked {
            background: linear-gradient(135deg, #BDBDBD 0%, #9E9E9E 100%);
            color: #FFFFFF;
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
        }

        .admin-main.expanded {
            margin-left: 80px;
            width: calc(100% - 80px);
        }

        /* Header */
        .admin-header {
            background-color: white;
            border-bottom: 1px solid #e0e0e0;
            padding: var(--spacing-md) var(--spacing-lg);
            box-shadow: var(--elevation-1);
            position: sticky;
            top: 0;
            z-index: 100;
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
            color: var(--on-surface);
            font-size: 1.5rem;
            cursor: pointer;
            padding: var(--spacing-sm);
            border-radius: var(--radius-sm);
            transition: all 0.2s ease;
            display: block;
        }

        .menu-toggle:hover {
            background-color: #f5f5f5;
        }

        .page-title {
            margin: 0;
            font-weight: 600;
            font-size: 1.5rem;
            color: var(--on-surface);
        }

        .user-menu {
            position: relative;
            display: flex;
            align-items: center;
            gap: var(--spacing-md);
        }

        .user-button {
            background: none;
            border: 1px solid #e0e0e0;
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            transition: all 0.2s ease;
            font-weight: 500;
            color: var(--on-surface);
        }

        .user-button:hover {
            background-color: #f5f5f5;
        }

        .user-button span {
            color: var(--on-surface);
            font-weight: 500;
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
            background-color: white;
            border: 1px solid #e0e0e0;
            border-radius: var(--radius-lg);
            box-shadow: var(--elevation-3);
            z-index: 1000;
            margin-top: var(--spacing-sm);
            overflow: hidden;
            /* Ensure dropdown stays within viewport */
            max-width: calc(100vw - 40px);
            max-height: calc(100vh - 100px);
            /* Smooth transitions */
            transition: all 0.2s ease;
        }

        .notification-header {
            padding: var(--spacing-md);
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
        }

        .notification-header h4 {
            margin: 0;
            font-weight: 600;
            color: var(--on-surface);
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
            border-bottom: 1px solid #f0f0f0;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-sm);
        }

        .notification-item:hover {
            background-color: #f8f9fa;
        }

        .notification-item.read {
            opacity: 0.7;
        }

        .notification-item.unread {
            background-color: #f0f8ff;
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
            color: var(--on-surface);
            margin-bottom: var(--spacing-xs);
            font-size: 0.875rem;
        }

        .notification-message {
            color: #666;
            font-size: 0.8rem;
            margin-bottom: var(--spacing-xs);
            line-height: 1.4;
        }

        .notification-time {
            color: #999;
            font-size: 0.75rem;
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
            color: #999;
        }

        .notification-empty i {
            font-size: 2rem;
            margin-bottom: var(--spacing-sm);
        }

        .notification-footer {
            padding: var(--spacing-md);
            border-top: 1px solid #e0e0e0;
            background-color: #f8f9fa;
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
            background-color: #f5f5f5;
            flex: 1;
            width: 100%;
            min-height: calc(100vh - 80px);
            overflow-x: hidden;
        }

        /* Material Design Components */
        .material-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--elevation-1);
            border: none;
            margin-bottom: var(--spacing-lg);
            transition: box-shadow 0.3s ease;
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
            background-color: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--elevation-4);
            width: 90%;
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
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .settings-header h3 {
            margin: 0;
            font-weight: 600;
            color: var(--on-surface);
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
        }

        .setting-group {
            margin-bottom: var(--spacing-lg);
        }

        .setting-group:last-child {
            margin-bottom: 0;
        }

        .setting-label {
            font-weight: 600;
            color: var(--on-surface);
            margin-bottom: var(--spacing-sm);
            display: block;
        }

        .setting-description {
            color: #666;
            font-size: 0.875rem;
            margin-bottom: var(--spacing-md);
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

        [data-theme="dark"] .admin-header {
            background-color: var(--card-background);
            border-bottom-color: var(--border-color);
        }

        [data-theme="dark"] .admin-content {
            background-color: var(--background-color);
        }

        [data-theme="dark"] .material-card {
            background-color: var(--card-background);
            color: var(--on-surface);
        }

        [data-theme="dark"] .settings-content {
            background-color: var(--card-background);
            color: var(--on-surface);
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

        [data-theme="dark"] .user-button {
            border-color: var(--border-color);
            background-color: var(--card-background);
            color: var(--on-surface);
        }

        [data-theme="dark"] .user-button:hover {
            background-color: var(--surface-color);
        }

        [data-theme="dark"] .user-button span {
            color: var(--on-surface);
        }

        [data-theme="dark"] .user-button i {
            color: var(--on-surface);
        }
            color: #1565c0;
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
                    <div class="setting-group">
                        <label class="setting-label">Thema</label>
                        <div class="setting-description">
                            Kies tussen light mode en dark mode voor een betere gebruikerservaring.
                        </div>
                        <div class="theme-options">
                            <div class="theme-toggle" id="lightTheme" onclick="setTheme('light')">
                                <div class="theme-icon">
                                    <i class="fas fa-sun"></i>
                                </div>
                                <div class="theme-info">
                                    <div class="theme-name">Light Mode</div>
                                    <div class="theme-description">Helder thema voor overdag gebruik</div>
                                </div>
                                <div class="theme-check">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="theme-toggle" id="darkTheme" onclick="setTheme('dark')">
                                <div class="theme-icon">
                                    <i class="fas fa-moon"></i>
                                </div>
                                <div class="theme-info">
                                    <div class="theme-name">Dark Mode</div>
                                    <div class="theme-description">Donker thema voor nachtelijk gebruik</div>
                                </div>
                                <div class="theme-check">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
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
            const savedTheme = localStorage.getItem('admin-theme') || 'light';
            setTheme(savedTheme);
        }

        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            localStorage.setItem('admin-theme', theme);
            
            // Update theme toggles
            const lightTheme = document.getElementById('lightTheme');
            const darkTheme = document.getElementById('darkTheme');
            
            if (theme === 'light') {
                lightTheme.classList.add('active');
                darkTheme.classList.remove('active');
            } else {
                darkTheme.classList.add('active');
                lightTheme.classList.remove('active');
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
        });
    </script>
</body>
</html>

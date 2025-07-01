<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?><?php echo htmlspecialchars($settings['site_title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description ?? $settings['site_description']); ?>">
    
    <!-- Mobile-first responsive design -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
    
    <!-- Theme CSS -->
    <link rel="stylesheet" href="/themes/<?php echo getActiveTheme(); ?>/style.css?v=<?php echo time(); ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
    
    <!-- Open Graph meta tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($settings['site_title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($settings['site_description']); ?>">
    <meta property="og:type" content="website">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="header-content">
                <div class="logo-section">
                    <h1 class="site-title">
                        <a href="/"><?php echo htmlspecialchars($settings['site_title']); ?></a>
                    </h1>
                    <p class="site-description"><?php echo htmlspecialchars($settings['site_description']); ?></p>
                </div>
                
                <!-- Mobile menu toggle -->
                <button class="mobile-menu-toggle" aria-label="Toggle mobile menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                
                <!-- Navigation -->
                <nav class="main-navigation">
                    <ul class="nav-menu">
                        <li><a href="/" class="nav-link">Home</a></li>
                        <li><a href="/admin/" class="nav-link">Admin</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
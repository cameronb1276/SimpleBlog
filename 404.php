<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
$settings = getSettings();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found - <?php echo htmlspecialchars($settings['site_title']); ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <div class="container">
            <h1 class="site-title">
                <a href="/"><?php echo htmlspecialchars($settings['site_title']); ?></a>
            </h1>
        </div>
    </header>
    
    <main class="main-content">
        <div class="container">
            <div class="error-page">
                <h1>404 - Page Not Found</h1>
                <p>The page you are looking for doesn't exist.</p>
                <a href="/" class="button">Go Home</a>
            </div>
        </div>
    </main>
</body>
</html>

<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$user = getCurrentUser();
$settings = getSettings();
$error = '';
$success = '';

// Handle theme activation
if ($_POST['action'] ?? '' === 'activate_theme') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $themeName = $_POST['theme_name'] ?? '';
        if (setActiveTheme($themeName)) {
            $success = "Theme '$themeName' activated successfully!";
        } else {
            $error = "Failed to activate theme '$themeName'. Theme may be invalid.";
        }
    }
}

// Handle theme deletion
if ($_POST['action'] ?? '' === 'delete_theme') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $themeName = $_POST['theme_name'] ?? '';
        if ($themeName === 'default') {
            $error = 'Cannot delete the default theme.';
        } elseif (removeTheme($themeName)) {
            $success = "Theme '$themeName' deleted successfully!";
        } else {
            $error = "Failed to delete theme '$themeName'.";
        }
    }
}

// Handle theme upload
if ($_POST['action'] ?? '' === 'upload_theme') {
    if (!validateCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $themeName = sanitize_input($_POST['theme_name'] ?? '');
        
        if (empty($themeName)) {
            $error = 'Theme name is required.';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $themeName)) {
            $error = 'Theme name can only contain letters, numbers, hyphens, and underscores.';
        } elseif (isset($_FILES['theme_zip']) && $_FILES['theme_zip']['error'] === UPLOAD_ERR_OK) {
            $uploadPath = $_FILES['theme_zip']['tmp_name'];
            $uploadSize = $_FILES['theme_zip']['size'];
            
            // Validate file size (max 10MB)
            if ($uploadSize > 10 * 1024 * 1024) {
                $error = 'File size too large. Maximum allowed size is 10MB.';
            } else {
                // Validate file type
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $uploadPath);
                finfo_close($finfo);
                
                // Check both MIME type and file extension
                $fileExt = strtolower(pathinfo($_FILES['theme_zip']['name'], PATHINFO_EXTENSION));
                
                if ($mimeType !== 'application/zip' && $mimeType !== 'application/x-zip-compressed' || $fileExt !== 'zip') {
                    $error = 'Please upload a valid ZIP file.';
                } else {
                    // Additional security: scan for malicious content
                    $fileContent = file_get_contents($uploadPath, false, null, 0, 1024);
                    if (strpos($fileContent, '<?php') !== false && strpos($fileContent, 'eval(') !== false) {
                        $error = 'Uploaded file contains potentially malicious code.';
                    } elseif (installThemeFromZip($uploadPath, $themeName)) {
                        $success = "Theme '$themeName' installed successfully!";
                    } else {
                        $error = "Failed to install theme. Please ensure the ZIP contains all required files and no malicious content.";
                    }
                }
            }
        } else {
            $error = 'Please select a ZIP file to upload.';
        }
    }
}

$installedThemes = getInstalledThemes();
$activeTheme = getActiveTheme();

generateCSRF();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Management - <?php echo htmlspecialchars($settings['site_title']); ?></title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-wrapper">
        <nav class="admin-nav">
            <div class="admin-nav-header">
                <h2>Admin Panel</h2>
            </div>
            <ul class="admin-nav-menu">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="posts.php">Posts</a></li>
                <li><a href="create-post.php">New Post</a></li>
                <li><a href="themes.php" class="active">Themes</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="/" target="_blank">View Site</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>Theme Management</h1>
                <p>Upload, activate, and manage blog themes</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Upload New Theme -->
            <div class="admin-card">
                <h3>Upload New Theme</h3>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="action" value="upload_theme">
                    
                    <div class="form-group">
                        <label for="theme_name">Theme Name:</label>
                        <input type="text" id="theme_name" name="theme_name" required 
                               pattern="[a-zA-Z0-9_-]+" 
                               title="Only letters, numbers, hyphens, and underscores allowed">
                        <small>Only letters, numbers, hyphens, and underscores allowed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="theme_zip">Theme ZIP File:</label>
                        <input type="file" id="theme_zip" name="theme_zip" accept=".zip" required>
                        <small>Upload a ZIP file containing theme files (header.php, footer.php, index.php, post.php, style.css)</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Install Theme</button>
                    </div>
                </form>
            </div>
            
            <!-- Installed Themes -->
            <div class="admin-card">
                <h3>Installed Themes</h3>
                
                <?php if (empty($installedThemes)): ?>
                    <p>No themes installed.</p>
                <?php else: ?>
                    <div class="themes-grid">
                        <?php foreach ($installedThemes as $themeName => $themeInfo): ?>
                            <div class="theme-card <?php echo $themeName === $activeTheme ? 'active-theme' : ''; ?>">
                                <div class="theme-preview">
                                    <?php
                                    $previewImage = "/themes/$themeName/preview.png";
                                    $previewPath = __DIR__ . '/../themes/' . $themeName . '/preview.png';
                                    if (file_exists($previewPath)):
                                    ?>
                                        <img src="<?php echo $previewImage; ?>" alt="<?php echo htmlspecialchars($themeInfo['name']); ?> Preview">
                                    <?php else: ?>
                                        <div class="theme-placeholder">
                                            <span>No Preview</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="theme-info">
                                    <h4><?php echo htmlspecialchars($themeInfo['name']); ?></h4>
                                    <p class="theme-description"><?php echo htmlspecialchars($themeInfo['description']); ?></p>
                                    <p class="theme-meta">
                                        <small>
                                            <strong>Version:</strong> <?php echo htmlspecialchars($themeInfo['version']); ?><br>
                                            <strong>Author:</strong> <?php echo htmlspecialchars($themeInfo['author']); ?>
                                        </small>
                                    </p>
                                </div>
                                
                                <div class="theme-actions">
                                    <?php if ($themeName === $activeTheme): ?>
                                        <span class="btn btn-success">Active</span>
                                    <?php else: ?>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="action" value="activate_theme">
                                            <input type="hidden" name="theme_name" value="<?php echo htmlspecialchars($themeName); ?>">
                                            <button type="submit" class="btn btn-primary">Activate</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($themeName !== 'default'): ?>
                                        <form method="post" style="display: inline;" 
                                              onsubmit="return confirm('Are you sure you want to delete this theme?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                            <input type="hidden" name="action" value="delete_theme">
                                            <input type="hidden" name="theme_name" value="<?php echo htmlspecialchars($themeName); ?>">
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Theme Development Guide -->
            <div class="admin-card">
                <h3>Theme Development Guide</h3>
                <p>To create a custom theme for SimpleBlog, create a ZIP file with the following structure:</p>
                
                <div class="code-block">
                    <pre>theme-name.zip
├── header.php      (Required)
├── footer.php      (Required)
├── index.php       (Required - Blog home page)
├── post.php        (Required - Single post page)
├── style.css       (Required)
├── theme.json      (Optional - Theme metadata)
├── preview.png     (Optional - Theme preview image)
└── assets/         (Optional - Additional CSS/JS/images)</pre>
                </div>
                
                <h4>Required Functions in Theme Files:</h4>
                <ul>
                    <li><strong>header.php:</strong> Use <code>getActiveTheme()</code> for asset URLs</li>
                    <li><strong>index.php:</strong> Use <code>loadThemeTemplate('header')</code> and <code>loadThemeTemplate('footer')</code></li>
                    <li><strong>post.php:</strong> Use <code>loadThemeTemplate('header')</code> and <code>loadThemeTemplate('footer')</code></li>
                    <li><strong>style.css:</strong> All theme styles should be self-contained</li>
                </ul>
                
                <h4>Available Variables:</h4>
                <ul>
                    <li><strong>$settings:</strong> Site configuration (title, description, etc.)</li>
                    <li><strong>$posts:</strong> Array of posts (index.php)</li>
                    <li><strong>$post:</strong> Single post data (post.php)</li>
                    <li><strong>$page, $total_pages:</strong> Pagination data (index.php)</li>
                </ul>
            </div>
        </main>
    </div>
</body>
</html>
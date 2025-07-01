<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$user = getCurrentUser();
$settings = getSettings();
$error = '';
$success = '';

if ($_POST && validateCSRF($_POST['csrf_token'] ?? '')) {
    $site_title = sanitize_input($_POST['site_title'] ?? '');
    $site_description = sanitize_input($_POST['site_description'] ?? '');
    $posts_per_page = intval($_POST['posts_per_page'] ?? 10);
    $allow_comments = $_POST['allow_comments'] ?? 'no';
    $date_format = sanitize_input($_POST['date_format'] ?? 'F j, Y');
    $time_format = sanitize_input($_POST['time_format'] ?? 'g:i a');
    
    if (empty($site_title)) {
        $error = "Site title is required.";
    } else {
        $updates = [
            'site_title' => $site_title,
            'site_description' => $site_description,
            'posts_per_page' => max(1, min(50, $posts_per_page)), // Between 1 and 50
            'allow_comments' => in_array($allow_comments, ['yes', 'no']) ? $allow_comments : 'no',
            'date_format' => $date_format,
            'time_format' => $time_format
        ];
        
        $success_count = 0;
        foreach ($updates as $option_name => $option_value) {
            $stmt = $pdo->prepare("UPDATE settings SET option_value = ? WHERE option_name = ?");
            if ($stmt->execute([$option_value, $option_name])) {
                $success_count++;
            }
        }
        
        if ($success_count === count($updates)) {
            $success = "Settings updated successfully!";
            // Refresh settings
            $settings = getSettings();
        } else {
            $error = "Some settings could not be updated.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - <?php echo htmlspecialchars($settings['site_title']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                <li><a href="themes.php">Themes</a></li>
                <li><a href="settings.php" class="active">Settings</a></li>
                <li><a href="/" target="_blank">View Site</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>Site Settings</h1>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="admin-section">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                    
                    <h3>General Settings</h3>
                    
                    <div class="form-group">
                        <label for="site_title">Site Title *</label>
                        <input type="text" id="site_title" name="site_title" value="<?php echo htmlspecialchars($_POST['site_title'] ?? $settings['site_title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="site_description">Site Description</label>
                        <textarea id="site_description" name="site_description" rows="3"><?php echo htmlspecialchars($_POST['site_description'] ?? $settings['site_description']); ?></textarea>
                    </div>
                    
                    <h3>Display Settings</h3>
                    
                    <div class="form-group">
                        <label for="posts_per_page">Posts per Page</label>
                        <input type="number" id="posts_per_page" name="posts_per_page" min="1" max="50" value="<?php echo intval($_POST['posts_per_page'] ?? $settings['posts_per_page']); ?>">
                        <small>Number of posts to show on the homepage (1-50)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_format">Date Format</label>
                        <select id="date_format" name="date_format">
                            <option value="F j, Y" <?php echo ($_POST['date_format'] ?? $settings['date_format']) === 'F j, Y' ? 'selected' : ''; ?>>January 1, 2024</option>
                            <option value="Y-m-d" <?php echo ($_POST['date_format'] ?? $settings['date_format']) === 'Y-m-d' ? 'selected' : ''; ?>>2024-01-01</option>
                            <option value="m/d/Y" <?php echo ($_POST['date_format'] ?? $settings['date_format']) === 'm/d/Y' ? 'selected' : ''; ?>>01/01/2024</option>
                            <option value="d/m/Y" <?php echo ($_POST['date_format'] ?? $settings['date_format']) === 'd/m/Y' ? 'selected' : ''; ?>>01/01/2024</option>
                            <option value="j F Y" <?php echo ($_POST['date_format'] ?? $settings['date_format']) === 'j F Y' ? 'selected' : ''; ?>>1 January 2024</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="time_format">Time Format</label>
                        <select id="time_format" name="time_format">
                            <option value="g:i a" <?php echo ($_POST['time_format'] ?? $settings['time_format']) === 'g:i a' ? 'selected' : ''; ?>>12:00 pm</option>
                            <option value="H:i" <?php echo ($_POST['time_format'] ?? $settings['time_format']) === 'H:i' ? 'selected' : ''; ?>>24:00</option>
                        </select>
                    </div>
                    
                    <h3>Features</h3>
                    
                    <div class="form-group">
                        <label for="allow_comments">Comments</label>
                        <select id="allow_comments" name="allow_comments">
                            <option value="yes" <?php echo ($_POST['allow_comments'] ?? $settings['allow_comments']) === 'yes' ? 'selected' : ''; ?>>Enable comments</option>
                            <option value="no" <?php echo ($_POST['allow_comments'] ?? $settings['allow_comments']) === 'no' ? 'selected' : ''; ?>>Disable comments</option>
                        </select>
                        <small>Note: Comment functionality is not yet implemented</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn">Save Settings</button>
                    </div>
                </form>
            </div>
            
            <div class="admin-section">
                <h3>System Information</h3>
                <table class="info-table">
                    <tr>
                        <td><strong>Blog Version:</strong></td>
                        <td>SimpleBlog 1.0</td>
                    </tr>
                    <tr>
                        <td><strong>PHP Version:</strong></td>
                        <td><?php echo PHP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Database:</strong></td>
                        <td><?php echo $pdo->getAttribute(PDO::ATTR_SERVER_VERSION); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Upload Limit:</strong></td>
                        <td><?php echo ini_get('upload_max_filesize'); ?></td>
                    </tr>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
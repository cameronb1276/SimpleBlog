<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$user = getCurrentUser();
$settings = getSettings();
$error = '';
$success = '';

if ($_POST && validateCSRF($_POST['csrf_token'] ?? '')) {
    $title = sanitize_input($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $excerpt = sanitize_input($_POST['excerpt'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'draft';
    
    if (empty($title) || empty($content)) {
        $error = "Title and content are required.";
    } else {
        $slug = generate_slug($title);
        
        // Check if slug already exists
        $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug .= '-' . time();
        }
        
        // Handle file upload
        $featured_image = null;
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            try {
                $featured_image = uploadFile($_FILES['featured_image']);
            } catch (Exception $e) {
                $error = "File upload error: " . $e->getMessage();
            }
        }
        
        if (empty($error)) {
            // Generate excerpt if not provided
            if (empty($excerpt)) {
                $excerpt = substr(strip_tags($content), 0, 160) . '...';
            }
            
            $stmt = $pdo->prepare("INSERT INTO posts (title, slug, content, excerpt, featured_image, author_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $slug, $content, $excerpt, $featured_image, $user['id'], $status])) {
                $success = "Post created successfully!";
                if ($status === 'published') {
                    $success .= ' <a href="/post/' . htmlspecialchars($slug) . '" target="_blank">View post</a>';
                }
            } else {
                $error = "Failed to create post.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post - <?php echo htmlspecialchars($settings['site_title']); ?></title>
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
                <li><a href="create-post.php" class="active">New Post</a></li>
                <li><a href="themes.php">Themes</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="/" target="_blank">View Site</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>Create New Post</h1>
                <a href="posts.php" class="btn btn-secondary">Back to Posts</a>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="admin-section">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                    
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Content *</label>
                        <textarea id="content" name="content" required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="excerpt">Excerpt</label>
                        <textarea id="excerpt" name="excerpt" rows="3" placeholder="Brief description (optional - will be auto-generated if empty)"><?php echo htmlspecialchars($_POST['excerpt'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="featured_image">Featured Image</label>
                        <input type="file" id="featured_image" name="featured_image" accept="image/*">
                        <small>Allowed formats: JPG, PNG, GIF (max 10MB)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="draft" <?php echo ($_POST['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo ($_POST['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn">Create Post</button>
                        <a href="posts.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
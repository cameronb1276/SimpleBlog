<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$user = getCurrentUser();
$settings = getSettings();
$error = '';
$success = '';

$post_id = intval($_GET['id'] ?? 0);
if (!$post_id) {
    header('Location: posts.php');
    exit;
}

// Get the post
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: posts.php');
    exit;
}

if ($_POST && validateCSRF($_POST['csrf_token'] ?? '')) {
    $title = sanitize_input($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $excerpt = sanitize_input($_POST['excerpt'] ?? '');
    $status = in_array($_POST['status'] ?? '', ['draft', 'published']) ? $_POST['status'] : 'draft';
    
    if (empty($title) || empty($content)) {
        $error = "Title and content are required.";
    } else {
        $slug = generate_slug($title);
        
        // Check if slug already exists (but not for this post)
        $stmt = $pdo->prepare("SELECT id FROM posts WHERE slug = ? AND id != ?");
        $stmt->execute([$slug, $post_id]);
        if ($stmt->fetch()) {
            $slug .= '-' . time();
        }
        
        // Handle file upload
        $featured_image = $post['featured_image']; // Keep existing image by default
        if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
            try {
                $featured_image = uploadFile($_FILES['featured_image']);
                // Delete old image if it exists
                if ($post['featured_image'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $post['featured_image'])) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . $post['featured_image']);
                }
            } catch (Exception $e) {
                $error = "File upload error: " . $e->getMessage();
            }
        }
        
        if (empty($error)) {
            // Generate excerpt if not provided
            if (empty($excerpt)) {
                $excerpt = substr(strip_tags($content), 0, 160) . '...';
            }
            
            $stmt = $pdo->prepare("UPDATE posts SET title = ?, slug = ?, content = ?, excerpt = ?, featured_image = ?, status = ?, updated_at = NOW() WHERE id = ?");
            if ($stmt->execute([$title, $slug, $content, $excerpt, $featured_image, $status, $post_id])) {
                $success = "Post updated successfully!";
                if ($status === 'published') {
                    $success .= ' <a href="/post/' . htmlspecialchars($slug) . '" target="_blank">View post</a>';
                }
                // Refresh post data
                $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
                $stmt->execute([$post_id]);
                $post = $stmt->fetch();
            } else {
                $error = "Failed to update post.";
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
    <title>Edit Post - <?php echo htmlspecialchars($settings['site_title']); ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../css/admin-extras.css">
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
                <li><a href="settings.php">Settings</a></li>
                <li><a href="/" target="_blank">View Site</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>Edit Post</h1>
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
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? $post['title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Content *</label>
                        <textarea id="content" name="content" required><?php echo htmlspecialchars($_POST['content'] ?? $post['content']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="excerpt">Excerpt</label>
                        <textarea id="excerpt" name="excerpt" rows="3" placeholder="Brief description (optional - will be auto-generated if empty)"><?php echo htmlspecialchars($_POST['excerpt'] ?? $post['excerpt']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="featured_image">Featured Image</label>
                        <?php if ($post['featured_image']): ?>
                            <div class="featured-image-current">
                                <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="Current featured image" class="featured-image-preview">
                                <p><small>Current image</small></p>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="featured_image" name="featured_image" accept="image/*">
                        <small>Upload a new image to replace the current one. Allowed formats: JPG, PNG, GIF (max 10MB)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="draft" <?php echo ($_POST['status'] ?? $post['status']) === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="published" <?php echo ($_POST['status'] ?? $post['status']) === 'published' ? 'selected' : ''; ?>>Published</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn">Update Post</button>
                        <a href="posts.php" class="btn btn-secondary">Cancel</a>
                        <?php if ($post['status'] === 'published'): ?>
                            <a href="/post/<?php echo htmlspecialchars($post['slug']); ?>" target="_blank" class="btn btn-secondary">View Post</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script src="../js/admin.js"></script>
</body>
</html>
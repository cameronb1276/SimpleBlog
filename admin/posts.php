<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$user = getCurrentUser();
$settings = getSettings();

// Handle post deletion
if (isset($_POST['delete_post']) && isset($_POST['post_id']) && validateCSRF($_POST['csrf_token'])) {
    $post_id = intval($_POST['post_id']);
    $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    if ($stmt->execute([$post_id])) {
        $success = "Post deleted successfully.";
    } else {
        $error = "Failed to delete post.";
    }
}

// Get all posts
$stmt = $pdo->query("SELECT p.*, u.display_name as author 
                     FROM posts p 
                     LEFT JOIN users u ON p.author_id = u.id 
                     ORDER BY p.created_at DESC");
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts - <?php echo htmlspecialchars($settings['site_title']); ?></title>
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
                <li><a href="posts.php" class="active">Posts</a></li>
                <li><a href="create-post.php">New Post</a></li>
                <li><a href="themes.php">Themes</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="/" target="_blank">View Site</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main class="admin-main">
            <div class="admin-header">
                <h1>All Posts</h1>
                <a href="create-post.php" class="btn">Create New Post</a>
            </div>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="admin-section">
                <?php if (empty($posts)): ?>
                    <p>No posts yet. <a href="create-post.php">Create your first post</a>!</p>
                <?php else: ?>
                    <div class="posts-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td>
                                            <a href="/post/<?php echo htmlspecialchars($post['slug']); ?>" target="_blank">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($post['author'] ?: 'Unknown'); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $post['status']; ?>">
                                                <?php echo ucfirst($post['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($post['created_at'])); ?></td>
                                        <td class="actions">
                                            <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="btn-small">Edit</a>
                                            <form method="POST" class="delete-form">
                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRF(); ?>">
                                                <button type="submit" name="delete_post" class="btn-small btn-danger">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="../js/admin.js"></script>
</body>
</html>
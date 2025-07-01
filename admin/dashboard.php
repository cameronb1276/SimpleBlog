<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

requireLogin();

$user = getCurrentUser();
$settings = getSettings();

// Get post counts
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM posts GROUP BY status");
$post_counts = [];
while ($row = $stmt->fetch()) {
    $post_counts[$row['status']] = $row['count'];
}

$published_count = $post_counts['published'] ?? 0;
$draft_count = $post_counts['draft'] ?? 0;

// Get recent posts
$recent_posts = getPosts('published', 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo htmlspecialchars($settings['site_title']); ?></title>
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
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
                <h1>Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($user['display_name']); ?>!</p>
            </div>
            
            <div class="admin-stats">
                <div class="stat-card">
                    <h3>Published Posts</h3>
                    <div class="stat-number"><?php echo $published_count; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Draft Posts</h3>
                    <div class="stat-number"><?php echo $draft_count; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Posts</h3>
                    <div class="stat-number"><?php echo $published_count + $draft_count; ?></div>
                </div>
            </div>
            
            <div class="admin-section">
                <h2>Recent Posts</h2>
                <?php if (empty($recent_posts)): ?>
                    <p>No posts yet. <a href="create-post.php">Create your first post</a>!</p>
                <?php else: ?>
                    <div class="posts-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_posts as $post): ?>
                                    <tr>
                                        <td>
                                            <a href="/post/<?php echo htmlspecialchars($post['slug']); ?>" target="_blank">
                                                <?php echo htmlspecialchars($post['title']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $post['status']; ?>">
                                                <?php echo ucfirst($post['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M j, Y', strtotime($post['created_at'])); ?></td>
                                        <td>
                                            <a href="edit-post.php?id=<?php echo $post['id']; ?>" class="btn-small">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <p><a href="posts.php">View all posts &rarr;</a></p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>

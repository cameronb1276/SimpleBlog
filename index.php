<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$page = max(1, intval($_GET['page'] ?? 1));
$posts_per_page = intval(getSetting('posts_per_page', 10));
$offset = ($page - 1) * $posts_per_page;

$posts = getPosts('published', $posts_per_page, $offset);
$settings = getSettings();

// Get total posts for pagination
$stmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'");
$total_posts = $stmt->fetchColumn();
$total_pages = ceil($total_posts / $posts_per_page);

// Load theme template
loadThemeTemplate('index', compact('posts', 'settings', 'page', 'total_pages', 'total_posts', 'posts_per_page'));

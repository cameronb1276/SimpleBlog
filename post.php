<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

$post = getPostBySlug($slug);
if (!$post) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

$settings = getSettings();

// Load theme template
loadThemeTemplate('post', compact('post', 'settings'));

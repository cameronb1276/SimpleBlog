<?php
function sanitize_input($data) {
    // Enhanced sanitization for security
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    
    // Remove null bytes and control characters
    $data = str_replace(chr(0), '', $data);
    $data = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $data);
    
    // Trim whitespace
    $data = trim($data);
    
    // Strip tags and encode HTML entities
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8', true);
    
    return $data;
}

// Additional security function for file uploads
function sanitize_filename($filename) {
    // Remove path traversal attempts
    $filename = basename($filename);
    
    // Remove dangerous characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    
    // Prevent hidden files
    $filename = ltrim($filename, '.');
    
    // Limit length
    $filename = substr($filename, 0, 255);
    
    return $filename;
}

// Security function for URL validation
function validate_url($url) {
    $url = filter_var($url, FILTER_SANITIZE_URL);
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }
    
    // Prevent SSRF attacks - only allow HTTP/HTTPS
    $parsed = parse_url($url);
    if (!in_array($parsed['scheme'] ?? '', ['http', 'https'])) {
        return false;
    }
    
    return $url;
}

function generate_slug($string) {
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $string), '-'));
    return substr($slug, 0, 200);
}

function getPosts($status = 'published', $limit = null, $offset = 0) {
    global $pdo;
    
    $sql = "SELECT p.*, u.display_name as author 
            FROM posts p 
            LEFT JOIN users u ON p.author_id = u.id 
            WHERE p.status = ? 
            ORDER BY p.created_at DESC";
    
    if ($limit) {
        $sql .= " LIMIT " . intval($limit) . " OFFSET " . intval($offset);
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status]);
    return $stmt->fetchAll();
}

function getPost($id) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT p.*, u.display_name as author 
                          FROM posts p 
                          LEFT JOIN users u ON p.author_id = u.id 
                          WHERE p.id = ? AND p.status = 'published'");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getPostBySlug($slug) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT p.*, u.display_name as author 
                          FROM posts p 
                          LEFT JOIN users u ON p.author_id = u.id 
                          WHERE p.slug = ? AND p.status = 'published'");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function getSettings() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT option_name, option_value FROM settings WHERE autoload = 'yes'");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['option_name']] = $row['option_value'];
    }
    return $settings;
}

function getSetting($name, $default = '') {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT option_value FROM settings WHERE option_name = ?");
    $stmt->execute([$name]);
    $result = $stmt->fetch();
    return $result ? $result['option_value'] : $default;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function validateCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    // Use timing-safe comparison
    return hash_equals($_SESSION['csrf_token'], $token);
}

function generateCSRF() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Enhanced session security
function secure_session_start() {
    // Prevent session fixation
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 1 : 0);
        ini_set('session.cookie_samesite', 'Strict');
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

// Rate limiting function
function check_rate_limit($action, $max_attempts = 5, $time_window = 300) {
    $key = $action . '_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    
    $now = time();
    
    // Clean old entries
    foreach ($_SESSION['rate_limits'] as $k => $data) {
        if ($now - $data['first_attempt'] > $time_window) {
            unset($_SESSION['rate_limits'][$k]);
        }
    }
    
    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = [
            'count' => 1,
            'first_attempt' => $now
        ];
        return true;
    }
    
    $_SESSION['rate_limits'][$key]['count']++;
    
    return $_SESSION['rate_limits'][$key]['count'] <= $max_attempts;
}

// Theme system functions
function getActiveTheme() {
    return getSetting('active_theme', 'default');
}

function getInstalledThemes() {
    $themes = [];
    $themesDir = __DIR__ . '/../themes/';
    
    if (is_dir($themesDir)) {
        $dirs = array_diff(scandir($themesDir), ['.', '..']);
        foreach ($dirs as $dir) {
            if (is_dir($themesDir . $dir)) {
                $themeInfo = getThemeInfo($dir);
                $themes[$dir] = $themeInfo;
            }
        }
    }
    
    return $themes;
}

function getThemeInfo($themeName) {
    $themeDir = __DIR__ . '/../themes/' . $themeName . '/';
    $themeJsonPath = $themeDir . 'theme.json';
    
    $defaultInfo = [
        'name' => ucfirst($themeName),
        'version' => '1.0.0',
        'author' => 'Unknown',
        'description' => 'No description available',
        'required_files' => ['header.php', 'footer.php', 'index.php', 'post.php', 'style.css']
    ];
    
    if (file_exists($themeJsonPath)) {
        $json = json_decode(file_get_contents($themeJsonPath), true);
        return array_merge($defaultInfo, $json ?: []);
    }
    
    return $defaultInfo;
}

function validateTheme($themeName) {
    $themeDir = __DIR__ . '/../themes/' . $themeName . '/';
    $themeInfo = getThemeInfo($themeName);
    
    if (!is_dir($themeDir)) {
        return false;
    }
    
    // Check required files exist
    foreach ($themeInfo['required_files'] as $file) {
        if (!file_exists($themeDir . $file)) {
            return false;
        }
    }
    
    return true;
}

function loadThemeTemplate($template, $variables = []) {
    global $settings, $posts, $post, $page, $total_pages, $total_posts, $posts_per_page;
    
    // Security: Validate template name to prevent directory traversal
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $template)) {
        throw new Exception("Invalid template name: $template");
    }
    
    $activeTheme = getActiveTheme();
    
    // Security: Validate theme name
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $activeTheme)) {
        $activeTheme = 'default';
    }
    
    $themesDir = realpath(__DIR__ . '/../themes/');
    $themeFile = $themesDir . '/' . $activeTheme . '/' . $template . '.php';
    $defaultFile = $themesDir . '/default/' . $template . '.php';
    
    // Security: Ensure files are within themes directory
    if (strpos(realpath(dirname($themeFile)), $themesDir) !== 0) {
        throw new Exception("Invalid theme path");
    }
    
    // Extract variables to make them available in template
    extract($variables, EXTR_SKIP); // EXTR_SKIP prevents overwriting existing vars
    
    // Try active theme first, fallback to default
    if (file_exists($themeFile) && strpos(realpath($themeFile), $themesDir) === 0) {
        include $themeFile;
    } elseif (file_exists($defaultFile) && strpos(realpath($defaultFile), $themesDir) === 0) {
        include $defaultFile;
    } else {
        throw new Exception("Template not found: $template");
    }
}

function setActiveTheme($themeName) {
    global $pdo;
    
    if (!validateTheme($themeName)) {
        return false;
    }
    
    $stmt = $pdo->prepare("UPDATE settings SET option_value = ? WHERE option_name = 'active_theme'");
    return $stmt->execute([$themeName]);
}

function installThemeFromZip($zipPath, $themeName) {
    // Security: Validate theme name
    if (!preg_match('/^[a-zA-Z0-9_-]{1,50}$/', $themeName)) {
        return false;
    }
    
    // Security: Validate zip file path
    if (!file_exists($zipPath) || !is_readable($zipPath)) {
        return false;
    }
    
    $themesDir = realpath(__DIR__ . '/../themes/');
    $themeDir = $themesDir . '/' . $themeName . '/';
    
    // Security: Ensure theme directory is within themes folder
    if (strpos(realpath(dirname($themeDir)), $themesDir) !== 0) {
        return false;
    }
    
    // Create theme directory
    if (!is_dir($themeDir)) {
        if (!mkdir($themeDir, 0755, true)) {
            return false;
        }
    }
    
    $zip = new ZipArchive();
    if ($zip->open($zipPath) === TRUE) {
        // Security: Validate zip contents before extraction
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            // Prevent directory traversal
            if (strpos($filename, '../') !== false || strpos($filename, '..\\') !== false) {
                $zip->close();
                return false;
            }
            
            // Only allow safe file extensions
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $allowedExt = ['php', 'css', 'js', 'json', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'txt', 'md'];
            if (!empty($ext) && !in_array($ext, $allowedExt)) {
                $zip->close();
                return false;
            }
            
            // Prevent hidden files and system files
            $basename = basename($filename);
            if (strpos($basename, '.') === 0 || in_array($basename, ['.htaccess', '.htpasswd', 'web.config'])) {
                $zip->close();
                return false;
            }
        }
        
        $zip->extractTo($themeDir);
        $zip->close();
        
        // Validate extracted theme
        if (validateTheme($themeName)) {
            return true;
        } else {
            // Remove invalid theme
            removeTheme($themeName);
            return false;
        }
    }
    
    return false;
}

function removeTheme($themeName) {
    if ($themeName === 'default') {
        return false; // Cannot remove default theme
    }
    
    $themeDir = __DIR__ . '/../themes/' . $themeName . '/';
    if (is_dir($themeDir)) {
        // Simple recursive directory removal
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($themeDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        
        return rmdir($themeDir);
    }
    
    return false;
}

function getThemeAssetUrl($asset, $themeName = null) {
    $theme = $themeName ?: getActiveTheme();
    return '/themes/' . $theme . '/' . $asset;
}

function uploadFile($file, $allowed_types = null) {
    if (!$allowed_types) {
        $allowed_types = ALLOWED_UPLOAD_TYPES;
    }
    
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/assets/uploads/';
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file
    if (!in_array($file_extension, $allowed_types)) {
        throw new Exception('File type not allowed');
    }
    
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        throw new Exception('File too large');
    }
    
    // Generate unique filename
    $filename = uniqid() . '.' . $file_extension;
    $filepath = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Upload failed');
    }
    
    return '/assets/uploads/' . $filename;
}
?>

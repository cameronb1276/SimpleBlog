<?php
// Set page variables for the single post
$page_title = $post['title'];
$page_description = $post['excerpt'] ?: substr(strip_tags($post['content']), 0, 160);

// Load header with variables  
loadThemeTemplate('header', compact('settings', 'page_title', 'page_description'));
?>

<article class="single-post">
    <header class="post-header">
        <div class="post-breadcrumb">
            <a href="/" class="breadcrumb-link">Home</a>
            <span class="breadcrumb-separator">/</span>
            <span class="breadcrumb-current">Post</span>
        </div>
        
        <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
        
        <div class="post-meta">
            <div class="meta-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12,6 12,12 16,14"/>
                </svg>
                <time datetime="<?php echo date('c', strtotime($post['created_at'])); ?>">
                    <?php echo date('F j, Y \a\t g:i A', strtotime($post['created_at'])); ?>
                </time>
            </div>
            
            <?php if ($post['updated_at'] && $post['updated_at'] !== $post['created_at']): ?>
                <div class="meta-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                        <path d="M21 3v5h-5"/>
                        <path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                        <path d="M3 21v-5h5"/>
                    </svg>
                    <span>Updated <?php echo date('M j, Y', strtotime($post['updated_at'])); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="meta-item">
                <span class="post-status status-<?php echo $post['status']; ?>">
                    <?php echo ucfirst($post['status']); ?>
                </span>
            </div>
        </div>
        
        <?php if ($post['excerpt']): ?>
            <div class="post-excerpt">
                <p><?php echo htmlspecialchars($post['excerpt']); ?></p>
            </div>
        <?php endif; ?>
    </header>
    
    <?php if ($post['featured_image']): ?>
        <div class="post-featured-image">
            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                 alt="<?php echo htmlspecialchars($post['title']); ?>"
                 loading="lazy">
        </div>
    <?php endif; ?>
    
    <div class="post-content">
        <?php echo nl2br(htmlspecialchars($post['content'])); ?>
    </div>
    
    <footer class="post-footer">
        <div class="post-navigation">
            <a href="/" class="nav-button nav-back">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
                Back to Posts
            </a>
            
            <div class="post-share">
                <span class="share-label">Share this post:</span>
                <div class="share-buttons">
                    <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($post['title']); ?>&url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . '/post/' . $post['slug']); ?>" 
                       target="_blank" class="share-button twitter" aria-label="Share on Twitter">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"/>
                        </svg>
                    </a>
                    
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . '/post/' . $post['slug']); ?>" 
                       target="_blank" class="share-button facebook" aria-label="Share on Facebook">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>
                        </svg>
                    </a>
                    
                    <button class="share-button copy-link" onclick="copyToClipboard('<?php echo 'http://' . $_SERVER['HTTP_HOST'] . '/post/' . $post['slug']; ?>')" aria-label="Copy link">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </footer>
</article>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show feedback
        const button = event.target.closest('.copy-link');
        const originalContent = button.innerHTML;
        button.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20,6 9,17 4,12"/></svg>';
        button.style.color = '#10b981';
        
        setTimeout(() => {
            button.innerHTML = originalContent;
            button.style.color = '';
        }, 2000);
    });
}
</script>

<?php
// Load footer with variables
loadThemeTemplate('footer', compact('settings'));
?>
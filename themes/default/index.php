<?php
// Load header with variables
loadThemeTemplate('header', compact('settings', 'page_title', 'page_description'));
?>

<div class="blog-header">
    <h2 class="page-title">Latest Posts</h2>
    <p class="page-description">Discover our latest articles and insights</p>
</div>

<div class="posts-section">
    <?php if (!empty($posts)): ?>
        <div class="posts-grid">
            <?php foreach ($posts as $post): ?>
                <article class="post-card">
                    <?php if ($post['featured_image']): ?>
                        <div class="post-image">
                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" 
                                 alt="<?php echo htmlspecialchars($post['title']); ?>"
                                 loading="lazy">
                        </div>
                    <?php endif; ?>
                    
                    <div class="post-content">
                        <div class="post-meta">
                            <time class="post-date" datetime="<?php echo date('c', strtotime($post['created_at'])); ?>">
                                <?php echo date('M j, Y', strtotime($post['created_at'])); ?>
                            </time>
                            <span class="post-status status-<?php echo $post['status']; ?>">
                                <?php echo ucfirst($post['status']); ?>
                            </span>
                        </div>
                        
                        <h3 class="post-title">
                            <a href="/post/<?php echo htmlspecialchars($post['slug']); ?>">
                                <?php echo htmlspecialchars($post['title']); ?>
                            </a>
                        </h3>
                        
                        <div class="post-excerpt">
                            <?php if ($post['excerpt']): ?>
                                <p><?php echo htmlspecialchars($post['excerpt']); ?></p>
                            <?php else: ?>
                                <p><?php echo htmlspecialchars(substr(strip_tags($post['content']), 0, 150)); ?>...</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="post-footer">
                            <a href="/post/<?php echo htmlspecialchars($post['slug']); ?>" class="read-more">
                                Read More
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M5 12h14M12 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="pagination" aria-label="Posts pagination">
                <div class="pagination-info">
                    Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                </div>
                
                <div class="pagination-links">
                    <?php if ($page > 1): ?>
                        <a href="/?page=<?php echo ($page - 1); ?>" class="pagination-link prev">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 12H5M12 19l-7-7 7-7"/>
                            </svg>
                            Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="pagination-link current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="/?page=<?php echo $i; ?>" class="pagination-link"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="/?page=<?php echo ($page + 1); ?>" class="pagination-link next">
                            Next
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </a>
                    <?php endif; ?>
                </div>
            </nav>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="no-posts">
            <div class="no-posts-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/>
                    <line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10,9 9,9 8,9"/>
                </svg>
            </div>
            <h3>No Posts Yet</h3>
            <p>There are no published posts to display. Check back soon for new content!</p>
            <a href="/admin/" class="cta-button">Create Your First Post</a>
        </div>
    <?php endif; ?>
</div>

<?php
// Load footer with variables
loadThemeTemplate('footer', compact('settings'));
?>
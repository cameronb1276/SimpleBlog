// Admin panel JavaScript functions

// Confirm delete post
function confirmDelete() {
    return confirm('Are you sure you want to delete this post?');
}

// Initialize admin functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to delete forms
    const deleteForms = document.querySelectorAll('.delete-form');
    deleteForms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            if (!confirmDelete()) {
                e.preventDefault();
            }
        });
    });
});
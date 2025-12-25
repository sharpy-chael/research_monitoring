/**
 * Notifications JavaScript
 * Handles marking notifications as read
 */

async function markAsRead(notificationId) {
    try {
        const response = await fetch('mark_notification_read.php', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json' 
            },
            body: JSON.stringify({ 
                notification_id: notificationId 
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Option 1: Reload the page to show updated status
            location.reload();
            
            // Option 2: Update UI without reload (uncomment if you prefer this)
            /*
            const card = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (card) {
                card.classList.remove('unread');
                card.classList.add('read');
                const badge = card.querySelector('.new-badge');
                if (badge) badge.remove();
                const btn = card.querySelector('.mark-read-btn');
                if (btn) btn.remove();
                
                // Update unread count
                const countBadge = document.querySelector('.notification-badge');
                if (countBadge) {
                    let count = parseInt(countBadge.textContent) - 1;
                    if (count > 0) {
                        countBadge.textContent = count;
                    } else {
                        countBadge.remove();
                    }
                }
            }
            */
        } else {
            alert(data.message || 'Failed to mark as read');
        }
    } catch (error) {
        console.error('Error marking notification as read:', error);
        alert('An error occurred. Please try again.');
    }
}

// Optional: Auto-refresh notifications every 2 minutes
// Uncomment if you want auto-refresh functionality
/*
setInterval(() => {
    // Only refresh if there are unread notifications
    const unreadCount = document.querySelector('.notification-badge');
    if (unreadCount) {
        location.reload();
    }
}, 120000); // 2 minutes
*/
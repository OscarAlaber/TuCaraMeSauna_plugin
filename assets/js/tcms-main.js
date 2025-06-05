/**
 * TCMS Messaging System Main JavaScript
 *
 * @package TCMS_Messaging_System
 */

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    TCMS.init();
});

// Main TCMS object
const TCMS = {
    /**
     * Initialize all components
     */
    init: function() {
        // Initialize tooltips
        this.initTooltips();
        
        // Initialize modals
        this.initModals();
        
        // Initialize tabs
        this.initTabs();
        
        // Initialize accordions
        this.initAccordions();
        
        // Initialize form validation
        this.initFormValidation();
        
        // Initialize notifications
        this.initNotifications();
        
        // Check for new messages
        this.checkForNewMessages();
        
        // Track user activity
        this.trackUserActivity();
        
        console.log('TCMS initialized');
    },
    
    /**
     * Initialize tooltips
     */
    initTooltips: function() {
        // Already handled via CSS
    },
    
    /**
     * Initialize modals
     */
    initModals: function() {
        // Open modal
        document.querySelectorAll('[data-modal-target]').forEach(trigger => {
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                const modalId = this.getAttribute('data-modal-target');
                const modal = document.getElementById(modalId);
                
                if (modal) {
                    modal.classList.add('tcms-fade-in');
                    modal.style.display = 'flex';
                    
                    // Prevent scrolling on body
                    document.body.style.overflow = 'hidden';
                }
            });
        });
        
        // Close modal
        document.querySelectorAll('.tcms-modal-close, [data-modal-close]').forEach(closer => {
            closer.addEventListener('click', function(e) {
                e.preventDefault();
                const modal = this.closest('.tcms-modal');
                
                if (modal) {
                    modal.style.display = 'none';
                    
                    // Restore scrolling on body
                    document.body.style.overflow = '';
                }
            });
        });
        
        // Close modal when clicking on overlay
        document.querySelectorAll('.tcms-modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                    
                    // Restore scrolling on body
                    document.body.style.overflow = '';
                }
            });
        });
    },
    
    /**
     * Initialize tabs
     */
    initTabs: function() {
        document.querySelectorAll('.tcms-tabs').forEach(tabGroup => {
            const tabs = tabGroup.querySelectorAll('.tcms-tab');
            const tabContents = document.querySelectorAll('.tcms-tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Get target tab
                    const target = this.getAttribute('data-tab-target');
                    
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to clicked tab and corresponding content
                    this.classList.add('active');
                    document.getElementById(target).classList.add('active');
                });
            });
        });
    },
    
    /**
     * Initialize accordions
     */
    initAccordions: function() {
        document.querySelectorAll('.tcms-accordion-header').forEach(header => {
            header.addEventListener('click', function() {
                const accordionItem = this.parentElement;
                
                // Toggle active class
                accordionItem.classList.toggle('active');
                
                // Toggle content visibility
                const content = this.nextElementSibling;
                if (accordionItem.classList.contains('active')) {
                    content.style.maxHeight = content.scrollHeight + 'px';
                } else {
                    content.style.maxHeight = 0;
                }
            });
        });
    },
    
    /**
     * Initialize form validation
     */
    initFormValidation: function() {
        document.querySelectorAll('.tcms-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                let valid = true;
                
                // Check required fields
                const requiredFields = this.querySelectorAll('[required]');
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        valid = false;
                        field.classList.add('tcms-error');
                        
                        // Add error message if not exists
                        if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('tcms-error-message')) {
                            const errorMessage = document.createElement('div');
                            errorMessage.classList.add('tcms-error-message');
                            errorMessage.textContent = 'This field is required';
                            field.parentNode.insertBefore(errorMessage, field.nextSibling);
                        }
                    } else {
                        field.classList.remove('tcms-error');
                        
                        // Remove error message if exists
                        if (field.nextElementSibling && field.nextElementSibling.classList.contains('tcms-error-message')) {
                            field.nextElementSibling.remove();
                        }
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                }
            });
        });
    },
    
    /**
     * Initialize notifications
     */
    initNotifications: function() {
        // Nothing to do here for now
    },
    
    /**
     * Show notification
     */
    showNotification: function(title, message, type = 'info') {
        const notificationContainer = document.getElementById('tcms-notification-container');
        
        // Create container if doesn't exist
        if (!notificationContainer) {
            const container = document.createElement('div');
            container.id = 'tcms-notification-container';
            container.style.position = 'fixed';
            container.style.bottom = '20px';
            container.style.right = '20px';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = 'tcms-notification';
        
        // Set icon based on type
        let icon = '';
        switch (type) {
            case 'success':
                icon = '✅';
                break;
            case 'error':
                icon = '❌';
                break;
            case 'warning':
                icon = '⚠️';
                break;
            default:
                icon = 'ℹ️';
        }
        
        // Create notification content
        notification.innerHTML = `
            <div class="tcms-notification-content">
                <div class="tcms-notification-icon">${icon}</div>
                <div class="tcms-notification-text">
                    <div class="tcms-notification-title">${title}</div>
                    <div class="tcms-notification-message">${message}</div>
                </div>
            </div>
        `;
        
        // Add to container
        document.getElementById('tcms-notification-container').appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
    },
    
    /**
     * Check for new messages
     */
    checkForNewMessages: function() {
        // Only check if user is logged in
        if (typeof tcms_ajax !== 'undefined' && tcms_ajax.is_logged_in === 'true') {
            // Check every 60 seconds
            setInterval(() => {
                this.fetchUnreadMessages();
            }, 60000);
            
            // Initial check
            this.fetchUnreadMessages();
        }
    },
    
    /**
     * Fetch unread messages
     */
    fetchUnreadMessages: function() {
        fetch(tcms_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tcms_get_conversations',
                nonce: tcms_ajax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let unreadCount = 0;
                
                // Count unread messages
                data.data.conversations.forEach(conversation => {
                    if (conversation.unread_count) {
                        unreadCount += parseInt(conversation.unread_count);
                    }
                });
                
                // Update unread badge
                if (unreadCount > 0) {
                    // Find or create unread badge
                    let unreadBadge = document.getElementById('tcms-unread-badge');
                    
                    if (!unreadBadge) {
                        // Create badge element
                        unreadBadge = document.createElement('span');
                        unreadBadge.id = 'tcms-unread-badge';
                        unreadBadge.className = 'tcms-unread-badge';
                        
                        // Add to menu
                        const messagesMenu = document.querySelector('.menu-item a[href*="messages"]');
                        if (messagesMenu) {
                            messagesMenu.style.position = 'relative';
                            messagesMenu.appendChild(unreadBadge);
                        }
                    }
                    
                    unreadBadge.textContent = unreadCount;
                    unreadBadge.style.display = 'inline-flex';
                    
                    // Show notification for new messages
                    const currentCount = parseInt(unreadBadge.getAttribute('data-count') || '0');
                    if (unreadCount > currentCount) {
                        this.showNotification(
                            'New Messages',
                            `You have ${unreadCount} unread message${unreadCount > 1 ? 's' : ''}.`,
                            'info'
                        );
                    }
                    
                    // Update count attribute
                    unreadBadge.setAttribute('data-count', unreadCount);
                } else {
                    // Hide badge if no unread messages
                    const unreadBadge = document.getElementById('tcms-unread-badge');
                    if (unreadBadge) {
                        unreadBadge.style.display = 'none';
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error checking messages:', error);
        });
    },
    
    /**
     * Track user activity
     */
    trackUserActivity: function() {
        // Only track if user is logged in
        if (typeof tcms_ajax !== 'undefined' && tcms_ajax.is_logged_in === 'true') {
            // Update activity on page load
            this.updateActivity();
            
            // Update every 5 minutes
            setInterval(() => {
                this.updateActivity();
            }, 5 * 60 * 1000);
            
            // Update on user interaction
            const events = ['mousedown', 'keydown', 'scroll', 'touchstart'];
            
            let activityTimeout;
            
            events.forEach(event => {
                document.addEventListener(event, () => {
                    clearTimeout(activityTimeout);
                    
                    // Debounce to avoid too many requests
                    activityTimeout = setTimeout(() => {
                        this.updateActivity();
                    }, 60000);
                });
            });
        }
    },
    
    /**
     * Update user activity
     */
    updateActivity: function() {
        // Make a request to update last activity
        fetch(tcms_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tcms_update_activity',
                nonce: tcms_ajax.nonce
            })
        })
        .catch(error => {
            console.error('Error updating activity:', error);
        });
    }
};

/**
 * Helper functions
 */

/**
 * Format distance
 */
function tcms_format_distance(distance) {
    if (distance < 1) {
        return Math.round(distance * 1000) + ' m';
    } else if (distance < 10) {
        return distance.toFixed(1) + ' km';
    } else {
        return Math.round(distance) + ' km';
    }
}

/**
 * Format date/time
 */
function tcms_format_datetime(datetime, format = 'relative') {
    const date = new Date(datetime);
    
    if (format === 'relative') {
        const now = new Date();
        const diffSeconds = Math.floor((now - date) / 1000);
        
        if (diffSeconds < 60) {
            return 'just now';
        }
        
        const diffMinutes = Math.floor(diffSeconds / 60);
        if (diffMinutes < 60) {
            return diffMinutes + ' minute' + (diffMinutes > 1 ? 's' : '') + ' ago';
        }
        
        const diffHours = Math.floor(diffMinutes / 60);
        if (diffHours < 24) {
            return diffHours + ' hour' + (diffHours > 1 ? 's' : '') + ' ago';
        }
        
        const diffDays = Math.floor(diffHours / 24);
        if (diffDays < 7) {
            return diffDays + ' day' + (diffDays > 1 ? 's' : '') + ' ago';
        }
        
        const diffWeeks = Math.floor(diffDays / 7);
        if (diffWeeks < 4) {
            return diffWeeks + ' week' + (diffWeeks > 1 ? 's' : '') + ' ago';
        }
        
        const diffMonths = Math.floor(diffDays / 30);
        if (diffMonths < 12) {
            return diffMonths + ' month' + (diffMonths > 1 ? 's' : '') + ' ago';
        }
        
        const diffYears = Math.floor(diffDays / 365);
        return diffYears + ' year' + (diffYears > 1 ? 's' : '') + ' ago';
    }
    
    return date.toLocaleString();
}

/**
 * Format user status
 */
function tcms_format_status(lastActive) {
    if (!lastActive) {
        return 'offline';
    }
    
    const lastActiveDate = new Date(lastActive);
    const now = new Date();
    const diffMinutes = Math.floor((now - lastActiveDate) / (1000 * 60));
    
    if (diffMinutes < 15) {
        return 'online';
    } else if (diffMinutes < 30) {
        return 'away';
    } else {
        return 'offline';
    }
}

/**
 * Truncate text
 */
function tcms_truncate(text, length = 100) {
    if (!text || text.length <= length) {
        return text;
    }
    
    return text.substring(0, length) + '...';
}

/**
 * Escape HTML
 */
function tcms_escape_html(html) {
    const div = document.createElement('div');
    div.textContent = html;
    return div.innerHTML;
}
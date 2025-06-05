<?php
/**
 * Template for Users Nearby page
 *
 * @package TCMS_Messaging_System
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$settings = tcms_get_settings();
$max_distance = isset($settings['max_distance']) ? intval($settings['max_distance']) : 50;
$is_premium = tcms_is_user_premium($user_id);
?>

<div class="tcms-container tcms-users-nearby">
    <h1 class="tcms-page-title"><?php _e('Users Nearby', 'tcms-messaging'); ?></h1>

    <?php if (!$is_premium): ?>
    <div class="tcms-premium-promotion">
        <div class="tcms-premium-icon">👑</div>
        <div class="tcms-premium-content">
            <h3><?php _e('Upgrade to Premium', 'tcms-messaging'); ?></h3>
            <p><?php _e('Get advanced filters, see who liked you, unlimited messages and more!', 'tcms-messaging'); ?></p>
            <a href="<?php echo esc_url(get_permalink(get_page_by_path('premium'))); ?>" class="tcms-btn tcms-btn-premium">
                <?php _e('Get Premium', 'tcms-messaging'); ?>
            </a>
        </div>
        <button class="tcms-premium-close">&times;</button>
    </div>
    <?php endif; ?>

    <div class="tcms-filters">
        <div class="tcms-filters-header">
            <h3><?php _e('Search Filters', 'tcms-messaging'); ?></h3>
            <button class="tcms-toggle-filters">
                <span class="tcms-toggle-icon">+</span>
                <span class="tcms-toggle-text"><?php _e('Show Filters', 'tcms-messaging'); ?></span>
            </button>
        </div>

        <div class="tcms-filters-content" style="display:none;">
            <form id="tcms-filter-form" class="tcms-filter-form">
                <div class="tcms-filter-row">
                    <div class="tcms-filter-group">
                        <label for="tcms-distance"><?php _e('Maximum Distance', 'tcms-messaging'); ?></label>
                        <div class="tcms-range-slider">
                            <input type="range" id="tcms-distance" name="distance" min="1" max="<?php echo esc_attr($max_distance); ?>" value="50" class="tcms-range-input">
                            <span class="tcms-range-value">50 km</span>
                        </div>
                    </div>

                    <div class="tcms-filter-group">
                        <label for="tcms-activity"><?php _e('Last Active', 'tcms-messaging'); ?></label>
                        <select id="tcms-activity" name="last_active" class="tcms-select">
                            <option value=""><?php _e('Any time', 'tcms-messaging'); ?></option>
                            <option value="1"><?php _e('Last hour', 'tcms-messaging'); ?></option>
                            <option value="24"><?php _e('Last 24 hours', 'tcms-messaging'); ?></option>
                            <option value="168"><?php _e('Last week', 'tcms-messaging'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="tcms-filter-row">
                    <div class="tcms-filter-group">
                        <label for="tcms-role"><?php _e('Role', 'tcms-messaging'); ?></label>
                        <select id="tcms-role" name="role" class="tcms-select">
                            <option value=""><?php _e('Any', 'tcms-messaging'); ?></option>
                            <option value="top"><?php _e('Top', 'tcms-messaging'); ?></option>
                            <option value="bottom"><?php _e('Bottom', 'tcms-messaging'); ?></option>
                            <option value="versatile"><?php _e('Versatile', 'tcms-messaging'); ?></option>
                        </select>
                    </div>

                    <div class="tcms-filter-group">
                        <div class="tcms-checkbox-group">
                            <label class="tcms-checkbox-label">
                                <input type="checkbox" name="verified" value="1" class="tcms-checkbox">
                                <span class="tcms-checkbox-text"><?php _e('Verified Profiles Only', 'tcms-messaging'); ?></span>
                            </label>
                            
                            <?php if ($is_premium): ?>
                            <label class="tcms-checkbox-label">
                                <input type="checkbox" name="with_photo" value="1" class="tcms-checkbox">
                                <span class="tcms-checkbox-text"><?php _e('With Photo Only', 'tcms-messaging'); ?></span>
                            </label>
                            <?php else: ?>
                            <label class="tcms-checkbox-label tcms-premium-feature">
                                <input type="checkbox" disabled class="tcms-checkbox">
                                <span class="tcms-checkbox-text"><?php _e('With Photo Only', 'tcms-messaging'); ?></span>
                                <span class="tcms-premium-label">👑</span>
                            </label>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($is_premium): ?>
                <div class="tcms-filter-row tcms-advanced-filters">
                    <div class="tcms-filter-group">
                        <label><?php _e('Advanced Filters', 'tcms-messaging'); ?></label>
                        <div class="tcms-checkbox-grid">
                            <label class="tcms-checkbox-label">
                                <input type="checkbox" name="online_now" value="1" class="tcms-checkbox">
                                <span class="tcms-checkbox-text"><?php _e('Online Now', 'tcms-messaging'); ?></span>
                            </label>
                            <label class="tcms-checkbox-label">
                                <input type="checkbox" name="liked_me" value="1" class="tcms-checkbox">
                                <span class="tcms-checkbox-text"><?php _e('Liked Me', 'tcms-messaging'); ?></span>
                            </label>
                            <label class="tcms-checkbox-label">
                                <input type="checkbox" name="new_users" value="1" class="tcms-checkbox">
                                <span class="tcms-checkbox-text"><?php _e('New Users', 'tcms-messaging'); ?></span>
                            </label>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="tcms-filter-actions">
                    <button type="button" class="tcms-btn tcms-btn-secondary tcms-reset-filters">
                        <?php _e('Reset', 'tcms-messaging'); ?>
                    </button>
                    <button type="submit" class="tcms-btn tcms-btn-primary tcms-apply-filters">
                        <?php _e('Apply Filters', 'tcms-messaging'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="tcms-location-status">
        <div class="tcms-location-indicator">
            <span class="tcms-location-icon">📍</span>
            <span class="tcms-current-location"><?php _e('Detecting location...', 'tcms-messaging'); ?></span>
        </div>
        <button class="tcms-btn tcms-btn-small tcms-update-location">
            <?php _e('Update Location', 'tcms-messaging'); ?>
        </button>
    </div>

    <div class="tcms-users-grid" id="tcms-users-grid">
        <div class="tcms-loading">
            <div class="tcms-loading-spinner"></div>
            <p><?php _e('Searching for users nearby...', 'tcms-messaging'); ?></p>
        </div>
    </div>

    <div class="tcms-load-more-container" style="display:none;">
        <button class="tcms-btn tcms-btn-secondary tcms-load-more">
            <?php _e('Load More', 'tcms-messaging'); ?>
        </button>
    </div>

    <div class="tcms-no-results" style="display:none;">
        <div class="tcms-no-results-icon">🔍</div>
        <h3><?php _e('No Users Found', 'tcms-messaging'); ?></h3>
        <p><?php _e('Try adjusting your filters or increasing the search distance.', 'tcms-messaging'); ?></p>
    </div>

    <!-- User Card Template -->
    <template id="tcms-user-card-template">
        <div class="tcms-user-card" data-user-id="{{user_id}}">
            <div class="tcms-user-photo">
                <img src="{{photo_url}}" alt="{{display_name}}" class="tcms-user-avatar">
                <div class="tcms-user-badges">
                    {{#is_online}}
                    <span class="tcms-badge tcms-badge-online"><?php _e('Online', 'tcms-messaging'); ?></span>
                    {{/is_online}}
                    {{#is_verified}}
                    <span class="tcms-badge tcms-badge-verified"><?php _e('Verified', 'tcms-messaging'); ?></span>
                    {{/is_verified}}
                </div>
                <div class="tcms-distance-badge">{{distance}}</div>
            </div>
            <div class="tcms-user-info">
                <div class="tcms-user-name-row">
                    <h3 class="tcms-user-name">{{display_name}}</h3>
                    {{#is_premium}}
                    <span class="tcms-premium-icon">👑</span>
                    {{/is_premium}}
                </div>
                <div class="tcms-user-meta">
                    <span class="tcms-user-location">{{city}}</span>
                    <span class="tcms-user-role">{{role}}</span>
                </div>
                <div class="tcms-user-actions">
                    <a href="{{profile_url}}" class="tcms-btn tcms-btn-outline tcms-view-profile">
                        <?php _e('View Profile', 'tcms-messaging'); ?>
                    </a>
                    <button class="tcms-btn tcms-btn-icon tcms-send-message" data-user-id="{{user_id}}">
                        💬
                    </button>
                    <button class="tcms-btn tcms-btn-icon tcms-like-user" data-user-id="{{user_id}}">
                        ❤️
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
jQuery(document).ready(function($) {
    // Variables
    let currentPage = 1;
    let isLoading = false;
    let hasMoreUsers = true;
    let userLocation = null;
    
    // Get user location
    function getUserLocation() {
        $('.tcms-current-location').text('<?php _e('Detecting location...', 'tcms-messaging'); ?>');
        
        // First check if we have a saved location
        $.ajax({
            url: tcms_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tcms_get_location',
                nonce: tcms_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.location) {
                    userLocation = response.data.location;
                    $('.tcms-current-location').text(userLocation.city || '<?php _e('Location found', 'tcms-messaging'); ?>');
                    loadUsers();
                } else {
                    // If no saved location, try to get current location
                    getCurrentPosition();
                }
            },
            error: function() {
                getCurrentPosition();
            }
        });
    }
    
    // Get current position from browser
    function getCurrentPosition() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    // Success
                    const latitude = position.coords.latitude;
                    const longitude = position.coords.longitude;
                    
                    // Reverse geocode to get city name
                    reverseGeocode(latitude, longitude);
                    
                    userLocation = {
                        latitude: latitude,
                        longitude: longitude
                    };
                    
                    loadUsers();
                },
                function(error) {
                    // Error
                    console.error('Geolocation error:', error);
                    $('.tcms-current-location').text('<?php _e('Location error', 'tcms-messaging'); ?>');
                    
                    // Try to load users anyway with default location
                    userLocation = null;
                    loadUsers();
                }
            );
        } else {
            $('.tcms-current-location').text('<?php _e('Geolocation not supported', 'tcms-messaging'); ?>');
            
            // Try to load users anyway with default location
            userLocation = null;
            loadUsers();
        }
    }
    
    // Reverse geocode coordinates to get location name
    function reverseGeocode(latitude, longitude) {
        $.ajax({
            url: 'https://nominatim.openstreetmap.org/reverse',
            type: 'GET',
            data: {
                format: 'json',
                lat: latitude,
                lon: longitude,
                addressdetails: 1
            },
            success: function(data) {
                let city = '';
                
                if (data.address) {
                    city = data.address.city || data.address.town || data.address.village || '';
                }
                
                if (city) {
                    $('.tcms-current-location').text(city);
                    
                    // Update user location with city
                    if (userLocation) {
                        userLocation.city = city;
                        userLocation.country = data.address.country || '';
                        
                        // Save location
                        saveUserLocation();
                    }
                }
            }
        });
    }
    
    // Save user location
    function saveUserLocation() {
        if (!userLocation) return;
        
        $.ajax({
            url: tcms_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tcms_update_location',
                nonce: tcms_ajax.nonce,
                latitude: userLocation.latitude,
                longitude: userLocation.longitude,
                city: userLocation.city || '',
                country: userLocation.country || '',
                privacy_level: 2 // Default to members only
            },
            success: function(response) {
                console.log('Location saved');
            }
        });
    }
    
    // Load users
    function loadUsers(append = false) {
        if (isLoading) return;
        
        isLoading = true;
        
        if (!append) {
            $('#tcms-users-grid').html('<div class="tcms-loading"><div class="tcms-loading-spinner"></div><p><?php _e('Searching for users nearby...', 'tcms-messaging'); ?></p></div>');
            currentPage = 1;
        }
        
        // Get filter values
        const filters = getFilters();
        
        // Calculate offset
        const limit = 20;
        const offset = (currentPage - 1) * limit;
        
        $.ajax({
            url: tcms_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tcms_get_nearby_users',
                nonce: tcms_ajax.nonce,
                latitude: userLocation ? userLocation.latitude : 0,
                longitude: userLocation ? userLocation.longitude : 0,
                distance: filters.distance,
                limit: limit,
                offset: offset,
                filters: filters
            },
            success: function(response) {
                isLoading = false;
                
                if (response.success) {
                    // Hide loading
                    $('#tcms-users-grid .tcms-loading').remove();
                    
                    if (response.data.users && response.data.users.length > 0) {
                        renderUsers(response.data.users, append);
                        
                        // Show load more button if we got a full page of results
                        if (response.data.users.length === limit) {
                            $('.tcms-load-more-container').show();
                            hasMoreUsers = true;
                        } else {
                            $('.tcms-load-more-container').hide();
                            hasMoreUsers = false;
                        }
                        
                        // Hide no results message
                        $('.tcms-no-results').hide();
                    } else {
                        if (!append) {
                            // Show no results message
                            $('#tcms-users-grid').empty();
                            $('.tcms-no-results').show();
                            $('.tcms-load-more-container').hide();
                        } else {
                            // No more users to load
                            $('.tcms-load-more-container').hide();
                        }
                        
                        hasMoreUsers = false;
                    }
                } else {
                    console.error('Error loading users:', response.data.message);
                    $('#tcms-users-grid').html('<div class="tcms-error">' + response.data.message + '</div>');
                }
            },
            error: function() {
                isLoading = false;
                $('#tcms-users-grid').html('<div class="tcms-error"><?php _e('Error loading users. Please try again.', 'tcms-messaging'); ?></div>');
            }
        });
    }
    
    // Render users
    function renderUsers(users, append) {
        const container = $('#tcms-users-grid');
        const template = $('#tcms-user-card-template').html();
        
        if (!append) {
            container.empty();
        }
        
        users.forEach(function(user) {
            // Format data for template
            const userData = {
                user_id: user.user_id,
                display_name: user.display_name,
                photo_url: user.photo_url || '<?php echo TCMS_PLUGIN_URL; ?>assets/images/default-avatar.png',
                is_online: tcms_get_user_status(user.last_active) === 'online',
                is_verified: user.is_verified == 1,
                is_premium: user.is_premium == 1,
                distance: tcms_format_distance(user.distance),
                city: user.city || '',
                role: tcms_format_role(user.role),
                profile_url: '<?php echo esc_url(get_permalink(get_page_by_path('user-profile'))); ?>?user_id=' + user.user_id
            };
            
            // Replace template variables
            let html = template;
            
            for (const key in userData) {
                if (typeof userData[key] === 'boolean') {
                    // Handle boolean conditionals with {{#var}}content{{/var}}
                    const regex = new RegExp('{{#' + key + '}}([\\s\\S]*?){{/' + key + '}}', 'g');
                    html = userData[key] 
                        ? html.replace(regex, '$1') 
                        : html.replace(regex, '');
                } else {
                    // Replace normal variables
                    html = html.replace(new RegExp('{{' + key + '}}', 'g'), userData[key]);
                }
            }
            
            container.append(html);
        });
    }
    
    // Get filter values
    function getFilters() {
        return {
            distance: parseInt($('#tcms-distance').val()) || 50,
            last_active: $('#tcms-activity').val(),
            role: $('#tcms-role').val(),
            verified: $('input[name="verified"]').is(':checked') ? 1 : 0,
            with_photo: $('input[name="with_photo"]').is(':checked') ? 1 : 0,
            online_now: $('input[name="online_now"]').is(':checked') ? 1 : 0,
            liked_me: $('input[name="liked_me"]').is(':checked') ? 1 : 0,
            new_users: $('input[name="new_users"]').is(':checked') ? 1 : 0
        };
    }
    
    // Format user status based on last_active
    function tcms_get_user_status(last_active) {
        if (!last_active) return 'offline';
        
        const lastActiveDate = new Date(last_active);
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
    
    // Format role
    function tcms_format_role(role) {
        switch (role) {
            case 'top': return '<?php _e('Top', 'tcms-messaging'); ?>';
            case 'bottom': return '<?php _e('Bottom', 'tcms-messaging'); ?>';
            case 'versatile': return '<?php _e('Versatile', 'tcms-messaging'); ?>';
            default: return '';
        }
    }
    
    // Format distance
    function tcms_format_distance(distance) {
        if (distance < 1) {
            return Math.round(distance * 1000) + ' m';
        } else if (distance < 10) {
            return distance.toFixed(1) + ' km';
        } else {
            return Math.round(distance) + ' km';
        }
    }
    
    // Update distance display on slider change
    $('#tcms-distance').on('input', function() {
        $('.tcms-range-value').text($(this).val() + ' km');
    });
    
    // Toggle filters
    $('.tcms-toggle-filters').on('click', function() {
        const filtersContent = $('.tcms-filters-content');
        const toggleText = $('.tcms-toggle-text');
        const toggleIcon = $('.tcms-toggle-icon');
        
        if (filtersContent.is(':visible')) {
            filtersContent.slideUp();
            toggleText.text('<?php _e('Show Filters', 'tcms-messaging'); ?>');
            toggleIcon.text('+');
        } else {
            filtersContent.slideDown();
            toggleText.text('<?php _e('Hide Filters', 'tcms-messaging'); ?>');
            toggleIcon.text('-');
        }
    });
    
    // Apply filters
    $('#tcms-filter-form').on('submit', function(e) {
        e.preventDefault();
        loadUsers();
    });
    
    // Reset filters
    $('.tcms-reset-filters').on('click', function() {
        $('#tcms-filter-form')[0].reset();
        $('#tcms-distance').val(50);
        $('.tcms-range-value').text('50 km');
    });
    
    // Load more users
    $('.tcms-load-more').on('click', function() {
        if (hasMoreUsers && !isLoading) {
            currentPage++;
            loadUsers(true);
        }
    });
    
    // Update location
    $('.tcms-update-location').on('click', function() {
        getCurrentPosition();
    });
    
    // Send message
    $(document).on('click', '.tcms-send-message', function() {
        const userId = $(this).data('user-id');
        window.location.href = '<?php echo esc_url(get_permalink(get_page_by_path('messages'))); ?>?conversation_with=' + userId;
    });
    
    // Like user
    $(document).on('click', '.tcms-like-user', function() {
        const userId = $(this).data('user-id');
        const button = $(this);
        
        // Add liked class to show it's been clicked
        button.addClass('tcms-liked');
        
        // We'll implement the actual like functionality in future versions
        // For now just show a message
        alert('<?php _e('User liked!', 'tcms-messaging'); ?>');
    });
    
    // Close premium promotion
    $('.tcms-premium-close').on('click', function() {
        $('.tcms-premium-promotion').fadeOut();
    });
    
    // Initialize
    getUserLocation();
});
</script>
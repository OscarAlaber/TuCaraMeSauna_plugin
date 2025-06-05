<?php
/**
 * Admin Dashboard Template
 *
 * @package TCMS_Messaging_System
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get stats
$stats = isset($stats) ? $stats : array();

// Default values
$stats = array_merge(
    array(
        'total_users' => 0,
        'active_users' => 0,
        'premium_users' => 0,
        'messages_month' => 0,
        'active_saunas' => 0,
        'pending_reports' => 0,
        'new_users' => 0,
        'online_users' => 0
    ),
    $stats
);
?>

<div class="wrap tcms-admin-dashboard">
    <h1><?php _e('TCMS Messaging System Dashboard', 'tcms-messaging'); ?></h1>
    
    <div class="tcms-admin-header">
        <div class="tcms-admin-info">
            <div class="tcms-version">
                <?php _e('Version:', 'tcms-messaging'); ?> <span><?php echo TCMS_VERSION; ?></span>
            </div>
            <div class="tcms-documentation">
                <a href="https://tucaramesauna.com/docs" target="_blank"><?php _e('Documentation', 'tcms-messaging'); ?></a>
            </div>
        </div>
        
        <div class="tcms-admin-actions">
            <a href="<?php echo admin_url('admin.php?page=tcms-settings'); ?>" class="button button-primary">
                <?php _e('Settings', 'tcms-messaging'); ?>
            </a>
        </div>
    </div>
    
    <div class="tcms-admin-section tcms-admin-stats">
        <div class="tcms-section-header">
            <h2><?php _e('System Overview', 'tcms-messaging'); ?></h2>
            <button class="tcms-refresh-stats button" data-section="stats">
                <?php _e('Refresh', 'tcms-messaging'); ?>
            </button>
        </div>
        
        <div class="tcms-stats-grid">
            <div class="tcms-stat-card">
                <div class="tcms-stat-icon dashicons dashicons-admin-users"></div>
                <div class="tcms-stat-content">
                    <div class="tcms-stat-value"><?php echo number_format($stats['total_users']); ?></div>
                    <div class="tcms-stat-label"><?php _e('Total Users', 'tcms-messaging'); ?></div>
                </div>
            </div>
            
            <div class="tcms-stat-card">
                <div class="tcms-stat-icon dashicons dashicons-star-filled"></div>
                <div class="tcms-stat-content">
                    <div class="tcms-stat-value"><?php echo number_format($stats['premium_users']); ?></div>
                    <div class="tcms-stat-label"><?php _e('Premium Users', 'tcms-messaging'); ?></div>
                </div>
            </div>
            
            <div class="tcms-stat-card">
                <div class="tcms-stat-icon dashicons dashicons-visibility"></div>
                <div class="tcms-stat-content">
                    <div class="tcms-stat-value"><?php echo number_format($stats['active_users']); ?></div>
                    <div class="tcms-stat-label"><?php _e('Active Users (7d)', 'tcms-messaging'); ?></div>
                </div>
            </div>
            
            <div class="tcms-stat-card">
                <div class="tcms-stat-icon dashicons dashicons-format-status"></div>
                <div class="tcms-stat-content">
                    <div class="tcms-stat-value"><?php echo number_format($stats['online_users']); ?></div>
                    <div class="tcms-stat-label"><?php _e('Online Now', 'tcms-messaging'); ?></div>
                </div>
            </div>
            
            <div class="tcms-stat-card">
                <div class="tcms-stat-icon dashicons dashicons-email"></div>
                <div class="tcms-stat-content">
                    <div class="tcms-stat-value"><?php echo number_format($stats['messages_month']); ?></div>
                    <div class="tcms-stat-label"><?php _e('Messages (30d)', 'tcms-messaging'); ?></div>
                </div>
            </div>
            
            <div class="tcms-stat-card">
                <div class="tcms-stat-icon dashicons dashicons-admin-site"></div>
                <div class="tcms-stat-content">
                    <div class="tcms-stat-value"><?php echo number_format($stats['active_saunas']); ?></div>
                    <div class="tcms-stat-label"><?php _e('Active Saunas', 'tcms-messaging'); ?></div>
                </div>
            </div>
            
            <div class="tcms-stat-card">
                <div class="tcms-stat-icon dashicons dashicons-welcome-learn-more"></div>
                <div class="tcms-stat-content">
                    <div class="tcms-stat-value"><?php echo number_format($stats['new_users']); ?></div>
                    <div class="tcms-stat-label"><?php _e('New Users (7d)', 'tcms-messaging'); ?></div>
                </div>
            </div>
            
            <div class="tcms-stat-card">
                <div class="tcms-stat-icon dashicons dashicons-flag"></div>
                <div class="tcms-stat-content">
                    <div class="tcms-stat-value"><?php echo number_format($stats['pending_reports']); ?></div>
                    <div class="tcms-stat-label"><?php _e('Pending Reports', 'tcms-messaging'); ?></div>
                </div>
                <?php if ($stats['pending_reports'] > 0) : ?>
                <a href="<?php echo admin_url('admin.php?page=tcms-reports'); ?>" class="tcms-stat-action">
                    <?php _e('View', 'tcms-messaging'); ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="tcms-admin-columns">
        <div class="tcms-admin-column">
            <div class="tcms-admin-section tcms-admin-activity">
                <div class="tcms-section-header">
                    <h2><?php _e('Recent Activity', 'tcms-messaging'); ?></h2>
                </div>
                
                <div class="tcms-activity-list">
                    <?php
                    global $wpdb;
                    
                    // Get recent user registrations
                    $recent_users = $wpdb->get_results("
                        SELECT ID, user_login, user_email, user_registered
                        FROM {$wpdb->users}
                        ORDER BY user_registered DESC
                        LIMIT 5
                    ");
                    
                    // Get recent messages
                    $messages_table = $wpdb->prefix . 'tcms_messages';
                    $recent_messages = $wpdb->get_results("
                        SELECT m.*, 
                               sender.display_name as sender_name,
                               receiver.display_name as receiver_name
                        FROM {$messages_table} m
                        LEFT JOIN {$wpdb->prefix}tcms_user_profiles sender ON m.sender_id = sender.user_id
                        LEFT JOIN {$wpdb->prefix}tcms_user_profiles receiver ON m.receiver_id = receiver.user_id
                        ORDER BY m.created_at DESC
                        LIMIT 5
                    ");
                    
                    // Combine and sort activities
                    $activities = array();
                    
                    foreach ($recent_users as $user) {
                        $activities[] = array(
                            'type' => 'registration',
                            'time' => $user->user_registered,
                            'data' => $user
                        );
                    }
                    
                    foreach ($recent_messages as $message) {
                        $activities[] = array(
                            'type' => 'message',
                            'time' => $message->created_at,
                            'data' => $message
                        );
                    }
                    
                    // Sort by time (newest first)
                    usort($activities, function($a, $b) {
                        return strtotime($b['time']) - strtotime($a['time']);
                    });
                    
                    // Output activities
                    if (empty($activities)) {
                        echo '<div class="tcms-empty-message">' . __('No recent activity', 'tcms-messaging') . '</div>';
                    } else {
                        foreach (array_slice($activities, 0, 10) as $activity) {
                            if ($activity['type'] === 'registration') {
                                $user = $activity['data'];
                                ?>
                                <div class="tcms-activity-item">
                                    <div class="tcms-activity-icon tcms-icon-user-new"></div>
                                    <div class="tcms-activity-content">
                                        <div class="tcms-activity-title">
                                            <?php _e('New user registered', 'tcms-messaging'); ?>
                                        </div>
                                        <div class="tcms-activity-details">
                                            <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->ID); ?>">
                                                <?php echo esc_html($user->user_login); ?>
                                            </a>
                                            (<?php echo esc_html($user->user_email); ?>)
                                        </div>
                                    </div>
                                    <div class="tcms-activity-time">
                                        <?php echo human_time_diff(strtotime($user->user_registered), current_time('timestamp')); ?>
                                        <?php _e('ago', 'tcms-messaging'); ?>
                                    </div>
                                </div>
                                <?php
                            } elseif ($activity['type'] === 'message') {
                                $message = $activity['data'];
                                ?>
                                <div class="tcms-activity-item">
                                    <div class="tcms-activity-icon tcms-icon-message"></div>
                                    <div class="tcms-activity-content">
                                        <div class="tcms-activity-title">
                                            <?php _e('Message sent', 'tcms-messaging'); ?>
                                        </div>
                                        <div class="tcms-activity-details">
                                            <?php 
                                            printf(
                                                __('From %1$s to %2$s', 'tcms-messaging'),
                                                '<a href="' . admin_url('admin.php?page=tcms-users&action=view&user_id=' . $message->sender_id) . '">' . esc_html($message->sender_name) . '</a>',
                                                '<a href="' . admin_url('admin.php?page=tcms-users&action=view&user_id=' . $message->receiver_id) . '">' . esc_html($message->receiver_name) . '</a>'
                                            );
                                            ?>
                                        </div>
                                    </div>
                                    <div class="tcms-activity-time">
                                        <?php echo human_time_diff(strtotime($message->created_at), current_time('timestamp')); ?>
                                        <?php _e('ago', 'tcms-messaging'); ?>
                                    </div>
                                </div>
                                <?php
                            }
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <div class="tcms-admin-column">
            <div class="tcms-admin-section tcms-admin-quick-actions">
                <div class="tcms-section-header">
                    <h2><?php _e('Quick Actions', 'tcms-messaging'); ?></h2>
                </div>
                
                <div class="tcms-quick-actions-grid">
                    <a href="<?php echo admin_url('admin.php?page=tcms-users'); ?>" class="tcms-quick-action">
                        <div class="tcms-quick-action-icon dashicons dashicons-admin-users"></div>
                        <div class="tcms-quick-action-text">
                            <h3><?php _e('Manage Users', 'tcms-messaging'); ?></h3>
                            <p><?php _e('View and manage all users', 'tcms-messaging'); ?></p>
                        </div>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=tcms-saunas'); ?>" class="tcms-quick-action">
                        <div class="tcms-quick-action-icon dashicons dashicons-admin-site"></div>
                        <div class="tcms-quick-action-text">
                            <h3><?php _e('Manage Saunas', 'tcms-messaging'); ?></h3>
                            <p><?php _e('View and manage all saunas', 'tcms-messaging'); ?></p>
                        </div>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=tcms-messages'); ?>" class="tcms-quick-action">
                        <div class="tcms-quick-action-icon dashicons dashicons-email"></div>
                        <div class="tcms-quick-action-text">
                            <h3><?php _e('View Messages', 'tcms-messaging'); ?></h3>
                            <p><?php _e('Browse all messages', 'tcms-messaging'); ?></p>
                        </div>
                    </a>
                    
                    <a href="<?php echo admin_url('admin.php?page=tcms-reports'); ?>" class="tcms-quick-action">
                        <div class="tcms-quick-action-icon dashicons dashicons-flag"></div>
                        <div class="tcms-quick-action-text">
                            <h3><?php _e('Reports', 'tcms-messaging'); ?></h3>
                            <p><?php _e('Handle user reports', 'tcms-messaging'); ?></p>
                        </div>
                    </a>
                </div>
            </div>
            
            <div class="tcms-admin-section tcms-admin-system-info">
                <div class="tcms-section-header">
                    <h2><?php _e('System Information', 'tcms-messaging'); ?></h2>
                </div>
                
                <table class="tcms-system-info-table">
                    <tr>
                        <th><?php _e('WordPress Version', 'tcms-messaging'); ?></th>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('PHP Version', 'tcms-messaging'); ?></th>
                        <td><?php echo phpversion(); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Database Version', 'tcms-messaging'); ?></th>
                        <td><?php echo $wpdb->db_version(); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('TCMS Version', 'tcms-messaging'); ?></th>
                        <td><?php echo TCMS_VERSION; ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Server Software', 'tcms-messaging'); ?></th>
                        <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Memory Limit', 'tcms-messaging'); ?></th>
                        <td><?php echo WP_MEMORY_LIMIT; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
/* Admin Dashboard Styles */
.tcms-admin-dashboard {
    max-width: 1600px;
}

.tcms-admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.tcms-admin-info {
    display: flex;
    align-items: center;
    gap: 20px;
}

.tcms-version {
    font-weight: 500;
}

.tcms-version span {
    color: #00FFCC;
    font-weight: 600;
}

.tcms-admin-section {
    background: #fff;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    overflow: hidden;
}

.tcms-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
}

.tcms-section-header h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.tcms-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
    gap: 20px;
    padding: 20px;
}

.tcms-stat-card {
    display: flex;
    align-items: center;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 4px;
    position: relative;
}

.tcms-stat-icon {
    font-size: 24px;
    color: #00FFCC;
    margin-right: 15px;
}

.tcms-stat-content {
    flex: 1;
}

.tcms-stat-value {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 5px;
}

.tcms-stat-label {
    font-size: 13px;
    color: #777;
}

.tcms-stat-action {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 3px 8px;
    background: #00FFCC;
    color: #333;
    border-radius: 3px;
    font-size: 12px;
    text-decoration: none;
}

.tcms-admin-columns {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.tcms-activity-list {
    padding: 15px;
}

.tcms-activity-item {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.tcms-activity-item:last-child {
    border-bottom: none;
}

.tcms-activity-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f0f0f0;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.tcms-icon-user-new::before {
    content: "\f110";
    font-family: dashicons;
    color: #00FFCC;
}

.tcms-icon-message::before {
    content: "\f465";
    font-family: dashicons;
    color: #00FFCC;
}

.tcms-activity-content {
    flex: 1;
}

.tcms-activity-title {
    font-weight: 600;
    margin-bottom: 3px;
}

.tcms-activity-details {
    font-size: 13px;
    color: #777;
}

.tcms-activity-time {
    font-size: 12px;
    color: #999;
    min-width: 90px;
    text-align: right;
}

.tcms-quick-actions-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    padding: 15px;
}

.tcms-quick-action {
    display: flex;
    align-items: center;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 4px;
    text-decoration: none;
    color: inherit;
    transition: all 0.2s ease;
}

.tcms-quick-action:hover {
    background: #f0f0f0;
    transform: translateY(-2px);
}

.tcms-quick-action-icon {
    font-size: 24px;
    color: #00FFCC;
    margin-right: 15px;
}

.tcms-quick-action-text h3 {
    margin: 0 0 5px 0;
    font-size: 14px;
}

.tcms-quick-action-text p {
    margin: 0;
    font-size: 12px;
    color: #777;
}

.tcms-system-info-table {
    width: 100%;
    border-collapse: collapse;
}

.tcms-system-info-table th,
.tcms-system-info-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #f0f0f0;
}

.tcms-system-info-table th {
    width: 40%;
    font-weight: 600;
}

.tcms-empty-message {
    padding: 20px;
    text-align: center;
    color: #777;
    font-style: italic;
}

@media screen and (max-width: 1200px) {
    .tcms-admin-columns {
        grid-template-columns: 1fr;
    }
}

@media screen and (max-width: 782px) {
    .tcms-stats-grid,
    .tcms-quick-actions-grid {
        grid-template-columns: 1fr;
    }
    
    .tcms-admin-header {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Refresh stats
    $('.tcms-refresh-stats').on('click', function() {
        const section = $(this).data('section');
        const button = $(this);
        
        // Add loading state
        button.addClass('loading').text('Loading...');
        
        // Make AJAX request
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tcms_refresh_stats',
                section: section,
                nonce: '<?php echo wp_create_nonce('tcms_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Reload page to show updated stats
                    location.reload();
                } else {
                    alert('Error refreshing stats');
                    button.removeClass('loading').text('Refresh');
                }
            },
            error: function() {
                alert('Error refreshing stats');
                button.removeClass('loading').text('Refresh');
            }
        });
    });
});
</script>
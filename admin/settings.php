<?php
/**
 * settings.php - System Settings Page
 * Allows admin to configure church settings, system preferences, and user roles
 */

require_once 'config/database.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

// Only Admin can access settings
if($_SESSION['role'] !== 'Admin') {
    $_SESSION['error'] = "You don't have permission to access settings.";
    redirect('dashboard.php');
}

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

// Create settings table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS church_settings (
    setting_id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'textarea', 'json') DEFAULT 'text',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$db->exec($create_table);

// Default settings
$default_settings = [
    'church_name' => ['value' => 'Kisii University SDA Church', 'type' => 'text', 'description' => 'Official name of the church'],
    'church_address' => ['value' => 'Kisii University, Kisii, Kenya', 'type' => 'textarea', 'description' => 'Physical address of the church'],
    'church_phone' => ['value' => '+254 700 000000', 'type' => 'text', 'description' => 'Main church phone number'],
    'church_email' => ['value' => 'info@kisiiuniversitysdachurch.org', 'type' => 'text', 'description' => 'Official church email address'],
    'service_time_sabbath_school' => ['value' => '09:00 AM', 'type' => 'text', 'description' => 'Sabbath School start time'],
    'service_time_divine' => ['value' => '11:00 AM', 'type' => 'text', 'description' => 'Divine Service start time'],
    'service_time_prayer' => ['value' => '05:30 PM', 'type' => 'text', 'description' => 'Wednesday Prayer Meeting time'],
    'sermon_absence_threshold' => ['value' => '3', 'type' => 'number', 'description' => 'Days absent before showing in follow-up'],
    'attendance_reminder_days' => ['value' => '7', 'type' => 'number', 'description' => 'Days before event to send reminder'],
    'auto_backup_enabled' => ['value' => '1', 'type' => 'boolean', 'description' => 'Enable automatic database backups'],
    'backup_frequency' => ['value' => 'daily', 'type' => 'text', 'description' => 'Backup frequency (daily/weekly/monthly)'],
    'maintenance_mode' => ['value' => '0', 'type' => 'boolean', 'description' => 'Put website in maintenance mode'],
    'maintenance_message' => ['value' => 'Website is under maintenance. Please check back soon.', 'type' => 'textarea', 'description' => 'Message shown during maintenance mode'],
    'timezone' => ['value' => 'Africa/Nairobi', 'type' => 'text', 'description' => 'Default timezone for the system'],
    'date_format' => ['value' => 'F j, Y', 'type' => 'text', 'description' => 'Date format (PHP date format)'],
    'currency_symbol' => ['value' => 'KES', 'type' => 'text', 'description' => 'Currency symbol for offerings'],
    'currency_position' => ['value' => 'before', 'type' => 'text', 'description' => 'Currency position (before/after)'],
    'enable_member_registration' => ['value' => '1', 'type' => 'boolean', 'description' => 'Allow online membership registration'],
    'require_email_verification' => ['value' => '1', 'type' => 'boolean', 'description' => 'Require email verification for new members'],
    'smtp_host' => ['value' => '', 'type' => 'text', 'description' => 'SMTP server hostname'],
    'smtp_port' => ['value' => '587', 'type' => 'number', 'description' => 'SMTP server port'],
    'smtp_encryption' => ['value' => 'tls', 'type' => 'text', 'description' => 'SMTP encryption (tls/ssl/none)'],
    'smtp_username' => ['value' => '', 'type' => 'text', 'description' => 'SMTP authentication username'],
    'smtp_password' => ['value' => '', 'type' => 'text', 'description' => 'SMTP authentication password'],
    'social_facebook' => ['value' => '', 'type' => 'text', 'description' => 'Facebook page URL'],
    'social_twitter' => ['value' => '', 'type' => 'text', 'description' => 'Twitter/X profile URL'],
    'social_youtube' => ['value' => '', 'type' => 'text', 'description' => 'YouTube channel URL'],
    'social_instagram' => ['value' => '', 'type' => 'text', 'description' => 'Instagram profile URL'],
    'social_whatsapp' => ['value' => '', 'type' => 'text', 'description' => 'WhatsApp group/contact link'],
    'logo_url' => ['value' => '', 'type' => 'text', 'description' => 'URL or path to church logo'],
    'favicon_url' => ['value' => '', 'type' => 'text', 'description' => 'URL or path to favicon'],
    'footer_copyright' => ['value' => 'Kisii University SDA Church. All rights reserved.', 'type' => 'textarea', 'description' => 'Copyright text in footer'],
    'analytics_id' => ['value' => '', 'type' => 'text', 'description' => 'Google Analytics tracking ID']
];

// Insert default settings if not exist
foreach ($default_settings as $key => $setting) {
    $check = "SELECT setting_id FROM church_settings WHERE setting_key = :key";
    $stmt = $db->prepare($check);
    $stmt->execute([':key' => $key]);
    if ($stmt->rowCount() == 0) {
        $insert = "INSERT INTO church_settings (setting_key, setting_value, setting_type, description) 
                   VALUES (:key, :value, :type, :desc)";
        $stmt = $db->prepare($insert);
        $stmt->execute([
            ':key' => $key,
            ':value' => $setting['value'],
            ':type' => $setting['type'],
            ':desc' => $setting['description']
        ]);
    }
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $settings = $_POST['settings'] ?? [];
    
    foreach ($settings as $key => $value) {
        $update = "UPDATE church_settings SET setting_value = :value WHERE setting_key = :key";
        $stmt = $db->prepare($update);
        $stmt->execute([
            ':value' => $value,
            ':key' => $key
        ]);
    }
    
    $success = "Settings updated successfully!";
    
    // Update timezone if changed
    if (isset($settings['timezone'])) {
        date_default_timezone_set($settings['timezone']);
    }
}

// Handle reset to defaults
if (isset($_POST['reset_defaults'])) {
    foreach ($default_settings as $key => $setting) {
        $update = "UPDATE church_settings SET setting_value = :value WHERE setting_key = :key";
        $stmt = $db->prepare($update);
        $stmt->execute([
            ':value' => $setting['value'],
            ':key' => $key
        ]);
    }
    $success = "Settings reset to defaults!";
}

// Get all settings
$query = "SELECT * FROM church_settings ORDER BY setting_key";
$stmt = $db->prepare($query);
$stmt->execute();
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organize settings by category
$settings_by_category = [
    'general' => ['church_name', 'church_address', 'church_phone', 'church_email', 'timezone', 'date_format', 'currency_symbol', 'currency_position'],
    'services' => ['service_time_sabbath_school', 'service_time_divine', 'service_time_prayer'],
    'notifications' => ['sermon_absence_threshold', 'attendance_reminder_days', 'auto_backup_enabled', 'backup_frequency'],
    'maintenance' => ['maintenance_mode', 'maintenance_message'],
    'membership' => ['enable_member_registration', 'require_email_verification'],
    'email' => ['smtp_host', 'smtp_port', 'smtp_encryption', 'smtp_username', 'smtp_password'],
    'social' => ['social_facebook', 'social_twitter', 'social_youtube', 'social_instagram', 'social_whatsapp'],
    'branding' => ['logo_url', 'favicon_url', 'footer_copyright'],
    'analytics' => ['analytics_id']
];

// Create lookup array
$settings_lookup = [];
foreach ($settings as $setting) {
    $settings_lookup[$setting['setting_key']] = $setting;
}
?>

<style>
    .settings-tabs {
        background: white;
        border-radius: 16px;
        border: 1px solid #e0e0e0;
        overflow: hidden;
        margin-bottom: 24px;
    }
    
    .settings-tab-btn {
        padding: 12px 20px;
        background: none;
        border: none;
        text-align: left;
        width: 100%;
        cursor: pointer;
        transition: all 0.2s;
        border-left: 3px solid transparent;
    }
    
    .settings-tab-btn:hover {
        background: #f8f9fa;
    }
    
    .settings-tab-btn.active {
        background: #f8f9fa;
        border-left-color: #E74C3C;
        font-weight: 600;
    }
    
    .settings-panel {
        display: none;
    }
    
    .settings-panel.active {
        display: block;
    }
    
    .setting-card {
        background: white;
        border-radius: 16px;
        border: 1px solid #e0e0e0;
        overflow: hidden;
        margin-bottom: 24px;
    }
    
    .setting-card .card-header {
        background: #f8f9fa;
        padding: 15px 20px;
        font-weight: 600;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .setting-card .card-header i {
        color: #E74C3C;
        margin-right: 8px;
    }
    
    .setting-row {
        padding: 15px 20px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .setting-row:last-child {
        border-bottom: none;
    }
    
    .setting-label {
        font-weight: 600;
        margin-bottom: 5px;
        color: #333;
    }
    
    .setting-description {
        font-size: 0.75rem;
        color: #888;
        margin-top: 5px;
    }
    
    .setting-value input, 
    .setting-value textarea, 
    .setting-value select {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        transition: all 0.2s;
    }
    
    .setting-value input:focus,
    .setting-value textarea:focus,
    .setting-value select:focus {
        outline: none;
        border-color: #E74C3C;
        box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
    }
    
    .checkbox-wrapper {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .checkbox-wrapper input {
        width: auto;
    }
    
    .btn-save {
        background: #E74C3C;
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        color: white;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .btn-save:hover {
        background: #c0392b;
        transform: translateY(-1px);
    }
    
    @media (max-width: 768px) {
        .settings-tab-btn {
            padding: 10px 15px;
            font-size: 0.85rem;
        }
    }
</style>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-cog"></i> System Settings</h2>
        <form method="POST" onsubmit="return confirm('Reset all settings to defaults? This cannot be undone.')">
            <button type="submit" name="reset_defaults" class="btn btn-secondary">
                <i class="fas fa-undo"></i> Reset to Defaults
            </button>
        </form>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Sidebar Tabs -->
        <div class="col-md-3 mb-4">
            <div class="settings-tabs">
                <button class="settings-tab-btn active" data-tab="general">
                    <i class="fas fa-church"></i> General Settings
                </button>
                <button class="settings-tab-btn" data-tab="services">
                    <i class="fas fa-clock"></i> Service Times
                </button>
                <button class="settings-tab-btn" data-tab="notifications">
                    <i class="fas fa-bell"></i> Notifications
                </button>
                <button class="settings-tab-btn" data-tab="membership">
                    <i class="fas fa-user-plus"></i> Membership
                </button>
                <button class="settings-tab-btn" data-tab="email">
                    <i class="fas fa-envelope"></i> Email Settings
                </button>
                <button class="settings-tab-btn" data-tab="social">
                    <i class="fab fa-facebook"></i> Social Media
                </button>
                <button class="settings-tab-btn" data-tab="branding">
                    <i class="fas fa-paint-brush"></i> Branding
                </button>
                <button class="settings-tab-btn" data-tab="analytics">
                    <i class="fas fa-chart-line"></i> Analytics
                </button>
                <button class="settings-tab-btn" data-tab="maintenance">
                    <i class="fas fa-tools"></i> Maintenance
                </button>
            </div>
        </div>
        
        <!-- Settings Forms -->
        <div class="col-md-9">
            <form method="POST" id="settingsForm">
                <!-- General Settings Panel -->
                <div class="settings-panel active" id="panel-general">
                    <div class="setting-card">
                        <div class="card-header">
                            <i class="fas fa-church"></i> Church Information
                        </div>
                        <div class="card-body">
                            <?php foreach ($settings_by_category['general'] as $key): ?>
                            <?php if (isset($settings_lookup[$key])): ?>
                            <div class="setting-row">
                                <div class="setting-label"><?php echo ucwords(str_replace('_', ' ', str_replace('church_', '', $key))); ?></div>
                                <div class="setting-value">
                                    <?php if ($settings_lookup[$key]['setting_type'] == 'textarea'): ?>
                                        <textarea name="settings[<?php echo $key; ?>]" rows="2"><?php echo htmlspecialchars($settings_lookup[$key]['setting_value']); ?></textarea>
                                    <?php else: ?>
                                        <input type="text" name="settings[<?php echo $key; ?>]" value="<?php echo htmlspecialchars($settings_lookup[$key]['setting_value']); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="setting-description"><?php echo $settings_lookup[$key]['description']; ?></div>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                            
                            <div class="setting-row">
                                <div class="setting-label">Timezone</div>
                                <div class="setting-value">
                                    <select name="settings[timezone]">
                                        <?php
                                        $timezones = [
                                            'Africa/Nairobi' => 'Africa/Nairobi (EAT)',
                                            'Africa/Lagos' => 'Africa/Lagos (WAT)',
                                            'Africa/Johannesburg' => 'Africa/Johannesburg (SAST)',
                                            'UTC' => 'UTC',
                                            'America/New_York' => 'America/New_York (EST)',
                                            'Europe/London' => 'Europe/London (GMT)'
                                        ];
                                        $current_tz = $settings_lookup['timezone']['setting_value'] ?? 'Africa/Nairobi';
                                        foreach ($timezones as $tz => $label):
                                        ?>
                                            <option value="<?php echo $tz; ?>" <?php echo $current_tz == $tz ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="setting-description">Default timezone for the system</div>
                            </div>
                            
                            <div class="setting-row">
                                <div class="setting-label">Date Format</div>
                                <div class="setting-value">
                                    <input type="text" name="settings[date_format]" value="<?php echo htmlspecialchars($settings_lookup['date_format']['setting_value'] ?? 'F j, Y'); ?>">
                                </div>
                                <div class="setting-description">PHP date format (e.g., F j, Y = January 1, 2024)</div>
                            </div>
                            
                            <div class="setting-row">
                                <div class="setting-label">Currency Settings</div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Currency Symbol</label>
                                        <input type="text" name="settings[currency_symbol]" value="<?php echo htmlspecialchars($settings_lookup['currency_symbol']['setting_value'] ?? 'KES'); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label>Currency Position</label>
                                        <select name="settings[currency_position]">
                                            <option value="before" <?php echo ($settings_lookup['currency_position']['setting_value'] ?? 'before') == 'before' ? 'selected' : ''; ?>>Before (KES 100)</option>
                                            <option value="after" <?php echo ($settings_lookup['currency_position']['setting_value'] ?? 'before') == 'after' ? 'selected' : ''; ?>>After (100 KES)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Service Times Panel -->
                <div class="settings-panel" id="panel-services">
                    <div class="setting-card">
                        <div class="card-header">
                            <i class="fas fa-clock"></i> Service Times
                        </div>
                        <div class="card-body">
                            <?php foreach ($settings_by_category['services'] as $key): ?>
                            <?php if (isset($settings_lookup[$key])): ?>
                            <div class="setting-row">
                                <div class="setting-label"><?php echo ucwords(str_replace('_', ' ', str_replace('service_time_', '', $key))); ?></div>
                                <div class="setting-value">
                                    <input type="time" name="settings[<?php echo $key; ?>]" value="<?php echo htmlspecialchars($settings_lookup[$key]['setting_value']); ?>">
                                </div>
                                <div class="setting-description"><?php echo $settings_lookup[$key]['description']; ?></div>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Notifications Panel -->
                <div class="settings-panel" id="panel-notifications">
                    <div class="setting-card">
                        <div class="card-header">
                            <i class="fas fa-bell"></i> Notification Settings
                        </div>
                        <div class="card-body">
                            <div class="setting-row">
                                <div class="setting-label">Sermon Absence Threshold</div>
                                <div class="setting-value">
                                    <input type="number" name="settings[sermon_absence_threshold]" value="<?php echo htmlspecialchars($settings_lookup['sermon_absence_threshold']['setting_value'] ?? 3); ?>" min="1">
                                </div>
                                <div class="setting-description">Days absent before showing in follow-up list</div>
                            </div>
                            
                            <div class="setting-row">
                                <div class="setting-label">Attendance Reminder Days</div>
                                <div class="setting-value">
                                    <input type="number" name="settings[attendance_reminder_days]" value="<?php echo htmlspecialchars($settings_lookup['attendance_reminder_days']['setting_value'] ?? 7); ?>" min="1">
                                </div>
                                <div class="setting-description">Days before event to send reminder</div>
                            </div>
                            
                            <div class="setting-row">
                                <div class="setting-label">Automatic Backup</div>
                                <div class="setting-value">
                                    <div class="checkbox-wrapper">
                                        <input type="hidden" name="settings[auto_backup_enabled]" value="0">
                                        <input type="checkbox" name="settings[auto_backup_enabled]" value="1" <?php echo ($settings_lookup['auto_backup_enabled']['setting_value'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                        <span>Enable automatic database backups</span>
                                    </div>
                                </div>
                                <div class="setting-description">Automatically backup database daily</div>
                            </div>
                            
                            <div class="setting-row">
                                <div class="setting-label">Backup Frequency</div>
                                <div class="setting-value">
                                    <select name="settings[backup_frequency]">
                                        <option value="daily" <?php echo ($settings_lookup['backup_frequency']['setting_value'] ?? 'daily') == 'daily' ? 'selected' : ''; ?>>Daily</option>
                                        <option value="weekly" <?php echo ($settings_lookup['backup_frequency']['setting_value'] ?? 'daily') == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                        <option value="monthly" <?php echo ($settings_lookup['backup_frequency']['setting_value'] ?? 'daily') == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Membership Panel -->
                <div class="settings-panel" id="panel-membership">
                    <div class="setting-card">
                        <div class="card-header">
                            <i class="fas fa-user-plus"></i> Membership Settings
                        </div>
                        <div class="card-body">
                            <div class="setting-row">
                                <div class="setting-label">Online Registration</div>
                                <div class="setting-value">
                                    <div class="checkbox-wrapper">
                                        <input type="hidden" name="settings[enable_member_registration]" value="0">
                                        <input type="checkbox" name="settings[enable_member_registration]" value="1" <?php echo ($settings_lookup['enable_member_registration']['setting_value'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                        <span>Allow online membership registration</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="setting-row">
                                <div class="setting-label">Email Verification</div>
                                <div class="setting-value">
                                    <div class="checkbox-wrapper">
                                        <input type="hidden" name="settings[require_email_verification]" value="0">
                                        <input type="checkbox" name="settings[require_email_verification]" value="1" <?php echo ($settings_lookup['require_email_verification']['setting_value'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                        <span>Require email verification for new members</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Email Settings Panel -->
                <div class="settings-panel" id="panel-email">
                    <div class="setting-card">
                        <div class="card-header">
                            <i class="fas fa-envelope"></i> Email (SMTP) Settings
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Configure SMTP to send emails from the system.
                            </div>
                            
                            <?php foreach ($settings_by_category['email'] as $key): ?>
                            <?php if (isset($settings_lookup[$key])): ?>
                            <div class="setting-row">
                                <div class="setting-label"><?php echo ucwords(str_replace('_', ' ', str_replace('smtp_', '', $key))); ?></div>
                                <div class="setting-value">
                                    <?php if ($key == 'smtp_password'): ?>
                                        <input type="password" name="settings[<?php echo $key; ?>]" value="<?php echo htmlspecialchars($settings_lookup[$key]['setting_value']); ?>" placeholder="••••••••">
                                    <?php else: ?>
                                        <input type="text" name="settings[<?php echo $key; ?>]" value="<?php echo htmlspecialchars($settings_lookup[$key]['setting_value']); ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="setting-description"><?php echo $settings_lookup[$key]['description']; ?></div>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Social Media Panel -->
                <div class="settings-panel" id="panel-social">
                    <div class="setting-card">
                        <div class="card-header">
                            <i class="fas fa-share-alt"></i> Social Media Links
                        </div>
                        <div class="card-body">
                            <?php foreach ($settings_by_category['social'] as $key): ?>
                            <?php if (isset($settings_lookup[$key])): ?>
                            <div class="setting-row">
                                <div class="setting-label"><?php echo ucfirst(str_replace('social_', '', $key)); ?></div>
                                <div class="setting-value">
                                    <input type="url" name="settings[<?php echo $key; ?>]" value="<?php echo htmlspecialchars($settings_lookup[$key]['setting_value']); ?>" placeholder="https://...">
                                </div>
                                <div class="setting-description">Full URL to your <?php echo ucfirst(str_replace('social_', '', $key)); ?> page</div>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Branding Panel -->
                <div class="settings-panel" id="panel-branding">
                    <div class="setting-card">
                        <div class="card-header">
                            <i class="fas fa-paint-brush"></i> Branding
                        </div>
                        <div class="card-body">
                            <?php foreach ($settings_by_category['branding'] as $key): ?>
                            <?php if (isset($settings_lookup[$key])): ?>
                            <div class="setting-row">
                                <div class="setting-label"><?php echo ucwords(str_replace('_', ' ', $key)); ?></div>
                                <div class="setting-value">
                                    <?php if ($key == 'footer_copyright'): ?>
                                        <textarea name="settings[<?php echo $key; ?>]" rows="2"><?php echo htmlspecialchars($settings_lookup[$key]['setting_value']); ?></textarea>
                                    <?php else: ?>
                                        <input type="text" name="settings[<?php echo $key; ?>]" value="<?php echo htmlspecialchars($settings_lookup[$key]['setting_value']); ?>" placeholder="URL or path to image">
                                    <?php endif; ?>
                                </div>
                                <div class="setting-description"><?php echo $settings_lookup[$key]['description']; ?></div>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Analytics Panel -->
                <div class="settings-panel" id="panel-analytics">
                    <div class="setting-card">
                        <div class="card-header">
                            <i class="fas fa-chart-line"></i> Analytics & Tracking
                        </div>
                        <div class="card-body">
                            <div class="setting-row">
                                <div class="setting-label">Google Analytics ID</div>
                                <div class="setting-value">
                                    <input type="text" name="settings[analytics_id]" value="<?php echo htmlspecialchars($settings_lookup['analytics_id']['setting_value'] ?? ''); ?>" placeholder="G-XXXXXXXXXX">
                                </div>
                                <div class="setting-description">Your Google Analytics measurement ID</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Maintenance Panel -->
                <div class="settings-panel" id="panel-maintenance">
                    <div class="setting-card">
                        <div class="card-header">
                            <i class="fas fa-tools"></i> Maintenance Mode
                        </div>
                        <div class="card-body">
                            <div class="setting-row">
                                <div class="setting-label">Maintenance Mode</div>
                                <div class="setting-value">
                                    <div class="checkbox-wrapper">
                                        <input type="hidden" name="settings[maintenance_mode]" value="0">
                                        <input type="checkbox" name="settings[maintenance_mode]" id="maintenance_mode" value="1" <?php echo ($settings_lookup['maintenance_mode']['setting_value'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                        <span>Enable maintenance mode</span>
                                    </div>
                                </div>
                                <div class="setting-description">When enabled, only admins can access the site</div>
                            </div>
                            
                            <div class="setting-row">
                                <div class="setting-label">Maintenance Message</div>
                                <div class="setting-value">
                                    <textarea name="settings[maintenance_message]" rows="3"><?php echo htmlspecialchars($settings_lookup['maintenance_message']['setting_value'] ?? 'Website is under maintenance. Please check back soon.'); ?></textarea>
                                </div>
                                <div class="setting-description">Message shown to visitors during maintenance</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Save Button -->
                <div class="mt-4">
                    <button type="submit" name="update_settings" class="btn btn-save">
                        <i class="fas fa-save"></i> Save All Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Tab switching
    const tabButtons = document.querySelectorAll('.settings-tab-btn');
    const panels = document.querySelectorAll('.settings-panel');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabId = button.dataset.tab;
            
            // Update active state on buttons
            tabButtons.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            
            // Update active panel
            panels.forEach(panel => panel.classList.remove('active'));
            document.getElementById(`panel-${tabId}`).classList.add('active');
        });
    });
</script>

<?php require_once '../admin/includes/footer.php'; ?>
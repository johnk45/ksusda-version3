<?php
/**
 * dashboard.php - Enhanced Church Management Dashboard
 * Features: Functional admin topbar, working notifications, search filter, mobile optimized
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

// Total members
$query = "SELECT COUNT(*) as total FROM members WHERE membership_status = 'Active'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_members'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Today's attendance
$query = "SELECT COUNT(*) as total FROM attendance WHERE service_date = CURDATE() AND status = 'Present'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['today_attendance'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Monthly offerings (only total, no chart)
$query = "SELECT SUM(amount) as total FROM offerings WHERE MONTH(offering_date) = MONTH(CURDATE()) AND YEAR(offering_date) = YEAR(CURDATE())";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['monthly_offerings'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Upcoming events
$query = "SELECT COUNT(*) as total FROM events WHERE event_date >= CURDATE() AND status = 'Planned'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['upcoming_events'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pending prayer requests
$query = "SELECT COUNT(*) as total FROM prayer_requests WHERE status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['pending_prayers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pending membership requests
$query = "SELECT COUNT(*) as total FROM pending_members WHERE status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['pending_members'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get real notifications count
$notifications = [];

// Check pending membership requests
if ($stats['pending_members'] > 0) {
    $notifications[] = [
        'type' => 'membership',
        'icon' => 'fas fa-user-plus',
        'title' => 'Pending Membership Requests',
        'message' => $stats['pending_members'] . ' new membership request(s) waiting for approval',
        'link' => 'admin_approve_members.php',
        'time' => 'Just now'
    ];
}

// Check pending prayer requests
if ($stats['pending_prayers'] > 0) {
    $notifications[] = [
        'type' => 'prayer',
        'icon' => 'fas fa-praying-hands',
        'title' => 'Prayer Requests',
        'message' => $stats['pending_prayers'] . ' prayer request(s) need your attention',
        'link' => 'admin_prayers.php',
        'time' => 'Pending'
    ];
}

// Check upcoming events (next 7 days)
$query = "SELECT COUNT(*) as total FROM events WHERE event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status = 'Planned'";
$stmt = $db->prepare($query);
$stmt->execute();
$upcoming_week_events = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
if ($upcoming_week_events > 0) {
    $notifications[] = [
        'type' => 'event',
        'icon' => 'fas fa-calendar-alt',
        'title' => 'Upcoming Events',
        'message' => $upcoming_week_events . ' event(s) scheduled in the next 7 days',
        'link' => 'events.php',
        'time' => 'This week'
    ];
}

// Recent members (last 7 days)
$query = "SELECT COUNT(*) as total FROM members WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
$stmt = $db->prepare($query);
$stmt->execute();
$new_members = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
if ($new_members > 0) {
    $notifications[] = [
        'type' => 'member',
        'icon' => 'fas fa-user-check',
        'title' => 'New Members',
        'message' => $new_members . ' new member(s) joined this week',
        'link' => 'members.php',
        'time' => 'This week'
    ];
}

$notification_count = count($notifications);

// Recent members list
$query = "SELECT * FROM members ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Upcoming events list
$query = "SELECT * FROM events WHERE event_date >= CURDATE() AND status != 'Cancelled' ORDER BY event_date ASC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$upcoming_events_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent prayer requests
$query = "SELECT * FROM prayer_requests WHERE status = 'approved' ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_prayers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Weekly attendance trend (last 7 days)
$weekly_attendance = [];
for($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $day_name = date('D', strtotime($date));
    
    $query = "SELECT COUNT(*) as count FROM attendance WHERE service_date = :date AND status = 'Present'";
    $stmt = $db->prepare($query);
    $stmt->execute([':date' => $date]);
    $weekly_attendance[] = [
        'day' => $day_name,
        'date' => $date,
        'count' => $stmt->fetch(PDO::FETCH_ASSOC)['count']
    ];
}

// Recent sermons (last 3)
$query = "SELECT * FROM sermons WHERE status = 'published' ORDER BY sermon_date DESC LIMIT 3";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_sermons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get user role
$user_role = $_SESSION['role'] ?? 'Admin';
$user_initial = strtoupper(substr($_SESSION['username'] ?? 'A', 0, 2));
?>

<style>
    /* Dashboard Specific Styles */
    :root {
        --red: #E74C3C;
        --red-dark: #C0392B;
        --gray-light: #F5F5F5;
        --gray-medium: #E0E0E0;
        --gray-dark: #666666;
        --gray-darker: #333333;
    }
    
    .stat-card {
        border-radius: 16px;
        padding: 20px;
        color: white;
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
    
    .stat-card .stat-number {
        font-size: 2rem;
        font-weight: 700;
        margin-bottom: 5px;
    }
    
    .stat-card .stat-label {
        font-size: 0.85rem;
        opacity: 0.9;
        margin-bottom: 0;
    }
    
    .stat-card .stat-icon {
        font-size: 2.5rem;
        opacity: 0.3;
        position: absolute;
        right: 20px;
        bottom: 20px;
    }
    
    .dashboard-card {
        background: white;
        border-radius: 16px;
        border: 1px solid var(--gray-medium);
        overflow: hidden;
        transition: box-shadow 0.2s;
        height: 100%;
    }
    
    .dashboard-card:hover {
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }
    
    .dashboard-card .card-header {
        background: white;
        border-bottom: 1px solid var(--gray-medium);
        padding: 15px 20px;
        font-weight: 600;
    }
    
    .dashboard-card .card-header i {
        color: var(--red);
        margin-right: 8px;
    }

    /* Admin Topbar Styles */
    .admin-topbar {
        background: #fff;
        border: 1px solid var(--gray-medium);
        border-radius: 16px;
        padding: 12px 20px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 1.5rem;
    }
    
    .topbar-search {
        display: flex;
        flex: 1 1 420px;
        gap: 10px;
        align-items: center;
    }
    
    .topbar-search input {
        flex: 1;
        min-width: 200px;
        border: 1px solid var(--gray-medium);
        border-radius: 12px;
        padding: 10px 14px;
        background: #f8f9fb;
        color: #23262f;
        font-size: 0.9rem;
    }
    
    .topbar-search input:focus {
        outline: none;
        border-color: var(--red);
        box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1);
    }
    
    .topbar-search select {
        border: 1px solid var(--gray-medium);
        border-radius: 12px;
        padding: 10px 14px;
        background: #f8f9fb;
        color: #23262f;
        font-size: 0.9rem;
        cursor: pointer;
    }
    
    .topbar-search button {
        background: var(--red);
        border: none;
        border-radius: 12px;
        padding: 10px 18px;
        color: white;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    .topbar-search button:hover {
        background: var(--red-dark);
        transform: translateY(-1px);
    }
    
    .topbar-action-group {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .topbar-btn {
        position: relative;
        border: 1px solid var(--gray-medium);
        background: #fff;
        color: #333;
        border-radius: 12px;
        padding: 10px 16px;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9rem;
    }
    
    .topbar-btn:hover {
        background: #f8f9fb;
        border-color: var(--red);
        color: var(--red);
    }
    
    .notification-badge {
        position: absolute;
        top: -6px;
        right: -6px;
        min-width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #ef4444;
        color: white;
        font-size: 0.7rem;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 5px;
    }
    
    .topbar-dropdown {
        position: absolute;
        right: 0;
        top: calc(100% + 10px);
        width: 360px;
        background: #fff;
        border: 1px solid var(--gray-medium);
        border-radius: 16px;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.12);
        z-index: 1000;
        display: none;
        overflow: hidden;
    }
    
    .topbar-dropdown.active {
        display: block;
    }
    
    .dropdown-header {
        padding: 15px 18px;
        font-weight: 700;
        border-bottom: 1px solid var(--gray-medium);
        background: #f8f9fb;
    }
    
    .topbar-dropdown-item {
        padding: 14px 18px;
        display: flex;
        align-items: flex-start;
        gap: 14px;
        border-bottom: 1px solid #eef2f7;
        text-decoration: none;
        color: #333;
        transition: background 0.2s;
        cursor: pointer;
    }
    
    .topbar-dropdown-item:hover {
        background: #f8f9fb;
    }
    
    .topbar-dropdown-item i {
        font-size: 1.2rem;
        margin-top: 2px;
    }
    
    .topbar-dropdown-item .item-content {
        flex: 1;
    }
    
    .topbar-dropdown-item .item-title {
        font-weight: 600;
        margin-bottom: 4px;
    }
    
    .topbar-dropdown-item .item-message {
        font-size: 0.8rem;
        color: #666;
        margin-bottom: 4px;
    }
    
    .topbar-dropdown-item .item-time {
        font-size: 0.7rem;
        color: #999;
    }
    
    .dropdown-footer {
        padding: 12px 18px;
        text-align: center;
        border-top: 1px solid var(--gray-medium);
    }
    
    .dropdown-footer a {
        color: var(--red);
        text-decoration: none;
        font-size: 0.85rem;
    }
    
    .dropdown-footer a:hover {
        text-decoration: underline;
    }
    
    .profile-card {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 15px 18px;
        border-bottom: 1px solid var(--gray-medium);
    }
    
    .topbar-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--red), var(--red-dark));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
    }
    
    .profile-card-info strong {
        display: block;
        font-size: 0.95rem;
    }
    
    .profile-card-info small {
        color: #71717a;
        font-size: 0.8rem;
    }
    
    .dropdown-menu-item {
        padding: 12px 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: #333;
        transition: background 0.2s;
        cursor: pointer;
    }
    
    .dropdown-menu-item:hover {
        background: #f8f9fb;
    }
    
    .dropdown-menu-item i {
        width: 20px;
        color: var(--red);
    }
    
    .quick-action-btn {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 15px;
        background: var(--gray-light);
        border-radius: 12px;
        text-decoration: none;
        color: var(--gray-darker);
        transition: all 0.2s;
        margin-bottom: 10px;
    }
    
    .quick-action-btn:hover {
        background: var(--red);
        color: white;
        transform: translateX(5px);
    }
    
    .quick-action-btn i {
        font-size: 1.1rem;
        width: 28px;
    }
    
    /* Search highlight */
    .search-highlight {
        background-color: #fff3cd;
        transition: background-color 0.3s;
    }
    
    /* Mobile Responsive */
    @media (max-width: 768px) {
        .stat-card .stat-number {
            font-size: 1.3rem;
        }
        
        .stat-card .stat-label {
            font-size: 0.7rem;
        }
        
        .stat-card .stat-icon {
            font-size: 1.8rem;
        }
        
        .admin-topbar {
            flex-direction: column;
            align-items: stretch;
            padding: 15px;
        }
        
        .topbar-search {
            width: 100%;
            flex-wrap: wrap;
        }
        
        .topbar-search input {
            min-width: auto;
        }
        
        .topbar-action-group {
            justify-content: flex-end;
            width: 100%;
        }
        
        .topbar-dropdown {
            right: auto;
            left: 0;
            width: calc(100vw - 30px);
            max-width: 360px;
        }
        
        .dashboard-card .card-header {
            padding: 12px 15px;
            font-size: 0.9rem;
        }
        
        .table td, .table th {
            padding: 8px;
            font-size: 0.85rem;
        }
        
        .quick-action-btn {
            padding: 10px 12px;
            font-size: 0.85rem;
        }
        
        .quick-action-btn i {
            font-size: 1rem;
            width: 24px;
        }
    }
    
    @media (max-width: 576px) {
        .stat-card {
            padding: 12px;
        }
        
        .stat-card .stat-number {
            font-size: 1.1rem;
        }
        
        .topbar-btn span {
            display: none;
        }
        
        .topbar-btn {
            padding: 10px 12px;
        }
        
        .dashboard-card .card-header .float-end {
            font-size: 0.7rem;
        }
    }
    
    .searchable-item {
        transition: all 0.2s;
    }
    
    .search-empty-state {
        text-align: center;
        padding: 40px;
        background: white;
        border-radius: 16px;
        color: #999;
    }
    
    /* Loading overlay for search */
    .search-loading {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        display: none;
        justify-content: center;
        align-items: center;
    }
    
    .search-loading .spinner {
        width: 50px;
        height: 50px;
        border: 4px solid white;
        border-top-color: var(--red);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>

<div class="container-fluid">
    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2><i class="fas fa-tachometer-alt" style="color: var(--red);"></i> Dashboard</h2>
        <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>!</p>
    </div>

    <!-- Admin Topbar with Search -->
    <div class="admin-topbar">
        <form class="topbar-search" id="dashboardSearchForm" onsubmit="performSearch(); return false;">
            <i class="fas fa-search" style="color: #999;"></i>
            <input type="search" id="dashboardSearchInput" placeholder="Search members, events, prayers..." autocomplete="off">
            <select id="dashboardFilter">
                <option value="all">All Sections</option>
                <option value="members">Members</option>
                <option value="events">Events</option>
                <option value="prayers">Prayers</option>
            </select>
            <button type="submit"><i class="fas fa-search"></i> Search</button>
        </form>

        <div class="topbar-action-group">
            <!-- Notifications Dropdown -->
            <div class="position-relative">
                <button type="button" class="topbar-btn" id="notificationToggle" onclick="toggleDropdown('notification')">
                    <i class="fas fa-bell"></i>
                    <span class="d-none d-sm-inline">Notifications</span>
                    <?php if($notification_count > 0): ?>
                        <span class="notification-badge" id="notificationCount"><?php echo $notification_count; ?></span>
                    <?php endif; ?>
                </button>
                <div class="topbar-dropdown" id="notificationDropdown">
                    <div class="dropdown-header">
                        <i class="fas fa-bell"></i> Notifications
                        <?php if($notification_count > 0): ?>
                            <span class="badge bg-danger ms-2"><?php echo $notification_count; ?> new</span>
                        <?php endif; ?>
                    </div>
                    <?php if(count($notifications) > 0): ?>
                        <?php foreach($notifications as $notif): ?>
                        <a href="<?php echo $notif['link']; ?>" class="topbar-dropdown-item">
                            <i class="<?php echo $notif['icon']; ?>" style="color: var(--red);"></i>
                            <div class="item-content">
                                <div class="item-title"><?php echo $notif['title']; ?></div>
                                <div class="item-message"><?php echo $notif['message']; ?></div>
                                <div class="item-time"><?php echo $notif['time']; ?></div>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="topbar-dropdown-item">
                            <i class="fas fa-check-circle" style="color: #28a745;"></i>
                            <div class="item-content">
                                <div class="item-title">All caught up!</div>
                                <div class="item-message">No new notifications at this time.</div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="dropdown-footer">
                        <a href="admin_notifications.php">View all notifications</a>
                    </div>
                </div>
            </div>

            <!-- Profile Dropdown -->
            <div class="position-relative">
                <button type="button" class="topbar-btn" id="profileToggle" onclick="toggleDropdown('profile')">
                    <span class="topbar-avatar"><?php echo $user_initial; ?></span>
                    <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="topbar-dropdown" id="profileDropdown">
                    <div class="profile-card">
                        <span class="topbar-avatar"><?php echo $user_initial; ?></span>
                        <div class="profile-card-info">
                            <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></strong>
                            <small><?php echo htmlspecialchars($user_role); ?></small>
                        </div>
                    </div>
                    <a href="profile.php" class="dropdown-menu-item">
                        <i class="fas fa-user-circle"></i> My Profile
                    </a>
                    <a href="settings.php" class="dropdown-menu-item">
                        <i class="fas fa-cog"></i> Account Settings
                    </a>
                    <a href="logout.php" class="dropdown-menu-item">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Empty State -->
    <div id="searchEmptyState" class="search-empty-state" style="display: none;">
        <i class="fas fa-search fa-3x mb-3" style="color: #ccc;"></i>
        <h5>No Results Found</h5>
        <p class="text-muted">No matching results found for your search. Try different keywords or clear the search.</p>
        <button class="btn btn-sm btn-secondary" onclick="clearSearch()">Clear Search</button>
    </div>

    <!-- Statistics Cards Row -->
    <div class="row mb-4">
        <div class="col-6 col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);" onclick="window.location.href='members.php'">
                <div class="stat-number"><?php echo $stats['total_members']; ?></div>
                <div class="stat-label">Total Active Members</div>
                <i class="fas fa-users stat-icon"></i>
            </div>
        </div>
        
        <div class="col-6 col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);" onclick="window.location.href='attendance.php'">
                <div class="stat-number"><?php echo $stats['today_attendance']; ?></div>
                <div class="stat-label">Today's Attendance</div>
                <i class="fas fa-calendar-check stat-icon"></i>
            </div>
        </div>
        
        <div class="col-6 col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);" onclick="window.location.href='offerings.php'">
                <div class="stat-number">KES <?php echo number_format($stats['monthly_offerings'], 0); ?></div>
                <div class="stat-label">Monthly Offerings</div>
                <i class="fas fa-hand-holding-usd stat-icon"></i>
            </div>
        </div>
        
        <div class="col-6 col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);" onclick="window.location.href='events.php'">
                <div class="stat-number"><?php echo $stats['upcoming_events']; ?></div>
                <div class="stat-label">Upcoming Events</div>
                <i class="fas fa-calendar-alt stat-icon"></i>
            </div>
        </div>
    </div>
    
    <!-- Second Row of Stats -->
    <div class="row mb-4">
        <div class="col-6 col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);" onclick="window.location.href='admin_prayers.php'">
                <div class="stat-number"><?php echo $stats['pending_prayers']; ?></div>
                <div class="stat-label">Pending Prayer Requests</div>
                <i class="fas fa-praying-hands stat-icon"></i>
            </div>
        </div>
        
        <div class="col-6 col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333;" onclick="window.location.href='admin_approve_members.php'">
                <div class="stat-number"><?php echo $stats['pending_members']; ?></div>
                <div class="stat-label">Pending Membership</div>
                <i class="fas fa-user-plus stat-icon"></i>
            </div>
        </div>
        
        <div class="col-6 col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); color: #333;" onclick="window.location.href='sermons.php'">
                <div class="stat-number"><?php echo count($recent_sermons); ?></div>
                <div class="stat-label">Recent Sermons</div>
                <i class="fas fa-church stat-icon"></i>
            </div>
        </div>
        
        <div class="col-6 col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%); color: #333;" onclick="window.location.href='departments.php'">
                <div class="stat-number"><?php 
                    $query = "SELECT COUNT(*) as total FROM departments";
                    $stmt = $db->prepare($query);
                    $stmt->execute();
                    echo $stmt->fetch(PDO::FETCH_ASSOC)['total'];
                ?></div>
                <div class="stat-label">Active Departments</div>
                <i class="fas fa-building stat-icon"></i>
            </div>
        </div>
    </div>
    
    <!-- Main Content Row -->
    <div class="row">
        <!-- Recent Members -->
        <div class="col-lg-6 mb-4">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-user-plus"></i> Recent Members
                    <a href="members.php" class="float-end text-decoration-none small">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr><th>Membership No</th><th>Name</th><th>Join Date</th></tr>
                            </thead>
                            <tbody id="membersList">
                                <?php if(count($recent_members) > 0): ?>
                                    <?php foreach($recent_members as $member): ?>
                                    <tr class="searchable-item" data-section="members" data-name="<?php echo strtolower($member['first_name'] . ' ' . $member['last_name']); ?>" data-membership="<?php echo $member['membership_no']; ?>" onclick="window.location.href='member_details.php?id=<?php echo $member['member_id']; ?>'" style="cursor: pointer;">
                                        <td><code><?php echo htmlspecialchars($member['membership_no']); ?></code></td>
                                        <td><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($member['join_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="3" class="text-center text-muted py-4">No members added yet</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Upcoming Events -->
        <div class="col-lg-6 mb-4">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-calendar-alt"></i> Upcoming Events
                    <a href="events.php" class="float-end text-decoration-none small">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-body p-0">
                    <div id="eventsList">
                        <?php if(count($upcoming_events_list) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach($upcoming_events_list as $event): ?>
                                <div class="list-group-item searchable-item" data-section="events" data-name="<?php echo strtolower($event['event_name']); ?>" data-venue="<?php echo strtolower($event['venue']); ?>" onclick="window.location.href='events.php?view_event=<?php echo $event['event_id']; ?>'" style="cursor: pointer;">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($event['event_name']); ?></strong>
                                            <br><small class="text-muted"><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($event['event_date'])); ?> <?php if($event['start_time']): ?> at <?php echo date('g:i A', strtotime($event['start_time'])); ?><?php endif; ?></small>
                                            <br><small class="text-muted"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['venue']) ?: 'TBD'; ?></small>
                                        </div>
                                        <span class="badge bg-<?php echo $event['status'] == 'Planned' ? 'primary' : ($event['status'] == 'Ongoing' ? 'success' : 'secondary'); ?>"><?php echo $event['status']; ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4"><i class="fas fa-calendar-times fa-2x mb-2"></i><p>No upcoming events</p><a href="events.php" class="btn btn-sm btn-primary">Create Event</a></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Second Row -->
    <div class="row">
        <!-- Prayer Requests -->
        <div class="col-lg-6 mb-4">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-praying-hands"></i> Recent Prayer Requests
                    <a href="admin_prayers.php" class="float-end text-decoration-none small">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-body p-0">
                    <div id="prayersList">
                        <?php if(count($recent_prayers) > 0): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach($recent_prayers as $prayer): ?>
                                <div class="list-group-item searchable-item" data-section="prayers" data-name="<?php echo strtolower($prayer['prayer_title']); ?>" data-requester="<?php echo strtolower($prayer['requester_name']); ?>">
                                    <div>
                                        <strong><?php echo htmlspecialchars($prayer['prayer_title']); ?></strong>
                                        <span class="badge bg-<?php echo $prayer['urgency'] == 'Critical' ? 'danger' : ($prayer['urgency'] == 'High' ? 'warning' : ($prayer['urgency'] == 'Medium' ? 'info' : 'success')); ?> float-end"><?php echo $prayer['urgency']; ?></span>
                                        <br><small class="text-muted"><i class="fas fa-user"></i> <?php echo htmlspecialchars($prayer['requester_name']); ?> <i class="fas fa-calendar ms-2"></i> <?php echo date('M d, Y', strtotime($prayer['created_at'])); ?></small>
                                        <p class="small text-muted mt-1 mb-0"><?php echo htmlspecialchars(substr($prayer['prayer_content'], 0, 100)); ?>...</p>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-4"><i class="fas fa-pray fa-2x mb-2"></i><p>No prayer requests yet</p></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="col-lg-3 mb-4">
            <div class="dashboard-card">
                <div class="card-header"><i class="fas fa-bolt"></i> Quick Actions</div>
                <div class="card-body">
                    <a href="members.php?action=add" class="quick-action-btn"><i class="fas fa-user-plus"></i> Add New Member</a>
                    <a href="events.php" class="quick-action-btn"><i class="fas fa-calendar-plus"></i> Create Event</a>
                    <a href="attendance.php" class="quick-action-btn"><i class="fas fa-check-circle"></i> Mark Attendance</a>
                    <a href="offerings.php" class="quick-action-btn"><i class="fas fa-hand-holding-usd"></i> Record Offering</a>
                    <a href="admin_sermons.php" class="quick-action-btn"><i class="fab fa-youtube"></i> Add YouTube Sermon</a>
                    <a href="admin_approve_members.php" class="quick-action-btn"><i class="fas fa-user-check"></i> Approve Members</a>
                </div>
            </div>
        </div>
        
        <!-- Recent Sermons -->
        <div class="col-lg-3 mb-4">
            <div class="dashboard-card">
                <div class="card-header"><i class="fas fa-church"></i> Recent Sermons <a href="sermons.php" class="float-end text-decoration-none small">View All <i class="fas fa-arrow-right"></i></a></div>
                <div class="card-body p-0">
                    <?php if(count($recent_sermons) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach($recent_sermons as $sermon): ?>
                            <div class="list-group-item" onclick="window.open('sermons.php', '_blank')" style="cursor: pointer;">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="https://img.youtube.com/vi/<?php echo $sermon['youtube_id']; ?>/default.jpg" alt="Thumbnail" style="width: 40px; height: 30px; object-fit: cover; border-radius: 4px;">
                                    <div><strong><?php echo htmlspecialchars(substr($sermon['title'], 0, 30)); ?></strong><br><small class="text-muted"><?php echo htmlspecialchars($sermon['preacher']); ?></small></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4"><i class="fas fa-video fa-2x mb-2"></i><p>No sermons yet</p><a href="admin_sermons.php" class="btn btn-sm btn-primary">Add Sermon</a></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Attendance Chart -->
    <div class="row mt-2">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="card-header"><i class="fas fa-chart-line"></i> Weekly Attendance Trend</div>
                <div class="card-body">
                    <canvas id="attendanceChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="searchLoading" class="search-loading"><div class="spinner"></div></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Weekly Attendance Chart
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    const weeklyData = <?php echo json_encode($weekly_attendance); ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: weeklyData.map(item => item.day),
            datasets: [{
                label: 'Attendance',
                data: weeklyData.map(item => item.count),
                borderColor: '#E74C3C',
                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#E74C3C',
                pointBorderColor: 'white',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: function(context) { return 'Attendance: ' + context.raw + ' people'; } } } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 }, title: { display: true, text: 'Number of People' } }, x: { title: { display: true, text: 'Day of Week' } } }
        }
    });

    // Dropdown Functions
    function toggleDropdown(type) {
        const notificationDropdown = document.getElementById('notificationDropdown');
        const profileDropdown = document.getElementById('profileDropdown');
        
        if (type === 'notification') {
            notificationDropdown.classList.toggle('active');
            profileDropdown.classList.remove('active');
        } else {
            profileDropdown.classList.toggle('active');
            notificationDropdown.classList.remove('active');
        }
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        const isNotificationClick = event.target.closest('#notificationToggle') || event.target.closest('#notificationDropdown');
        const isProfileClick = event.target.closest('#profileToggle') || event.target.closest('#profileDropdown');
        if (!isNotificationClick) document.getElementById('notificationDropdown').classList.remove('active');
        if (!isProfileClick) document.getElementById('profileDropdown').classList.remove('active');
    });

    // Search Functionality
    let searchTimeout;

    function performSearch() {
        const query = document.getElementById('dashboardSearchInput').value.toLowerCase().trim();
        const filter = document.getElementById('dashboardFilter').value;
        const items = document.querySelectorAll('.searchable-item');
        let visibleCount = 0;

        // Show loading
        document.getElementById('searchLoading').style.display = 'flex';
        
        setTimeout(() => {
            items.forEach(item => {
                const section = item.dataset.section;
                const searchText = (item.dataset.name || item.innerText.toLowerCase());
                const matchesSection = filter === 'all' || section === filter;
                const matchesText = !query || searchText.includes(query);
                
                if (matchesSection && matchesText) {
                    item.style.display = '';
                    visibleCount++;
                    // Highlight matching text
                    if (query) {
                        highlightText(item, query);
                    } else {
                        removeHighlight(item);
                    }
                } else {
                    item.style.display = 'none';
                }
            });

            if (visibleCount === 0 && query !== '') {
                document.getElementById('searchEmptyState').style.display = 'block';
            } else {
                document.getElementById('searchEmptyState').style.display = 'none';
            }
            
            document.getElementById('searchLoading').style.display = 'none';
        }, 200);
    }

    function highlightText(element, query) {
        if (!element || !query) return;
        const originalHtml = element.innerHTML;
        const regex = new RegExp(`(${query})`, 'gi');
        const newHtml = originalHtml.replace(regex, '<span class="search-highlight">$1</span>');
        if (originalHtml !== newHtml) {
            element.innerHTML = newHtml;
            element.setAttribute('data-original', originalHtml);
        }
    }

    function removeHighlight(element) {
        if (element.hasAttribute('data-original')) {
            element.innerHTML = element.getAttribute('data-original');
            element.removeAttribute('data-original');
        }
    }

    function clearSearch() {
        document.getElementById('dashboardSearchInput').value = '';
        document.getElementById('dashboardFilter').value = 'all';
        performSearch();
    }

    // Debounced search on input
    document.getElementById('dashboardSearchInput').addEventListener('input', function() {
        if (searchTimeout) clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 300);
    });
    document.getElementById('dashboardFilter').addEventListener('change', performSearch);

    // Refresh notifications (simple AJAX)
    function refreshNotifications() {
        fetch('ajax/get_notifications.php')
            .then(response => response.json())
            .then(data => {
                const count = data.count || 0;
                const badge = document.getElementById('notificationCount');
                if (badge) {
                    if (count > 0) {
                        badge.textContent = count;
                        badge.style.display = 'flex';
                    } else {
                        badge.style.display = 'none';
                    }
                }
            })
            .catch(error => console.log('Notification refresh failed:', error));
    }
    
    // Refresh every 60 seconds
    setInterval(refreshNotifications, 60000);
</script>

<?php require_once 'includes/footer.php'; ?>
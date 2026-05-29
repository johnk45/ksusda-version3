<?php
/**
 * dashboard.php - Enhanced Church Management Dashboard
 * Features: Statistics cards, recent activity, charts, quick actions
 * Consistent design with events, sermons, and members pages
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

// Monthly offerings
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

// Recent members
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
?>



<style>
    /* Dashboard Specific Styles */
    .stat-card {
        border-radius: 16px;
        padding: 20px;
        color: white;
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
        height: 100%;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    }
    
    .stat-card .stat-number {
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 5px;
    }
    
    .stat-card .stat-label {
        font-size: 1rem;
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
        font-size: 1.2rem;
        width: 30px;
    }
    
    .trend-up {
        color: #28a745;
    }
    
    .trend-down {
        color: #dc3545;
    }
    
    @media (max-width: 768px) {
        .stat-card .stat-number {
            font-size: 1.5rem;
        }
        .stat-card .stat-icon {
            font-size: 1.8rem;
        }
    }
</style>

<div class="container-fluid">
    <!-- Page Title -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-tachometer-alt text-red"></i> Dashboard</h2>
        <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>!</p>
    </div>
    
    <!-- Statistics Cards Row -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);" onclick="window.location.href='members.php'">
                <div class="stat-number"><?php echo $stats['total_members']; ?></div>
                <div class="stat-label">Total Active Members</div>
                <i class="fas fa-users stat-icon"></i>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);" onclick="window.location.href='attendance.php'">
                <div class="stat-number"><?php echo $stats['today_attendance']; ?></div>
                <div class="stat-label">Today's Attendance</div>
                <i class="fas fa-calendar-check stat-icon"></i>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);" onclick="window.location.href='offerings.php'">
                <div class="stat-number">KES <?php echo number_format($stats['monthly_offerings'], 0); ?></div>
                <div class="stat-label">Monthly Offerings</div>
                <i class="fas fa-hand-holding-usd stat-icon"></i>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);" onclick="window.location.href='events.php'">
                <div class="stat-number"><?php echo $stats['upcoming_events']; ?></div>
                <div class="stat-label">Upcoming Events</div>
                <i class="fas fa-calendar-alt stat-icon"></i>
            </div>
        </div>
    </div>
    
    <!-- Second Row of Stats (New) -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);" onclick="window.location.href='admin_prayers.php'">
                <div class="stat-number"><?php echo $stats['pending_prayers']; ?></div>
                <div class="stat-label">Pending Prayer Requests</div>
                <i class="fas fa-praying-hands stat-icon"></i>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333;" onclick="window.location.href='admin_approve_members.php'">
                <div class="stat-number"><?php echo $stats['pending_members']; ?></div>
                <div class="stat-label">Pending Membership Requests</div>
                <i class="fas fa-user-plus stat-icon"></i>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="stat-card" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); color: #333;" onclick="window.location.href='sermons.php'">
                <div class="stat-number"><?php echo count($recent_sermons); ?></div>
                <div class="stat-label">Recent Sermons</div>
                <i class="fas fa-church stat-icon"></i>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
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
        <!-- Left Column -->
        <div class="col-md-6 mb-4">
            <!-- Recent Members Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-user-plus"></i> Recent Members
                    <a href="members.php" class="float-end text-decoration-none small">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Membership No</th>
                                    <th>Name</th>
                                    <th>Join Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(count($recent_members) > 0): ?>
                                    <?php foreach($recent_members as $member): ?>
                                    <tr onclick="window.location.href='member_details.php?id=<?php echo $member['member_id']; ?>'" style="cursor: pointer;">
                                        <td><code><?php echo htmlspecialchars($member['membership_no']); ?></code></td>
                                        <td><?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($member['join_date'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">No members added yet</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-md-6 mb-4">
            <!-- Upcoming Events Card -->
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-calendar-alt"></i> Upcoming Events
                    <a href="events.php" class="float-end text-decoration-none small">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-body p-0">
                    <?php if(count($upcoming_events_list) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach($upcoming_events_list as $event): ?>
                            <div class="list-group-item" onclick="window.location.href='events.php?view_event=<?php echo $event['event_id']; ?>'" style="cursor: pointer;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($event['event_name']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                                            <?php if($event['start_time']): ?> at <?php echo date('g:i A', strtotime($event['start_time'])); ?><?php endif; ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($event['venue']) ?: 'TBD'; ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php 
                                        echo $event['status'] == 'Planned' ? 'primary' : 
                                            ($event['status'] == 'Ongoing' ? 'success' : 'secondary'); 
                                    ?>">
                                        <?php echo $event['status']; ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-calendar-times fa-2x mb-2"></i>
                            <p>No upcoming events</p>
                            <a href="events.php" class="btn btn-sm btn-primary">Create Event</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Second Row -->
    <div class="row">
        <!-- Prayer Requests Card -->
        <div class="col-md-6 mb-4">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-praying-hands"></i> Recent Prayer Requests
                    <a href="admin_prayers.php" class="float-end text-decoration-none small">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-body p-0">
                    <?php if(count($recent_prayers) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach($recent_prayers as $prayer): ?>
                            <div class="list-group-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($prayer['prayer_title']); ?></strong>
                                    <span class="badge bg-<?php 
                                        echo $prayer['urgency'] == 'Critical' ? 'danger' : 
                                            ($prayer['urgency'] == 'High' ? 'warning' : 
                                            ($prayer['urgency'] == 'Medium' ? 'info' : 'success')); 
                                    ?> float-end">
                                        <?php echo $prayer['urgency']; ?>
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($prayer['requester_name']); ?>
                                        <i class="fas fa-calendar ms-2"></i> <?php echo date('M d, Y', strtotime($prayer['created_at'])); ?>
                                    </small>
                                    <p class="small text-muted mt-1 mb-0"><?php echo htmlspecialchars(substr($prayer['prayer_content'], 0, 100)); ?>...</p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-pray fa-2x mb-2"></i>
                            <p>No prayer requests yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions Card -->
        <div class="col-md-3 mb-4">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-bolt"></i> Quick Actions
                </div>
                <div class="card-body">
                    <a href="members.php?action=add" class="quick-action-btn">
                        <i class="fas fa-user-plus"></i> Add New Member
                    </a>
                    <a href="events.php" class="quick-action-btn">
                        <i class="fas fa-calendar-plus"></i> Create Event
                    </a>
                    <a href="attendance.php" class="quick-action-btn">
                        <i class="fas fa-check-circle"></i> Mark Attendance
                    </a>
                    <a href="offerings.php" class="quick-action-btn">
                        <i class="fas fa-hand-holding-usd"></i> Record Offering
                    </a>
                    <a href="admin_sermons.php" class="quick-action-btn">
                        <i class="fab fa-youtube"></i> Add YouTube Sermon
                    </a>
                    <a href="admin_approve_members.php" class="quick-action-btn">
                        <i class="fas fa-user-check"></i> Approve Members
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent Sermons Card -->
        <div class="col-md-3 mb-4">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-church"></i> Recent Sermons
                    <a href="sermons.php" class="float-end text-decoration-none small">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-body p-0">
                    <?php if(count($recent_sermons) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach($recent_sermons as $sermon): ?>
                            <div class="list-group-item" onclick="window.open('sermons.php', '_blank')" style="cursor: pointer;">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="https://img.youtube.com/vi/<?php echo $sermon['youtube_id']; ?>/default.jpg" 
                                         alt="Thumbnail" style="width: 40px; height: 30px; object-fit: cover; border-radius: 4px;">
                                    <div>
                                        <strong><?php echo htmlspecialchars(substr($sermon['title'], 0, 30)); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($sermon['preacher']); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-video fa-2x mb-2"></i>
                            <p>No sermons yet</p>
                            <a href="admin_sermons.php" class="btn btn-sm btn-primary">Add Sermon</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer Stats Row -->
    <div class="row mt-2">
        <div class="col-12">
            <div class="dashboard-card">
                <div class="card-header">
                    <i class="fas fa-chart-line"></i> Weekly Attendance Trend
                </div>
                <div class="card-body">
                    <canvas id="attendanceChart" height="80"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

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
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Attendance: ${context.raw} people`;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    },
                    title: {
                        display: true,
                        text: 'Number of People'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Day of Week'
                    }
                }
            }
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>
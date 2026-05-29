<?php
// prayer_wall.php - Display all approved prayer requests
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle "I'm Praying" action
if(isset($_POST['praying']) && isset($_POST['prayer_id'])) {
    $prayer_id = $_POST['prayer_id'];
    $commenter_name = $_POST['commenter_name'] ?? 'Anonymous';
    
    // Update prayer count
    $query = "UPDATE prayer_requests SET prayer_count = prayer_count + 1 WHERE prayer_id = :prayer_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':prayer_id' => $prayer_id]);
    
    // Add comment
    $query = "INSERT INTO prayer_comments (prayer_id, commenter_name, comment_text, is_praying) 
              VALUES (:prayer_id, :commenter_name, 'I am praying for this request!', 1)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':prayer_id' => $prayer_id,
        ':commenter_name' => $commenter_name
    ]);
    
    $pray_success = "Thank you for praying!";
}

// Handle comment submission
if(isset($_POST['submit_comment']) && isset($_POST['prayer_id'])) {
    $prayer_id = $_POST['prayer_id'];
    $commenter_name = $_POST['commenter_name'] ?: 'Anonymous';
    $comment_text = $_POST['comment_text'];
    
    $query = "INSERT INTO prayer_comments (prayer_id, commenter_name, comment_text) 
              VALUES (:prayer_id, :commenter_name, :comment_text)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':prayer_id' => $prayer_id,
        ':commenter_name' => $commenter_name,
        ':comment_text' => $comment_text
    ]);
    
    $comment_success = "Comment added successfully!";
}

// Get filter parameters
$category = $_GET['category'] ?? '';
$urgency = $_GET['urgency'] ?? '';
$answered = $_GET['answered'] ?? '';

// Build query
$query = "SELECT p.*, 
          (SELECT COUNT(*) FROM prayer_comments WHERE prayer_id = p.prayer_id) as comment_count
          FROM prayer_requests p 
          WHERE p.status = 'approved' AND p.is_public = 1";

if($category && $category != 'all') {
    $query .= " AND p.category = :category";
}
if($urgency && $urgency != 'all') {
    $query .= " AND p.urgency = :urgency";
}
if($answered == 'answered') {
    $query .= " AND p.is_answered = 1";
} elseif($answered == 'unanswered') {
    $query .= " AND p.is_answered = 0";
}

$query .= " ORDER BY 
            CASE p.urgency 
                WHEN 'Critical' THEN 1 
                WHEN 'High' THEN 2 
                WHEN 'Medium' THEN 3 
                WHEN 'Low' THEN 4 
            END, 
            p.created_at DESC";

$stmt = $db->prepare($query);
if($category && $category != 'all') {
    $stmt->bindParam(':category', $category);
}
if($urgency && $urgency != 'all') {
    $stmt->bindParam(':urgency', $urgency);
}
$stmt->execute();
$prayers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$cat_query = "SELECT DISTINCT category, COUNT(*) as count FROM prayer_requests WHERE status = 'approved' GROUP BY category";
$cat_stmt = $db->prepare($cat_query);
$cat_stmt->execute();
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prayer Wall - Kisii University SDA Church</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
        }
        
        .prayer-header {
            background: lightblue,url('https://i.pinimg.com/736x/b5/a9/43/b5a9430392294777a51f458e988ec839.jpg');
            background-size:cover;
            color: white;
            padding: 60px 0;
            text-align: center;
            
        }
        
        .prayer-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            transition: transform 0.2s;
            overflow: hidden;
        }
        
        .prayer-card:hover {
            transform: translateY(-3px);
        }
        
        .prayer-header-card {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            background: #f8f9fa;
        }
        
        .prayer-body-card {
            padding: 20px 25px;
        }
        
        .urgency-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .urgency-Critical { background: #dc3545; color: white; }
        .urgency-High { background: #fd7e14; color: white; }
        .urgency-Medium { background: #ffc107; color: #333; }
        .urgency-Low { background: #28a745; color: white; }
        
        .prayer-count {
            background: #667eea;
            color: white;
            padding: 8px 15px;
            border-radius: 30px;
            font-size: 0.9rem;
        }
        
        .comment-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }
        
        .filter-btn {
            margin: 5px;
            border-radius: 25px;
            padding: 8px 20px;
        }
        
        .btn-pray {
            background: linear-gradient(135deg, #0b1339 0%, #0f172a 100%);
            border: none;
            border-radius: 25px;
            padding: 8px 20px;
            color:#fff;
        }
        
        .btn-pray:hover {
            transform: scale(1.05);
        }
        
        .answer-badge {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            color:white;
        }
        
        @media (max-width: 768px) {
            .prayer-header-card {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                 KSUSDA Church
            </a>
            <div class="ms-auto">
                <a href="prayer_request.php" class="btn btn-outline-light">
                    <i class="fas fa-praying-hands"></i> Submit Prayer
                </a>
                <a href="index.php" class="btn btn-light ms-2">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Header -->
    <div class="prayer-header">
        <div class="container">
            <i class="fas fa-hands-praying fa-4x mb-3"></i>
            <h1>Prayer Wall</h1>
            <p class="lead">"Pray without ceasing" - 1 Thessalonians 5:17</p>
            <a href="prayer_request.php" class="btn btn-light btn-lg mt-3">
                <i class="fas fa-pen"></i> Submit Prayer Request
            </a>
        </div>
    </div>
    
    <div class="container mt-4">
        <?php if(isset($pray_success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-heart"></i> <?php echo $pray_success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if(isset($comment_success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-comment"></i> <?php echo $comment_success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <h6><i class="fas fa-filter"></i> Filter Prayer Requests</h6>
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <select name="category" class="form-select">
                            <option value="all">All Categories</option>
                            <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['category']; ?>" <?php echo $category == $cat['category'] ? 'selected' : ''; ?>>
                                <?php echo $cat['category']; ?> (<?php echo $cat['count']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="urgency" class="form-select">
                            <option value="all">All Urgency</option>
                            <option value="Critical" <?php echo $urgency == 'Critical' ? 'selected' : ''; ?>>Critical</option>
                            <option value="High" <?php echo $urgency == 'High' ? 'selected' : ''; ?>>High</option>
                            <option value="Medium" <?php echo $urgency == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="Low" <?php echo $urgency == 'Low' ? 'selected' : ''; ?>>Low</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="answered" class="form-select">
                            <option value="all">All Requests</option>
                            <option value="unanswered" <?php echo $answered == 'unanswered' ? 'selected' : ''; ?>>Unanswered</option>
                            <option value="answered" <?php echo $answered == 'answered' ? 'selected' : ''; ?>>Answered</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Prayer Requests -->
        <?php if(count($prayers) > 0): ?>
            <?php foreach($prayers as $prayer): ?>
            <div class="prayer-card" id="prayer-<?php echo $prayer['prayer_id']; ?>">
                <div class="prayer-header-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <h5 class="mb-1"><?php echo htmlspecialchars($prayer['prayer_title']); ?></h5>
                            <small class="text-muted">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($prayer['requester_name']); ?>
                                <i class="fas fa-calendar ms-2"></i> <?php echo date('M d, Y', strtotime($prayer['created_at'])); ?>
                            </small>
                        </div>
                        <div>
                            <span class="urgency-badge urgency-<?php echo $prayer['urgency']; ?> me-2">
                                <?php echo $prayer['urgency']; ?>
                            </span>
                            <span class="badge bg-secondary"><?php echo $prayer['category']; ?></span>
                            <?php if($prayer['is_answered']): ?>
                            <span class="answer-badge ms-2">
                                <i class="fas fa-check-circle"></i> Answered
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="prayer-body-card">
                    <p><?php echo nl2br(htmlspecialchars($prayer['prayer_content'])); ?></p>
                    
                    <?php if($prayer['answer_notes']): ?>
                    <div class="alert alert-success mt-2">
                        <strong><i class="fas fa-pray"></i> Prayer Answered:</strong>
                        <?php echo htmlspecialchars($prayer['answer_notes']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap">
                        <div>
                            <span class="prayer-count">
                                <i class="fas fa-praying-hands"></i> <?php echo $prayer['prayer_count']; ?> people praying
                            </span>
                            <span class="ms-3 text-muted">
                                <i class="fas fa-comments"></i> <?php echo $prayer['comment_count']; ?> comments
                            </span>
                        </div>
                        <button class="btn btn-pray btn-sm" onclick="showPrayerForm(<?php echo $prayer['prayer_id']; ?>)">
                            <i class="fas fa-hands-praying"></i> I'm Praying
                        </button>
                    </div>
                    
                    <!-- Comments Section -->
                    <div class="comment-section mt-3">
                        <button class="btn btn-link p-0" onclick="toggleComments(<?php echo $prayer['prayer_id']; ?>)">
                            <i class="fas fa-comments"></i> Show Comments
                        </button>
                        
                        <div id="comments-<?php echo $prayer['prayer_id']; ?>" style="display: none;">
                            <?php
                            $comment_query = "SELECT * FROM prayer_comments WHERE prayer_id = :prayer_id ORDER BY created_at DESC LIMIT 10";
                            $comment_stmt = $db->prepare($comment_query);
                            $comment_stmt->execute([':prayer_id' => $prayer['prayer_id']]);
                            $comments = $comment_stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            
                            <?php foreach($comments as $comment): ?>
                            <div class="border-bottom py-2">
                                <strong><?php echo htmlspecialchars($comment['commenter_name']); ?></strong>
                                <?php if($comment['is_praying']): ?>
                                <span class="badge bg-success">Praying</span>
                                <?php endif; ?>
                                <small class="text-muted"><?php echo date('M d, H:i', strtotime($comment['created_at'])); ?></small>
                                <p class="mb-0"><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                            </div>
                            <?php endforeach; ?>
                            
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="prayer_id" value="<?php echo $prayer['prayer_id']; ?>">
                                <div class="input-group">
                                    <input type="text" name="commenter_name" class="form-control form-control-sm" placeholder="Your name" style="width: 120px;">
                                    <input type="text" name="comment_text" class="form-control form-control-sm" placeholder="Leave a comment or encouragement...">
                                    <button type="submit" name="submit_comment" class="btn btn-sm btn-primary">Post</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-pray fa-3x mb-3"></i>
                <h5>No prayer requests found</h5>
                <p>Be the first to submit a prayer request!</p>
                <a href="prayer_request.php" class="btn btn-primary">Submit Prayer Request</a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Praying Modal -->
    <div class="modal fade" id="prayerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">I'm Praying for This Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="prayer_id" id="prayer_id">
                        <div class="mb-3">
                            <label>Your Name (Optional)</label>
                            <input type="text" name="commenter_name" class="form-control" placeholder="Anonymous">
                        </div>
                        <p class="text-muted">Your prayer will be counted and you'll be added to the prayer list.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="praying" class="btn btn-primary">
                            <i class="fas fa-praying-hands"></i> Yes, I'm Praying
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function showPrayerForm(prayerId) {
            document.getElementById('prayer_id').value = prayerId;
            new bootstrap.Modal(document.getElementById('prayerModal')).show();
        }
        
        function toggleComments(prayerId) {
            const commentsDiv = document.getElementById(`comments-${prayerId}`);
            if(commentsDiv.style.display === 'none') {
                commentsDiv.style.display = 'block';
            } else {
                commentsDiv.style.display = 'none';
            }
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
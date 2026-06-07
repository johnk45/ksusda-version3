<?php
// prayer_request.php - Public form for submitting prayer requests
require_once __DIR__ . '/admin/config/database.php';

$database = new Database();
$db = $database->getConnection();

$success = '';
$error = '';

$memberQuery = 'SELECT member_id, CONCAT(first_name, " ", last_name) AS name FROM members WHERE membership_status = "Active" ORDER BY first_name';
$memberStmt = $db->prepare($memberQuery);
$memberStmt->execute();
$members = $memberStmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_prayer'])) {
    $member_id = !empty($_POST['member_id']) ? $_POST['member_id'] : null;
    $requester_name = trim($_POST['requester_name'] ?? '');
    $requester_email = trim($_POST['requester_email'] ?? '');
    $requester_phone = trim($_POST['requester_phone'] ?? '');
    $prayer_title = trim($_POST['prayer_title'] ?? '');
    $prayer_content = trim($_POST['prayer_content'] ?? '');
    $category = trim($_POST['category'] ?? 'Personal');
    $urgency = trim($_POST['urgency'] ?? 'Low');
    $is_public = isset($_POST['is_public']) ? 1 : 0;

    $errors = [];
    if ($requester_name === '') {
        $errors[] = 'Your name is required.';
    }
    if ($prayer_title === '') {
        $errors[] = 'A prayer title is required.';
    }
    if ($prayer_content === '') {
        $errors[] = 'Please describe your prayer request.';
    }

    if (empty($errors)) {
        $query = 'INSERT INTO prayer_requests (member_id, requester_name, requester_email, requester_phone, prayer_title, prayer_content, category, urgency, is_public, status) VALUES (:member_id, :requester_name, :requester_email, :requester_phone, :prayer_title, :prayer_content, :category, :urgency, :is_public, "pending")';
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':member_id' => $member_id,
            ':requester_name' => $requester_name,
            ':requester_email' => $requester_email,
            ':requester_phone' => $requester_phone,
            ':prayer_title' => $prayer_title,
            ':prayer_content' => $prayer_content,
            ':category' => $category,
            ':urgency' => $urgency,
            ':is_public' => $is_public,
        ]);

        $success = 'Thank you. Your prayer request has been submitted and will be prayed for by our team.';
    } else {
        $error = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Submit a prayer request to Kisii University SDA Church. Share your need and our prayer team will pray for you.">
  <title>Prayer Request | Kisii University SDA Church</title>
  <link rel="stylesheet" href="css/styles.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="icon" type="image/png" href="../images/kisiilogo.png">
  <style>
    :root {
      --primary-green: #0f5a35;
      --primary-green-dark: #0a3d24;
      --primary-green-light: #1a7a4a;
      --secondary-teal: #164f43;
      --accent-gold: #c4a747;
      --gray-light: #f8f9fa;
      --gray-medium: #e9ecef;
      --gray-dark: #6c757d;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #e8edf5 100%);
      min-height: 100vh;
    }

    /* Header Styles */
    .site-header {
      
      background: linear-gradient(#1a1a1a 0%, #1a1a1a 100%);
      color: white;
      position: relative;
      z-index: 100;
    }

    .header-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0.75rem 2rem;
      flex-wrap: wrap;
      gap: 1rem;
    }

    .header-logo a {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      text-decoration: none;
      color: white;
    }

    .sda-logo-img {
      height: 45px;
      filter: brightness(0) invert(1);
    }

    .logo-text {
      font-size: 1.2rem;
      font-weight: 600;
      letter-spacing: -0.3px;
    }

    .giving-pill {
      background: rgba(255,255,255,0.15);
      backdrop-filter: blur(5px);
      border-radius: 40px;
      padding: 0.5rem 1.25rem;
      text-decoration: none;
      color: white;
      font-weight: 500;
      transition: all 0.3s;
    }

    .giving-pill:hover {
      background: var(--accent-gold);
      color: var(--primary-green);
    }

    .header-social-icons {
      display: flex;
      gap: 1rem;
    }

    .header-social-icons a {
      color: white;
      opacity: 0.8;
      transition: opacity 0.3s;
    }

    .header-social-icons a:hover {
      opacity: 1;
    }

    /* Hero Section */
    .hero-section {
      background: linear-gradient(135deg, var(--primary-green) 0%, var(--secondary-teal) 100%);
      color: white;
      padding: 4rem 2rem;
      text-align: center;
      position: relative;
      overflow: hidden;
      margin-top:10px;
      
    }

    .hero-section::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.08) 1%, transparent 1%);
      background-size: 40px 40px;
    }

    .hero-section h1 {
      font-size: 2.5rem;
      margin-bottom: 1rem;
    }

    .hero-section p {
      font-size: 1.1rem;
      opacity: 0.9;
      max-width: 600px;
      margin: 0 auto;
    }

    /* Card Styles */
    .card {
      background: #ffffff;
      border-radius: 26px;
      box-shadow: 0 24px 70px rgba(15, 23, 42, 0.08);
      overflow: hidden;
      margin: -2rem auto 4rem;
      border: 1px solid rgba(15, 23, 42, 0.04);
      max-width: 1100px;
      margin-top:20px;
    }

    .card-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0;
    }

    .card-panel {
      padding: 2.25rem;
    }

    .card-panel--primary {
      background: linear-gradient(135deg, #0f5a35 0%, #164f43 100%);
      color: #ffffff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 1rem;
    }

    .card-panel--primary h2 {
      margin: 0;
      font-size: clamp(1.7rem, 3vw, 2.4rem);
      line-height: 1.08;
    }

    .card-panel--primary p {
      color: #dbe7e0;
      margin: 0;
      line-height: 1.8;
    }

    .card-panel--primary .badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.55rem 0.9rem;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.12);
      font-size: 0.9rem;
      color: #f8fafc;
      width: fit-content;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    /* Prayer Stats */
    .prayer-stats {
      display: flex;
      gap: 1rem;
      margin-top: 1rem;
      flex-wrap: wrap;
    }

    .stat-item {
      background: rgba(255,255,255,0.1);
      border-radius: 12px;
      padding: 0.5rem 1rem;
      text-align: center;
    }

    .stat-number {
      font-size: 1.3rem;
      font-weight: bold;
    }

    /* Form Styles */
    .form-section {
      padding: 2.5rem;
      display: grid;
      gap: 1.5rem;
    }

    .field-group {
      display: grid;
      gap: 0.5rem;
    }

    .field-row {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 1rem;
    }

    label {
      font-size: 0.85rem;
      font-weight: 600;
      color: #111827;
      margin-bottom: 0;
      display: block;
    }

    .required:after {
      content: " *";
      color: #dc3545;
    }

    input, select, textarea {
      width: 100%;
      min-height: 48px;
      border-radius: 14px;
      border: 1px solid #d1d5db;
      background: #f9fafb;
      padding: 0.85rem 1rem;
      font-size: 0.95rem;
      transition: all 0.2s ease;
    }

    textarea {
      min-height: 120px;
      resize: vertical;
    }

    input:focus, select:focus, textarea:focus {
      outline: none;
      border-color: var(--primary-green);
      background: #ffffff;
      box-shadow: 0 0 0 0.15rem rgba(15, 90, 53, 0.15);
    }

    .note {
      color: #6b7280;
      font-size: 0.75rem;
      margin-top: 0.25rem;
    }

    .actions {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      margin-top: 0.75rem;
      align-items: center;
    }

    .btn-primary {
      background: var(--primary-green);
      color: #ffffff;
      border: none;
      border-radius: 999px;
      padding: 0.9rem 1.8rem;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.2s ease;
      width: 100%;
      max-width: 260px;
      font-weight: 600;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 16px 38px rgba(15, 23, 42, 0.16);
      background: var(--primary-green-dark);
    }

    .btn-secondary {
      background: #f3f4f6;
      color: #374151;
      border: 1px solid #e5e7eb;
      border-radius: 999px;
      padding: 0.9rem 1.8rem;
      text-decoration: none;
      transition: all 0.2s ease;
    }

    .btn-secondary:hover {
      background: #e5e7eb;
    }

    .status-box {
      border-radius: 18px;
      padding: 1.3rem 1.35rem;
      border: 1px solid rgba(15, 23, 42, 0.08);
      background: #f8fafc;
      line-height: 1.7;
    }

    .status-success {
      border-color: #d1fae5;
      background: #ecfdf5;
      color: #065f46;
    }

    .status-error {
      border-color: #fecaca;
      background: #fef2f2;
      color: #991b1b;
    }

    /* Checkbox Styles */
    .checkbox-wrapper {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.5rem 0;
    }

    .checkbox-wrapper input {
      width: 20px;
      height: 20px;
      min-height: auto;
      cursor: pointer;
    }

    /* Footer */
    .site-footer {
      background: #1a1a1a;
      color: #999;
      padding: 2rem 0;
      margin-top: 3rem;
    }

    .footer-main {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1.5rem;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 2rem;
    }

    .footer-info h4 {
      color: white;
      font-size: 1rem;
      margin-bottom: 0.5rem;
    }

    .footer-info a {
      color: #999;
      text-decoration: none;
    }

    .footer-info a:hover {
      color: var(--accent-gold);
    }

    .footer-social {
      display: flex;
      gap: 1rem;
      margin-top: 0.5rem;
    }

    .footer-social a {
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
      color: #999;
    }

    .footer-bottom {
      text-align: center;
      padding-top: 1.5rem;
      margin-top: 1.5rem;
      border-top: 1px solid #333;
      font-size: 0.75rem;
    }

    @media (max-width: 840px) {
      .card-grid {
        grid-template-columns: 1fr;
      }
      .field-row {
        grid-template-columns: 1fr;
      }
      .hero-section h1 {
        font-size: 1.8rem;
      }
      .header-top {
        padding: 0.75rem 1rem;
      }
      .logo-text {
        font-size: 0.9rem;
      }
    }
  </style>
</head>
<body>

<!-- Header -->
<header class="site-header" role="banner">
  <div class="header-top">
    <div class="header-logo">
      <a href="index.php" aria-label="Kisii University SDA Church Home">
        <img src="https://sthelenaca.adventistchurch.org/wp-content/themes/acc-themes/base/assets/images/logo-adventist-white.svg" alt="Seventh-day Adventist Logo" class="sda-logo-img">
        <span class="logo-text">Kisii University SDA Church</span>
      </a>
    </div>
    <div class="header-right-top">
      <a href="giving.html" class="giving-pill giving-pill-desktop">❤️ Giving</a>
      <div class="header-social-icons">
        <a href="https://www.facebook.com/KisiiUniversitySDAChurch" target="_blank" rel="noopener" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
        <a href="https://www.youtube.com/@KisiiUniversitySDACHurch" target="_blank" rel="noopener" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
        <a href="https://wa.me/254700000000" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
      </div>
    </div>
  </div>
  <nav class="header-nav" style="background: rgba(0,0,0,0.2); padding: 0.5rem 2rem;">
    <div class="d-flex gap-3">
      <a href="index.php" style="color: white; text-decoration: none;">Home</a>
      <a href="sermons.php" style="color: white; text-decoration: none;">Sermons</a>
      <a href="events.php" style="color: white; text-decoration: none;">Events</a>
      <a href="prayer_wall.php" style="color: white; text-decoration: none;">Prayer Wall</a>
      <a href="request_membership.php" style="color: white; text-decoration: none;">Join Us</a>
      <a href="contact.php" style="color: white; text-decoration: none;">Contact</a>
    </div>
  </nav>
</header>

<!-- Hero Section -->
<section class="hero-section">
  <div class="container">
    <i class="fas fa-praying-hands fa-3x mb-3"></i>
    <h1>Submit a Prayer Request</h1>
    <p>We believe in the power of prayer. Share your burden with us and let our prayer team stand with you.</p>
  </div>
</section>

<main class="page-shell">
  <section class="card">
    <div class="card-grid">
      <div class="card-panel card-panel--primary">
        <span class="badge"><i class="fas fa-hands-praying"></i> Prayer Request</span>
        <h2>You are not alone</h2>
        <p>Your comfort matters. Choose whether your request stays private or is shared publicly on the prayer wall for the wider church family.</p>
        <div class="prayer-stats">
          <div class="stat-item">
            <div class="stat-number"><i class="fas fa-users"></i> 24/7</div>
            <div>Prayer Team</div>
          </div>
          <div class="stat-item">
            <div class="stat-number"><i class="fas fa-clock"></i> Within 24h</div>
            <div>Response Time</div>
          </div>
        </div>
        <p class="note"><i class="fas fa-lock"></i> Fields marked with * are required. If you want a private prayer, uncheck the public option before submitting.</p>
      </div>

      <div class="card-panel form-section">
        <?php if ($success): ?>
          <div class="status-box status-success">
            <i class="fas fa-check-circle fa-2x mb-2"></i>
            <strong>Submitted successfully.</strong>
            <p><?php echo $success; ?></p>
            <a href="prayer_wall.php" class="btn-secondary" style="display: inline-block; margin-top: 1rem;">Visit Prayer Wall <i class="fas fa-arrow-right"></i></a>
          </div>
        <?php else: ?>
          <?php if ($error): ?>
            <div class="status-box status-error">
              <i class="fas fa-exclamation-circle"></i>
              <strong>Check the form below.</strong>
              <p><?php echo $error; ?></p>
            </div>
          <?php endif; ?>

          <form method="POST" autocomplete="off" class="prayer-form" id="prayerForm">
            <div class="field-row">
              <div class="field-group">
                <label for="requester_name" class="required">Your Name</label>
                <input id="requester_name" name="requester_name" type="text" required placeholder="Josephine Mwangi" value="<?php echo isset($_POST['requester_name']) ? htmlspecialchars($_POST['requester_name']) : ''; ?>">
              </div>
              <div class="field-group">
                <label for="requester_email">Email</label>
                <input id="requester_email" name="requester_email" type="email" placeholder="you@example.com" value="<?php echo isset($_POST['requester_email']) ? htmlspecialchars($_POST['requester_email']) : ''; ?>">
              </div>
            </div>

            <div class="field-row">
              <div class="field-group">
                <label for="requester_phone">Phone</label>
                <input id="requester_phone" name="requester_phone" type="tel" placeholder="+254 700 000 000" value="<?php echo isset($_POST['requester_phone']) ? htmlspecialchars($_POST['requester_phone']) : ''; ?>">
              </div>
              <div class="field-group">
                <label for="member_id">Member status</label>
                <select id="member_id" name="member_id">
                  <option value="">Not a member (Guest)</option>
                  <?php foreach ($members as $member): ?>
                    <option value="<?php echo $member['member_id']; ?>"><?php echo htmlspecialchars($member['name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="field-group">
              <label for="prayer_title" class="required">Prayer Title</label>
              <input id="prayer_title" name="prayer_title" type="text" required placeholder="e.g., Healing for my mother, Job interview, Family peace" value="<?php echo isset($_POST['prayer_title']) ? htmlspecialchars($_POST['prayer_title']) : ''; ?>">
            </div>

            <div class="field-group">
              <label for="prayer_content" class="required">Prayer Request</label>
              <textarea id="prayer_content" name="prayer_content" required placeholder="Please share your prayer request in detail. The more specific, the better we can pray..."><?php echo isset($_POST['prayer_content']) ? htmlspecialchars($_POST['prayer_content']) : ''; ?></textarea>
              <p class="note"><i class="fas fa-info-circle"></i> <span id="charCount">0</span> characters</p>
            </div>

            <div class="field-row">
              <div class="field-group">
                <label for="category">Category</label>
                <select id="category" name="category">
                  <option value="Personal">Personal</option>
                  <option value="Family">Family</option>
                  <option value="Health">Health</option>
                  <option value="Financial">Financial</option>
                  <option value="Work">Work</option>
                  <option value="Ministry">Ministry</option>
                  <option value="Community">Community</option>
                  <option value="Other">Other</option>
                </select>
              </div>
              <div class="field-group">
                <label for="urgency">Urgency</label>
                <select id="urgency" name="urgency">
                  <option value="Low">🙏 Low – general prayer</option>
                  <option value="Medium">⏰ Medium – needs prayer soon</option>
                  <option value="High">🔥 High – urgent prayer needed</option>
                  <option value="Critical">⚠️ Critical – immediate prayer</option>
                </select>
              </div>
            </div>

            <div class="field-group">
              <div class="checkbox-wrapper">
                <input type="checkbox" id="is_public" name="is_public" checked>
                <label for="is_public" style="margin-bottom: 0;">Make my request public on the prayer wall</label>
              </div>
              <p class="note">If unchecked, your prayer request remains private and is only viewed by the prayer team and pastors.</p>
            </div>

            <div class="actions">
              <button type="submit" name="submit_prayer" class="btn-primary" id="submitBtn">
                <i class="fas fa-paper-plane"></i> Submit Prayer Request
              </button>
              <a href="prayer_wall.php" class="btn-secondary">
                <i class="fas fa-eye"></i> View Prayer Wall
              </a>
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Encouragement Section -->
  <div class="text-center mt-4 mb-5">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="bg-white rounded-4 p-4 shadow-sm">
          <i class="fas fa-bible fa-2x text-success mb-2"></i>
          <p class="mb-0"><strong>"Do not be anxious about anything, but in every situation, by prayer and petition, with thanksgiving, present your requests to God."</strong><br> — Philippians 4:6</p>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Footer -->
<footer class="site-footer" role="contentinfo">
  <div class="footer-main">
    <div class="footer-info">
      <h4>Kisii University Seventh-day Adventist Church</h4>
      <p><i class="fas fa-map-marker-alt"></i> <a href="https://www.google.com/maps/search/Kisii+University+Kenya" target="_blank">Kisii University Campus, Kisii, Kenya</a><br>
      <i class="fas fa-phone"></i> <a href="tel:+254700000000">+254 700 000 000</a><br>
      <i class="fas fa-envelope"></i> <a href="mailto:info@kisiiuniversitysdachurch.org">info@kisiiuniversitysdachurch.org</a></p>
      <div class="footer-social">
        <a href="https://www.facebook.com/KisiiUniversitySDAChurch" target="_blank"><i class="fab fa-facebook-f"></i> Facebook</a>
        <a href="https://www.youtube.com/@KisiiUniversitySDACHurch" target="_blank"><i class="fab fa-youtube"></i> YouTube</a>
        <a href="https://wa.me/254700000000" target="_blank"><i class="fab fa-whatsapp"></i> WhatsApp</a>
      </div>
    </div>
    <div class="footer-sda-logo">
      <img src="https://sthelenaca.adventistchurch.org/wp-content/themes/acc-themes/base/assets/images/logo-adventist-black.svg" alt="Seventh-day Adventist Church" style="height: 55px;">
    </div>
  </div>
  <div class="footer-bottom">
    <p>Copyright &copy; <?php echo date('Y'); ?> Kisii University Seventh-day Adventist Church.</p>
    <p><a href="#">Privacy Policy</a> &nbsp;|&nbsp; <a href="#">Terms of Use</a></p>
  </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  // Character counter for prayer content
  const prayerContent = document.getElementById('prayer_content');
  const charCount = document.getElementById('charCount');
  
  if (prayerContent && charCount) {
    function updateCharCount() {
      const count = prayerContent.value.length;
      charCount.textContent = count;
      if (count > 500) {
        charCount.style.color = '#dc3545';
      } else {
        charCount.style.color = '#6c757d';
      }
    }
    
    prayerContent.addEventListener('input', updateCharCount);
    updateCharCount();
  }
  
  // Form validation before submit
  document.getElementById('prayerForm')?.addEventListener('submit', function(e) {
    const name = document.getElementById('requester_name').value.trim();
    const title = document.getElementById('prayer_title').value.trim();
    const content = document.getElementById('prayer_content').value.trim();
    
    if (!name || !title || !content) {
      e.preventDefault();
      alert('Please fill in all required fields (Name, Prayer Title, and Prayer Request).');
    }
  });
  
  // Auto-dismiss alerts after 5 seconds
  setTimeout(function() {
    const alerts = document.querySelectorAll('.status-box');
    alerts.forEach(alert => {
      if (alert.classList.contains('status-success')) {
        setTimeout(() => {
          alert.style.opacity = '0';
          setTimeout(() => alert.style.display = 'none', 300);
        }, 5000);
      }
    });
  }, 1000);
</script>

</body>
</html>
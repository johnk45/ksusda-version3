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
  <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⛪</text></svg>">
  <style>
    :root {
      color-scheme: light;
      color: #1a1a1a;
      font-family: 'Inter', system-ui, sans-serif;
      background: #f6f8fb;
    }

    body {
      margin: 0;
      background: linear-gradient(180deg, #e9f7f1 0%, #fcfcfe 100%);
      color: #1f2937;
      min-height: 100vh;
    }

    .page-shell {
      max-width: 1180px;
      margin: 0 auto;
      padding: 2rem;
    }

    .card {
      background: #ffffff;
      border-radius: 26px;
      box-shadow: 0 24px 70px rgba(15, 23, 42, 0.08);
      overflow: hidden;
      margin: 2rem auto 4rem;
      border: 1px solid rgba(15, 23, 42, 0.04);
      margin-bottom:20px;
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
      background: linear-gradient(180deg, #0f5a35 0%, #164f43 100%);
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

    .form-section {
      padding: 2.5rem;
      display: grid;
      gap: 1.5rem;
    }

    .field-group {
      display: grid;
      gap: 1rem;
    }

    .field-row {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 1rem;
    }

    .field-row--stacked {
      grid-template-columns: 1fr;
    }

    label {
      font-size: 0.95rem;
      font-weight: 600;
      color: #111827;
      margin-bottom: 0.55rem;
      display: block;
    }

    input,
    select,
    textarea {
      width: 100%;
      min-height: 48px;
      border-radius: 14px;
      border: 1px solid #d1d5db;
      background: #f9fafb;
      padding: 0.95rem 1rem;
      font-size: 0.95rem;
      transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
      color: #111827;
    }

    textarea {
      min-height: 160px;
      resize: vertical;
      line-height: 1.65;
    }

    input:focus,
    select:focus,
    textarea:focus {
      outline: none;
      border-color: #16a34a;
      background: #ffffff;
      box-shadow: 0 0 0 0.15rem rgba(22, 163, 74, 0.15);
    }

    .note {
      color: #6b7280;
      font-size: 0.92rem;
    }

    .actions {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      margin-top: 0.75rem;
      align-items: center;
    }

    .btn-primary {
      background: #0f5a35;
      color: #ffffff;
      border: none;
      border-radius: 999px;
      padding: 0.95rem 1.5rem;
      font-size: 1rem;
      cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      width: 100%;
      max-width: 260px;
    }

    .btn-primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 16px 38px rgba(15, 23, 42, 0.16);
    }

    .status-box {
      border-radius: 18px;
      padding: 1.3rem 1.35rem;
      border: 1px solid rgba(15, 23, 42, 0.08);
      background: #f8fafc;
      color: #111827;
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

    @media (max-width: 840px) {
      .card-grid {
        grid-template-columns: 1fr;
      }

      .field-row {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <main class="page-shell">
    <section class="card">
      <div class="card-grid">
        <div class="card-panel card-panel--primary">
          <span class="badge">Prayer Request</span>
          <h2>Submit with confidence</h2>
          <p>Your comfort matters. Choose whether your request stays private or is shared publicly on the prayer wall for the wider church family.</p>
          <p class="note">Fields marked with * are required. If you want a private prayer, uncheck the public option before submitting.</p>
        </div>

        <div class="card-panel form-section">
          <?php if ($success): ?>
            <div class="status-box status-success">
              <strong>Submitted successfully.</strong>
              <p><?php echo $success; ?></p>
            </div>
          <?php else: ?>
            <?php if ($error): ?>
              <div class="status-box status-error">
                <strong>Check the form below.</strong>
                <p><?php echo $error; ?></p>
              </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off" class="prayer-form">
              <div class="field-row">
                <div class="field-group">
                  <label for="requester_name">Your Name *</label>
                  <input id="requester_name" name="requester_name" type="text" required placeholder="Josephine Mwangi">
                </div>
                <div class="field-group">
                  <label for="requester_email">Email</label>
                  <input id="requester_email" name="requester_email" type="email" placeholder="you@example.com">
                </div>
              </div>

              <div class="field-row">
                <div class="field-group">
                  <label for="requester_phone">Phone</label>
                  <input id="requester_phone" name="requester_phone" type="tel" placeholder="+254 700 000 000">
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
                <label for="prayer_title">Prayer Title *</label>
                <input id="prayer_title" name="prayer_title" type="text" required placeholder="Healing for my mother">
              </div>

              <div class="field-group">
                <label for="prayer_content">Prayer Request *</label>
                <textarea id="prayer_content" name="prayer_content" required placeholder="Share your prayer request in more detail..."></textarea>
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
                    <option value="Low">Low – general prayer</option>
                    <option value="Medium">Medium – soon</option>
                    <option value="High">High – prayer request needed</option>
                    <option value="Critical">Critical – immediate prayer</option>
                  </select>
                </div>
              </div>

              <div class="field-group">
                <label>
                  <input type="checkbox" id="is_public" name="is_public" checked>
                  Make my request public on the prayer wall
                </label>
                <p class="note">If unchecked, your prayer request remains private and is only viewed by the prayer team.</p>
              </div>

              <div class="actions">
                <button type="submit" name="submit_prayer" class="btn-primary">Submit Prayer Request</button>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
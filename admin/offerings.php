<?php
require_once 'config/database.php';
require_once 'includes/header.php';

if(!isLoggedIn()) redirect('login.php');

$database = new Database();
$db = $database->getConnection();

// Record offering
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['record_offering'])) {
    $receipt_no = 'RCP/' . date('Ymd') . '/' . rand(1000, 9999);
    
    $query = "INSERT INTO offerings (member_id, offering_date, offering_type, amount, 
              payment_method, transaction_id, receipt_no, recorded_by, notes) 
              VALUES (:member_id, :offering_date, :offering_type, :amount, 
              :payment_method, :transaction_id, :receipt_no, :recorded_by, :notes)";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':member_id' => $_POST['member_id'],
        ':offering_date' => $_POST['offering_date'],
        ':offering_type' => $_POST['offering_type'],
        ':amount' => $_POST['amount'],
        ':payment_method' => $_POST['payment_method'],
        ':transaction_id' => $_POST['transaction_id'],
        ':receipt_no' => $receipt_no,
        ':recorded_by' => $_SESSION['user_id'],
        ':notes' => $_POST['notes']
    ]);
    
    $success = "Offering recorded successfully! Receipt No: " . $receipt_no;
}

// Get offerings summary
$query = "SELECT offering_type, SUM(amount) as total FROM offerings 
          WHERE MONTH(offering_date) = MONTH(CURDATE()) 
          GROUP BY offering_type";
$stmt = $db->prepare($query);
$stmt->execute();
$summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent offerings
$query = "SELECT o.*, CONCAT(m.first_name, ' ', m.last_name) as member_name 
          FROM offerings o 
          LEFT JOIN members m ON o.member_id = m.member_id 
          ORDER BY o.created_at DESC LIMIT 20";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_offerings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get members for dropdown
$query = "SELECT member_id, CONCAT(first_name, ' ', last_name) as name FROM members WHERE membership_status = 'Active'";
$stmt = $db->prepare($query);
$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid">
    <h2 class="mb-4">Offerings Management</h2>
    
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Record New Offering</h5>
                </div>
                <div class="card-body">
                    <?php if(isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label>Member</label>
                            <select name="member_id" class="form-control" required>
                                <option value="">Select Member</option>
                                <?php foreach($members as $member): ?>
                                <option value="<?php echo $member['member_id']; ?>">
                                    <?php echo $member['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Offering Date</label>
                            <input type="date" name="offering_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>Offering Type</label>
                            <select name="offering_type" class="form-control" required>
                                <option value="Tithe">Tithe</option>
                                <option value="Sabbath School">Sabbath School</option>
                                <option value="Building Fund">Building Fund</option>
                                <option value="Poor Fund">Poor Fund</option>
                                <option value="Mission">Mission</option>
                                <option value="Thanksgiving">Thanksgiving</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Amount (KES)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Payment Method</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="Cash">Cash</option>
                                <option value="M-Pesa">M-Pesa</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Transaction ID (if any)</label>
                            <input type="text" name="transaction_id" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" name="record_offering" class="btn btn-primary w-100">Record Offering</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5>Monthly Summary (<?php echo date('F Y'); ?>)</h5>
                </div>
                <div class="card-body">
                    <canvas id="offeringsChart" height="200"></canvas>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h5>Recent Offerings</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Member</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Receipt No</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_offerings as $offering): ?>
                            <tr>
                                <td><?php echo $offering['offering_date']; ?></td>
                                <td><?php echo $offering['member_name'] ?? 'Anonymous'; ?></td>
                                <td><?php echo $offering['offering_type']; ?></td>
                                <td>KES <?php echo number_format($offering['amount'], 2); ?></td>
                                <td><?php echo $offering['receipt_no']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Prepare chart data
const offeringTypes = <?php echo json_encode(array_column($summary, 'offering_type')); ?>;
const offeringTotals = <?php echo json_encode(array_column($summary, 'total')); ?>;

const ctx = document.getElementById('offeringsChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: offeringTypes,
        datasets: [{
            label: 'Amount (KES)',
            data: offeringTotals,
            backgroundColor: 'rgba(54, 162, 235, 0.5)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'KES ' + value.toLocaleString();
                    }
                }
            }
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
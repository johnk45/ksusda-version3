<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$event_id = $_GET['event_id'];

$query = "SELECT er.*, CONCAT(m.first_name, ' ', m.last_name) as member_name, 
          m.phone, m.email, m.membership_no
          FROM event_registrations er
          JOIN members m ON er.member_id = m.member_id
          WHERE er.event_id = :event_id
          ORDER BY er.registration_date DESC";
$stmt = $db->prepare($query);
$stmt->execute([':event_id' => $event_id]);
$registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

if(count($registrations) > 0):
    foreach($registrations as $reg):
?>
<tr>
    <td>
        <?php echo htmlspecialchars($reg['member_name']); ?>
        <br>
        <small class="text-muted"><?php echo $reg['membership_no']; ?></small>
    </td>
    <td><?php echo $reg['phone']; ?></td>
    <td><?php echo date('M d, Y', strtotime($reg['registration_date'])); ?></td>
    <td>
        <span class="badge bg-<?php echo $reg['attendance_status'] == 'Checked In' ? 'success' : 'warning'; ?>">
            <?php echo $reg['attendance_status']; ?>
        </span>
    </td>
    <td>
        <?php if($reg['attendance_status'] != 'Checked In'): ?>
        <form method="POST" style="display: inline;">
            <input type="hidden" name="registration_id" value="<?php echo $reg['registration_id']; ?>">
            <button type="submit" name="mark_attendance" class="btn btn-sm btn-success">
                <i class="fas fa-check"></i> Check In
            </button>
        </form>
        <?php endif; ?>
    </td>
</tr>
<?php
    endforeach;
else:
?>
<tr>
    <td colspan="5" class="text-center">No registrations yet</td>
</tr>
<?php endif; ?>
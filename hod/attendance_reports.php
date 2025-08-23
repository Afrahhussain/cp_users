<?php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$dept = $_SESSION['department'] ?? '';
$attendance = [];
$classes = [];
$class_id = $_GET['class_id'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

// fetch all classes of HOD department
$stmt = $conn->prepare("SELECT id, department, year, section FROM class_allotments WHERE department=?");
$stmt->bind_param("s", $dept);
$stmt->execute();
$classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// fetch attendance
if ($class_id && $from && $to) {
    $sql = "
      SELECT a.date, u.full_name, u.email, a.status
      FROM attendance a
      JOIN users u ON u.id = a.student_id
      WHERE a.class_id=? AND a.date BETWEEN ? AND ?
      ORDER BY a.date, u.full_name
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $class_id, $from, $to);
    $stmt->execute();
    $attendance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<main class="main" role="main">

  <!-- Page Title -->
  <div class="page-title" style="margin-bottom:12px">
    <h2 style="font-size:20px;font-weight:700;color:#1e293b">Attendance Reports</h2>
  </div>

  <!-- Filters -->
  <div class="card" style="padding:12px;margin-bottom:14px">
    <form method="get" style="display:flex;flex-wrap:wrap;gap:10px;align-items:end;margin:0">
      
      <div style="flex:1;min-width:200px">
        <label style="font-size:13px;color:#374151">Class</label>
        <select name="class_id" class="input" required
          style="padding:7px 10px;border:1px solid #d1d5db;border-radius:6px;width:100%">
          <option value="">Select class</option>
          <?php foreach($classes as $c): ?>
            <option value="<?= $c['id']; ?>" <?= $c['id']==$class_id?'selected':''; ?>>
              <?= $c['department']." • ".$c['year']." • ".$c['section']; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label style="font-size:13px;color:#374151">From</label>
        <input type="date" name="from" value="<?= htmlspecialchars($from); ?>" required
          style="padding:7px 10px;border:1px solid #d1d5db;border-radius:6px">
      </div>

      <div>
        <label style="font-size:13px;color:#374151">To</label>
        <input type="date" name="to" value="<?= htmlspecialchars($to); ?>" required
          style="padding:7px 10px;border:1px solid #d1d5db;border-radius:6px">
      </div>

      <div style="display:flex;gap:8px;align-items:center">
        <button type="submit"
          style="padding:7px 14px;border:1px solid #3b82f6;background:#3b82f6;
                 border-radius:6px;cursor:pointer;font-size:14px;color:#fff">
          Load Report
        </button>
        <?php if ($attendance): ?>
        <a href="export_attendance.php?class_id=<?= $class_id ?>&from=<?= $from ?>&to=<?= $to ?>&type=csv"
          style="padding:7px 14px;border:1px solid #d1d5db;background:#fff;
                 border-radius:6px;font-size:14px;text-decoration:none;color:#111">
          Export CSV
        </a>
        <a href="export_attendance.php?class_id=<?= $class_id ?>&from=<?= $from ?>&to=<?= $to ?>&type=pdf"
          style="padding:7px 14px;border:1px solid #d1d5db;background:#fff;
                 border-radius:6px;font-size:14px;text-decoration:none;color:#111">
          Export PDF
        </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Report -->
  <?php if ($attendance): ?>
    <div class="card" style="padding:0">
      <table class="table" style="width:100%;border-collapse:collapse">
        <thead style="background:#f9fafb;border-bottom:2px solid #e5e7eb">
          <tr>
            <th style="padding:10px;font-size:14px;text-align:left">Date</th>
            <th style="padding:10px;font-size:14px;text-align:left">Student</th>
            <th style="padding:10px;font-size:14px;text-align:left">Email</th>
            <th style="padding:10px;font-size:14px;text-align:left">Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($attendance as $row): ?>
            <tr style="border-bottom:1px solid #f1f5f9">
              <td style="padding:10px;font-size:14px"><?= htmlspecialchars($row['date']); ?></td>
              <td style="padding:10px;font-size:14px"><?= htmlspecialchars($row['full_name']); ?></td>
              <td style="padding:10px;font-size:14px"><?= htmlspecialchars($row['email']); ?></td>
              <td style="padding:10px;font-size:14px">
                <?php 
                  $status = strtolower($row['status']);
                  if ($status === 'present') {
                    echo '<span style="padding:3px 8px;border-radius:10px;background:#dcfce7;color:#166534;font-size:12px;font-weight:500">Present</span>';
                  } elseif ($status === 'absent') {
                    echo '<span style="padding:3px 8px;border-radius:10px;background:#fee2e2;color:#991b1b;font-size:12px;font-weight:500">Absent</span>';
                  } elseif ($status === 'late') {
                    echo '<span style="padding:3px 8px;border-radius:10px;background:#fef9c3;color:#854d0e;font-size:12px;font-weight:500">Late</span>';
                  } else {
                    echo htmlspecialchars($row['status']);
                  }
                ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php elseif ($class_id): ?>
    <div class="card" style="padding:14px;color:#6b7280;font-size:14px">
      No attendance records found for this range.
    </div>
  <?php endif; ?>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

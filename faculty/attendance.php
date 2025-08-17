<?php
// faculty/attendance.php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$faculty_id = $_SESSION['user_id'] ?? 0;
$errors = []; $messages = [];

/* fetch my classes */
$classes = [];
$res = $conn->prepare("SELECT id, department, year, section FROM class_allotments WHERE faculty_id=? ORDER BY department, year, section");
$res->bind_param("i", $faculty_id);
$res->execute();
$classes = $res->get_result()->fetch_all(MYSQLI_ASSOC);
$res->close();

/* selected class + date */
$class_id = (int)($_GET['class_id'] ?? $_POST['class_id'] ?? 0);
$att_date = $_GET['date'] ?? $_POST['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $att_date)) $att_date = date('Y-m-d');

/* load students for selected class */
$students = [];
if ($class_id) {
  $meta = $conn->prepare("SELECT department,year,section FROM class_allotments WHERE id=? AND faculty_id=? LIMIT 1");
  $meta->bind_param("ii", $class_id, $faculty_id);
  $meta->execute();
  $classRow = $meta->get_result()->fetch_assoc();
  $meta->close();

  if ($classRow) {
    $q = $conn->prepare("
      SELECT id, full_name, email
      FROM users
      WHERE role='student' AND department=? AND year=? AND section=? AND (status='approved' OR status IS NULL)
      ORDER BY full_name
    ");
    $q->bind_param("sss", $classRow['department'], $classRow['year'], $classRow['section']);
    $q->execute();
    $students = $q->get_result()->fetch_all(MYSQLI_ASSOC);
    $q->close();
  } else {
    $errors[] = "Invalid class selection.";
  }
}

/* save attendance */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $class_id) {
  // Remove existing records for that date/class/faculty to allow re-submit
  $del = $conn->prepare("DELETE FROM attendance WHERE faculty_id=? AND class_id=? AND date=?");
  $del->bind_param("iis", $faculty_id, $class_id, $att_date);
  $del->execute();
  $del->close();

  $present_ids = array_map('intval', $_POST['present'] ?? []);
  $ins = $conn->prepare("INSERT INTO attendance (faculty_id,class_id,student_id,date,status) VALUES (?,?,?,?,?)");
  foreach ($students as $stu) {
    $sid = (int)$stu['id'];
    $status = in_array($sid, $present_ids, true) ? 'Present' : 'Absent';
    $ins->bind_param("iiiss", $faculty_id, $class_id, $sid, $att_date, $status);
    $ins->execute();
  }
  $ins->close();
  $messages[] = "Attendance saved for $att_date.";
}
?>
<main class="main" role="main">
  <div class="page-title" style="font-weight:700;font-size:20px;margin-bottom:8px">Mark Attendance</div>

  <?php foreach ($errors as $e): ?>
    <div class="card" style="background:#fff3f2;color:#9C1C12;margin-bottom:10px"><?= htmlspecialchars($e); ?></div>
  <?php endforeach; ?>
  <?php foreach ($messages as $m): ?>
    <div class="card" style="background:#ecfdf5;color:#065f46;margin-bottom:10px"><?= htmlspecialchars($m); ?></div>
  <?php endforeach; ?>

  <div class="card">
    <form method="get" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;align-items:end">
      <div>
        <div class="label">Class</div>
        <select name="class_id" class="input" required>
          <option value="">Select class</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?= (int)$c['id']; ?>" <?= $class_id==$c['id']?'selected':''; ?>>
              <?= htmlspecialchars($c['department']." • ".$c['year']." • ".$c['section']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <div class="label">Date</div>
        <input type="date" name="date" class="input" value="<?= htmlspecialchars($att_date); ?>" required>
      </div>
      <div>
        <button class="btn btn-primary" type="submit"><i class="fas fa-arrow-right" style="margin-right:6px"></i>Load</button>
      </div>
    </form>
  </div>

  <?php if ($class_id && $students): ?>
    <form method="post" class="card" style="margin-top:12px">
      <input type="hidden" name="class_id" value="<?= (int)$class_id; ?>">
      <input type="hidden" name="date" value="<?= htmlspecialchars($att_date); ?>">

      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
        <div><strong>Students (<?= count($students); ?>)</strong></div>
        <div class="small">Toggle all:
          <input type="checkbox" id="toggleAll" checked style="transform:translateY(2px)">
        </div>
      </div>

      <table class="table">
        <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Present</th></tr></thead>
        <tbody>
          <?php $i=1; foreach ($students as $stu): ?>
            <tr>
              <td><?= $i++; ?></td>
              <td><?= htmlspecialchars($stu['full_name']); ?></td>
              <td><?= htmlspecialchars($stu['email']); ?></td>
              <td><input type="checkbox" name="present[]" value="<?= (int)$stu['id']; ?>" checked></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div style="margin-top:12px">
        <button class="btn btn-ghost" type="submit"><i class="fas fa-save" style="margin-right:6px"></i>Save Attendance</button>
      </div>
    </form>

    <script>
      // simple toggle-all for present checkboxes
      document.getElementById('toggleAll')?.addEventListener('change', function(){
        document.querySelectorAll('input[name="present[]"]').forEach(cb => { cb.checked = !!this.checked; });
      });
    </script>
  <?php elseif ($class_id): ?>
    <div class="card" style="margin-top:12px">No students in this class.</div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>

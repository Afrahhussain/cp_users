<?php
// faculty/dashboard.php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$user_id = $_SESSION['user_id'] ?? 0;
$name    = $_SESSION['full_name'] ?? 'Faculty';

/* ---------- quick stats ---------- */
$classesCount = 0; 
$studentsCount = 0;

// how many classes allotted to this faculty
$stmt = $conn->prepare("SELECT COUNT(*) c FROM class_allotments WHERE faculty_id=?");
$stmt->bind_param("i", $user_id); 
$stmt->execute();
$classesCount = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

// total students in the system (not just my class)
$res = $conn->query("SELECT COUNT(*) c FROM users WHERE role='student'");
$studentsCount = (int)($res->fetch_assoc()['c'] ?? 0);

/* ---------- search ---------- */
$search = trim($_GET['q'] ?? '');
$found  = null;
$foundMarks = []; 
$foundAttendance = [];

if ($search !== '') {
  // global search: any student
  $sql = "
    SELECT u.*
    FROM users u
    WHERE u.role='student'
      AND (
          u.id = ? OR
          u.full_name LIKE CONCAT('%', ?, '%') OR
          u.email LIKE CONCAT('%', ?, '%')
      )
    LIMIT 1
  ";
  $idAsInt = ctype_digit($search) ? (int)$search : 0;
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("iss", $idAsInt, $search, $search);
  $stmt->execute();
  $found = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if ($found) {
      // marks added by me for this student
      $m = $conn->prepare("
        SELECT exam_type, subject, marks_obtained, total_marks, created_at
        FROM marks
        WHERE student_id=? AND faculty_id=?
        ORDER BY created_at DESC LIMIT 6
      ");
      $m->bind_param("ii", $found['id'], $user_id);
      $m->execute();
      $foundMarks = $m->get_result()->fetch_all(MYSQLI_ASSOC);
      $m->close();

      // attendance added by me for this student
      $a = $conn->prepare("
        SELECT date, status
        FROM attendance
        WHERE student_id=? AND faculty_id=?
        ORDER BY date DESC LIMIT 10
      ");
      $a->bind_param("ii", $found['id'], $user_id);
      $a->execute();
      $foundAttendance = $a->get_result()->fetch_all(MYSQLI_ASSOC);
      $a->close();
  }
}
?>
<main class="main" role="main">

  <!-- Top stats -->
  <div class="stats-grid">
    <div class="card"><div class="label">Assigned Classes</div><div class="value"><?= $classesCount; ?></div></div>
    <div class="card"><div class="label">Total Students</div><div class="value"><?= $studentsCount; ?></div></div>
  </div>

  <!-- Search -->
  <div class="card" style="margin-top:16px">
    <form method="get" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <input type="text" name="q" value="<?= htmlspecialchars($search); ?>" placeholder="Search student by ID, name, or email…" class="input" style="flex:1;min-width:260px">
      <button class="btn btn-primary" type="submit"><i class="fas fa-search" style="margin-right:6px"></i>Search</button>
    </form>
    <?php if ($search !== '' && !$found): ?>
      <div class="small" style="margin-top:10px;color:#9CA3AF">No matching student found.</div>
    <?php endif; ?>
  </div>

  <!-- Search Result -->
  <?php if ($found): ?>
    <div class="card" style="margin-top:12px">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
        <div><div class="label">Name</div><div class="value" style="font-size:18px"><?= htmlspecialchars($found['full_name']); ?></div></div>
        <div><div class="label">Email</div><div><?= htmlspecialchars($found['email']); ?></div></div>
        <div><div class="label">Dept / Year / Sec</div><div><?= htmlspecialchars($found['department'] . " • " . $found['year'] . " • " . $found['section']); ?></div></div>
        <div><div class="label">User ID</div><div>#<?= (int)$found['id']; ?></div></div>
      </div>

      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:14px;margin-top:18px">
        <div class="card" style="box-shadow:none;border:1px solid #eef2f7">
          <div class="label">Recent Marks (by you)</div>
          <?php if (!$foundMarks): ?>
            <div class="small">No marks entered by you yet.</div>
          <?php else: ?>
            <table class="table" style="margin-top:8px">
              <thead><tr><th>Exam</th><th>Subject</th><th>Marks</th><th>Total</th><th>Date</th></tr></thead>
              <tbody>
                <?php foreach ($foundMarks as $r): ?>
                  <tr>
                    <td><?= htmlspecialchars($r['exam_type']); ?></td>
                    <td><?= htmlspecialchars($r['subject']); ?></td>
                    <td><?= htmlspecialchars($r['marks_obtained']); ?></td>
                    <td><?= htmlspecialchars($r['total_marks']); ?></td>
                    <td><?= htmlspecialchars($r['created_at']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>

        <div class="card" style="box-shadow:none;border:1px solid #eef2f7">
          <div class="label">Recent Attendance (by you)</div>
          <?php if (!$foundAttendance): ?>
            <div class="small">No attendance marked by you yet.</div>
          <?php else: ?>
            <table class="table" style="margin-top:8px">
              <thead><tr><th>Date</th><th>Status</th></tr></thead>
              <tbody>
                <?php foreach ($foundAttendance as $r): ?>
                  <tr>
                    <td><?= htmlspecialchars($r['date']); ?></td>
                    <td><?= htmlspecialchars($r['status']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- Welcome -->
  <div class="quick-actions" style="margin-top:16px">
    <h3>Welcome, <?= htmlspecialchars($name); ?> 👋</h3>
    <p>Use the sidebar to navigate through your classes, attendance, and marks.</p>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>

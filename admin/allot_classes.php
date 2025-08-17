<?php 
// admin/allot_classes.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../config.php';

// admin guard
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$errors = [];
$messages = [];

// Departments and Years
$DEPTS = ['CSE','EEE','ECE'];
$YEARS = [1,2,3];
$SECTIONS = ['A','B','C'];

// handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['allot_class'])) {
    $department = strtoupper(trim($_POST['department'] ?? ''));
    $year = intval($_POST['year'] ?? 0);
    $section = strtoupper(trim($_POST['section'] ?? ''));
    $subject = trim($_POST['subject'] ?? '');
    $faculty_id = intval($_POST['faculty_id'] ?? 0);

    if (!in_array($department, $DEPTS, true) || !in_array($year, $YEARS, true) || !in_array($section, $SECTIONS, true) || $subject === '' || $faculty_id <= 0) {
        $errors[] = "Please fill all fields correctly.";
    } else {
        // check faculty exists
        $chk = $conn->prepare("SELECT id FROM users WHERE id=? AND role='faculty' LIMIT 1");
        $chk->bind_param("i", $faculty_id);
        $chk->execute(); $chk->store_result();
        if ($chk->num_rows === 0) {
            $errors[] = "Selected faculty not found.";
        }
        $chk->close();

        if (!$errors) {
            // check duplicate
            $dup = $conn->prepare("SELECT id FROM class_allotments WHERE faculty_id=? AND department=? AND year=? AND section=? AND subject=?");
            $dup->bind_param("isiss", $faculty_id, $department, $year, $section, $subject);
            $dup->execute(); $dup->store_result();
            if ($dup->num_rows > 0) {
                $errors[] = "This class is already allotted to the selected faculty.";
            }
            $dup->close();

            if (!$errors) {
                $stmt = $conn->prepare("INSERT INTO class_allotments (department,year,section,subject,faculty_id,created_at) VALUES (?,?,?,?,?,NOW())");
                if ($stmt) {
                    $stmt->bind_param("sissi", $department, $year, $section, $subject, $faculty_id);
                    if ($stmt->execute()) {
                        $messages[] = "Class allotted successfully.";
                    } else {
                        $errors[] = "DB insert error: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $errors[] = "DB prepare error: " . $conn->error;
                }
            }
        }
    }
}

// fetch faculty list
$facultyList = [];
$res = $conn->query("SELECT id, full_name, department FROM users WHERE role='faculty' ORDER BY department, full_name");
while ($row = $res->fetch_assoc()) {
    $facultyList[] = $row;
}

// fetch existing allotments
$allotments = [];
$res = $conn->query("
    SELECT ca.id, ca.department, ca.year, ca.section, ca.subject, ca.created_at, 
           u.full_name AS faculty_name, u.department AS fac_dept
    FROM class_allotments ca
    JOIN users u ON ca.faculty_id=u.id
    ORDER BY ca.created_at DESC
");
while ($row = $res->fetch_assoc()) {
    $allotments[] = $row;
}

// includes
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<main class="main" role="main">
  <div class="page-title" style="font-weight:700;font-size:20px;margin-bottom:12px">Class Allotment</div>

  <?php foreach ($errors as $e): ?>
    <div class="card" style="background:#fff3f2;color:#9C1C12;margin-bottom:14px;padding:12px;border-radius:8px"><?= htmlspecialchars($e); ?></div>
  <?php endforeach; ?>
  <?php foreach ($messages as $m): ?>
    <div class="card" style="background:#ecfdf5;color:#064e3b;margin-bottom:14px;padding:12px;border-radius:8px"><?= htmlspecialchars($m); ?></div>
  <?php endforeach; ?>

  <!-- Allot Form -->
  <div class="card" style="padding:16px">
    <h3 style="margin-bottom:8px">Allot Class</h3>
    <form method="POST">
      <div class="form-row">
        <select name="department" required class="input">
          <option value="">Department</option>
          <?php foreach ($DEPTS as $d): ?>
            <option value="<?= $d ?>"><?= $d ?></option>
          <?php endforeach; ?>
        </select>
        <select name="year" required class="input">
          <option value="">Year</option>
          <?php foreach ($YEARS as $y): ?>
            <option value="<?= $y ?>">Year <?= $y ?></option>
          <?php endforeach; ?>
        </select>
        <select name="section" required class="input">
          <option value="">Section</option>
          <?php foreach ($SECTIONS as $s): ?>
            <option value="<?= $s ?>"><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-row" style="margin-top:12px">
        <input name="subject" placeholder="Subject name" required class="input">
        <select name="faculty_id" required class="input">
          <option value="">Select Faculty</option>
          <?php foreach ($facultyList as $f): ?>
            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['full_name']) ?> (<?= $f['department'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="margin-top:12px">
        <button type="submit" name="allot_class" class="btn btn-primary"><i class="fas fa-plus" style="margin-right:6px"></i> Allot</button>
      </div>
    </form>
  </div>

  <!-- Existing Allotments -->
  <div class="card" style="margin-top:20px;padding:16px">
    <h3 style="margin-bottom:8px">Existing Allotments</h3>
    <?php if (!$allotments): ?>
      <div class="small">No allotments yet.</div>
    <?php else: ?>
      <table class="table" style="margin-top:10px">
        <thead>
          <tr>
            <th>Faculty</th><th>Dept</th><th>Year</th><th>Section</th><th>Subject</th><th>Created At</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allotments as $a): ?>
            <tr>
              <td><?= htmlspecialchars($a['faculty_name']); ?></td>
              <td><?= htmlspecialchars($a['department']); ?></td>
              <td><?= htmlspecialchars($a['year']); ?></td>
              <td><?= htmlspecialchars($a['section']); ?></td>
              <td><?= htmlspecialchars($a['subject']); ?></td>
              <td><?= htmlspecialchars($a['created_at']); ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

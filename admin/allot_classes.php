<?php 
// admin/allot_classes.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// include config
if (file_exists(__DIR__ . '/../config.php')) {
    include __DIR__ . '/../config.php';
} else {
    die("Missing config.php in project root. Please put config.php one level above admin/ (admin/../config.php).");
}

// admin guard
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$errors = [];
$messages = [];

// Departments and Years
$DEPTS = ['CSE','EEE','ECE'];
$YEARS = [1,2,3,4];
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
            // insert allotment
            $stmt = $conn->prepare("INSERT INTO class_allotments (department,year,section,subject,faculty_id,created_at) VALUES (?,?,?,?,?,NOW())");
            if ($stmt === false) {
                $errors[] = "DB prepare error: " . $conn->error;
            } else {
                $stmt->bind_param("sissi", $department, $year, $section, $subject, $faculty_id);
                if ($stmt->execute()) {
                    $messages[] = "Class allotted successfully.";
                } else {
                    $errors[] = "DB insert error: " . $stmt->error;
                }
                $stmt->close();
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

// include header/sidebar/footer if available, otherwise render minimal UI like faculty file
$hasHeaderInclude = file_exists(__DIR__ . '/includes/header.php');
$hasSidebarInclude = file_exists(__DIR__ . '/includes/sidebar.php');
$hasFooterInclude = file_exists(__DIR__ . '/includes/footer.php');

if ($hasHeaderInclude) {
    include __DIR__ . '/includes/header.php';
} else {
    ?>
    <!doctype html>
    <html lang="en"><head><meta charset="utf-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/><title>Class Allotment - Admin</title><link rel="stylesheet" href="assets/styles.css"><script defer src="assets/scripts.js"></script><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"></head><body>
    <header class="topbar" style="position:fixed;left:0;right:0;top:0;height:64px;background:#fff;border-bottom:1px solid #eef2f7;display:flex;align-items:center;padding:0 18px;z-index:1000">
      <div class="topbar-left">
        <button id="hamburger" class="hamburger" style="font-size:18px;padding:6px 10px;border-radius:6px;border:1px solid #eee;background:#fafafa;cursor:pointer"><i class="fas fa-bars"></i></button>
        <a class="brand" href="dashboard.php" style="margin-left:12px;font-weight:700;color:#0f172a">College Portal</a>
      </div>
      <div class="topbar-right" style="display:flex;align-items:center;gap:12px">
        <span class="role-pill" style="background:#eef2ff;color:#1e3a8a;padding:6px 10px;border-radius:999px;font-weight:600">Admin</span>
        <a href="../logout.php" style="color:#6b7280"><i class="fas fa-sign-out-alt"></i></a>
      </div>
    </header>
    <?php
}
if ($hasSidebarInclude) include __DIR__ . '/includes/sidebar.php';
else {
    ?>
    <aside id="sidebar" style="position:fixed;top:64px;left:0;width:260px;height:calc(100vh - 64px);background:#0f172a;color:#fff;padding:16px;">
      <div style="font-weight:700;margin-bottom:12px">Admin Panel</div>
      <nav>
        <a href="dashboard.php" style="display:block;color:#fff;padding:8px 0">Dashboard</a>
        <a href="manage_users.php" style="display:block;color:#fff;padding:8px 0">Manage Users</a>
        <a href="upload_students.php" style="display:block;color:#fff;padding:8px 0">Upload Students</a>
        <a href="upload_faculty.php" style="display:block;color:#fff;padding:8px 0">Upload Faculty</a>
        <a href="allot_classes.php" style="display:block;color:#fff;padding:8px 0">Class Allotment</a>
      </nav>
    </aside>
    <?php
}
?>

<main class="main" role="main" style="margin-left:260px;margin-top:84px;padding:22px;min-height:calc(100vh - 84px);">
  <div class="page-title" style="font-weight:700;font-size:20px;margin-bottom:12px">Class Allotment</div>

  <?php foreach ($errors as $e): ?>
    <div class="card" style="background:#fff3f2;color:#9C1C12;margin-bottom:14px;padding:12px;border-radius:8px"><?= htmlspecialchars($e); ?></div>
  <?php endforeach; ?>
  <?php foreach ($messages as $m): ?>
    <div class="card" style="background:#ecfdf5;color:#064e3b;margin-bottom:14px;padding:12px;border-radius:8px"><?= htmlspecialchars($m); ?></div>
  <?php endforeach; ?>

  <div class="card" style="padding:16px">
    <h3 style="margin-bottom:8px">Allot Class</h3>
    <form method="POST" class="form">
      <div class="form-row" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px">
        <select name="department" required style="padding:10px;border-radius:8px;border:1px solid #e6eef6">
          <option value="">Department</option>
          <?php foreach ($DEPTS as $d): ?>
            <option value="<?= $d ?>"><?= $d ?></option>
          <?php endforeach; ?>
        </select>
        <select name="year" required style="padding:10px;border-radius:8px;border:1px solid #e6eef6">
          <option value="">Year</option>
          <?php foreach ($YEARS as $y): ?>
            <option value="<?= $y ?>">Year <?= $y ?></option>
          <?php endforeach; ?>
        </select>
        <select name="section" required style="padding:10px;border-radius:8px;border:1px solid #e6eef6">
          <option value="">Section</option>
          <?php foreach ($SECTIONS as $s): ?>
            <option value="<?= $s ?>"><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-row" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-top:12px">
        <input name="subject" placeholder="Subject name" required style="padding:10px;border-radius:8px;border:1px solid #e6eef6">
        <select name="faculty_id" required style="padding:10px;border-radius:8px;border:1px solid #e6eef6">
          <option value="">Select Faculty</option>
          <?php foreach ($facultyList as $f): ?>
            <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['full_name']) ?> (<?= $f['department'] ?>)</option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="margin-top:12px">
        <button type="submit" name="allot_class" class="btn btn-success" style="padding:8px 12px;background:#14B8A6;color:#fff;border-radius:8px;border:0">Allot</button>
      </div>
    </form>
  </div>
</main>

<?php
if ($hasFooterInclude) include __DIR__ . '/includes/footer.php';
else {
    ?><footer style="padding:12px;text-align:center;color:#64748b;margin-top:12px">© <?= date("Y"); ?> College Portal</footer></body></html><?php
}
?>

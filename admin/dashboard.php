<?php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

// fetch real counts
$counts = [
  'total' => (int)($conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'] ?? 0),
  'students' => (int)($conn->query("SELECT COUNT(*) c FROM users WHERE role='student'")->fetch_assoc()['c'] ?? 0),
  'faculty' => (int)($conn->query("SELECT COUNT(*) c FROM users WHERE role='faculty'")->fetch_assoc()['c'] ?? 0),
  'pending' => (int)($conn->query("SELECT COUNT(*) c FROM users WHERE status='pending'")->fetch_assoc()['c'] ?? 0),
];
?>
<main class="main" role="main">
  <div class="stats-grid">
    <div class="card"><div class="label">Total Users</div><div class="value"><?= $counts['total']; ?></div></div>
    <div class="card"><div class="label">Students</div><div class="value"><?= $counts['students']; ?></div></div>
    <div class="card"><div class="label">Faculty</div><div class="value"><?= $counts['faculty']; ?></div></div>
    <div class="card"><div class="label">Pending Approvals</div><div class="value"><?= $counts['pending']; ?></div></div>
  </div>

  <div class="quick-actions">
    <div style="display:flex;align-items:center;justify-content:space-between">
      <div style="font-weight:700">Quick actions</div>
    </div>
    <div class="actions" style="margin-top:12px">
      <a class="btn btn-primary" href="manage_users.php">Manage Users</a>
      <a class="btn btn-ghost" href="upload_students.php">Upload Students</a>
      <a class="btn btn-primary" href="upload_faculty.php">Upload Faculty</a>
    </div>
  </div>

  <div class="footer"></div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

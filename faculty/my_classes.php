<?php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$user_id = $_SESSION['user_id'] ?? 0;

$rows = [];
$stmt = $conn->prepare("SELECT id, department, year, section, subject, created_at
                        FROM class_allotments
                        WHERE faculty_id=?
                        ORDER BY department, year, section, subject");
if ($stmt){
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $res = $stmt->get_result();
  while($r = $res->fetch_assoc()){ $rows[] = $r; }
  $stmt->close();
}
?>
<main class="main" role="main">
  <div class="card" style="margin-bottom:16px">
    <h2 style="margin:0">My Classes</h2>
    <p class="small" style="margin:6px 0 0 0">All classes allotted to you.</p>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>#</th>
          <th>Department</th>
          <th>Year</th>
          <th>Section</th>
          <th>Subject</th>
          <th>Allotted On</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="6" class="small">No classes allotted yet.</td></tr>
      <?php else: ?>
        <?php $i=1; foreach($rows as $row): ?>
          <tr>
            <td><?= $i++; ?></td>
            <td><?= htmlspecialchars($row['department']); ?></td>
            <td><?= (int)$row['year']; ?></td>
            <td><?= htmlspecialchars($row['section']); ?></td>
            <td><?= htmlspecialchars($row['subject']); ?></td>
            <td><?= htmlspecialchars($row['created_at']); ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>

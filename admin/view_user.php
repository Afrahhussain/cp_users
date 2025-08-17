<?php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

// Search/filter
$q = trim($_GET['q'] ?? '');
$role = $_GET['role'] ?? '';

$where = "WHERE 1=1";
$params = []; $types = '';
if ($q !== '') { 
  $where .= " AND (full_name LIKE CONCAT('%',?,'%') OR email LIKE CONCAT('%',?,'%'))"; 
  $params[]=$q; $params[]=$q; $types .= 'ss'; 
}
if ($role !== '') { 
  $where .= " AND role=?"; 
  $params[]=$role; $types .= 's'; 
}

$sql = "SELECT id, full_name, email, role, department, year, section, status 
        FROM users $where ORDER BY created_at DESC LIMIT 500";
$stmt = $conn->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute(); 
$res = $stmt->get_result();

$id = (int)($_GET['id'] ?? 0);
$user = null; 
$allots = [];

if ($id > 0) {
  $ps = $conn->prepare("SELECT id, full_name, email, role, department, year, section, status, created_at 
                        FROM users WHERE id=? LIMIT 1");
  $ps->bind_param("i",$id); 
  $ps->execute(); 
  $user = $ps->get_result()->fetch_assoc();
  $ps->close();

  if ($user) {
    // If faculty/HOD/class_incharge -> fetch allotted classes
    if (in_array($user['role'], ['faculty','class_incharge','hod'])) {
      $qs = $conn->prepare("SELECT department, year, section, subject 
                            FROM class_allotments 
                            WHERE faculty_id=? ORDER BY department,year,section");
      $qs->bind_param("i",$id); 
      $qs->execute(); 
      $rr = $qs->get_result();
      while ($r = $rr->fetch_assoc()) {
        $allots[] = $r['department'] . ' / Year ' . $r['year'] . ' / Section ' . $r['section'] . ' / ' . $r['subject'];
      }
      $qs->close();
    }
  }
}
?>
<main class="main">
  <?php if ($id > 0 && $user): ?>
    <h2>Profile: <?= htmlspecialchars($user['full_name']); ?></h2>
    <div class="card">
      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
        <div><div class="small">Full name</div><div style="font-weight:700"><?= htmlspecialchars($user['full_name']); ?></div></div>
        <div><div class="small">Email</div><div style="font-weight:700"><?= htmlspecialchars($user['email']); ?></div></div>
        <div><div class="small">Role</div><div style="font-weight:700"><?= htmlspecialchars(ucfirst($user['role'])); ?></div></div>
        <div><div class="small">Status</div><div style="font-weight:700"><?= htmlspecialchars($user['status']); ?></div></div>
        <div><div class="small">Department</div><div style="font-weight:700"><?= htmlspecialchars($user['department'] ?: '-'); ?></div></div>
        <div><div class="small">Year</div><div style="font-weight:700"><?= htmlspecialchars($user['year'] ?: '-'); ?></div></div>
        <div><div class="small">Section</div><div style="font-weight:700"><?= htmlspecialchars($user['section'] ?: '-'); ?></div></div>
        <div><div class="small">Created</div><div style="font-weight:700"><?= htmlspecialchars($user['created_at']); ?></div></div>
      </div>

      <?php if (!empty($allots)): ?>
        <div style="margin-top:12px">
          <div class="small">Allotted Classes</div>
          <ul style="margin-top:6px">
            <?php foreach($allots as $cl): ?><li><?= htmlspecialchars($cl); ?></li><?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div style="margin-top:12px">
        <a class="btn" style="background:#6b7280;color:#fff" href="view_user.php">← Back</a>
      </div>
    </div>

  <?php else: ?>
    <h2>View Users</h2>

    <div class="card">
      <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
        <input name="q" placeholder="Search name or email" value="<?= htmlspecialchars($q); ?>" class="input" style="min-width:220px">
        <select name="role" class="input" style="width:160px">
          <option value="">All roles</option>
          <?php foreach(['student','faculty','class_incharge','hod','admin'] as $r): ?>
            <option value="<?= $r; ?>" <?= $role===$r ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$r)); ?></option>
          <?php endforeach;?>
        </select>
        <button class="btn btn-primary" type="submit">Search</button>
      </form>
    </div>

    <div class="card">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Role</th>
            <th>Dept</th><th>Year</th><th>Section</th><th>Status</th><th>Details</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($u = $res->fetch_assoc()): ?>
          <tr>
            <td><?= (int)$u['id']; ?></td>
            <td><?= htmlspecialchars($u['full_name']); ?></td>
            <td><?= htmlspecialchars($u['email']); ?></td>
            <td><?= htmlspecialchars($u['role']); ?></td>
            <td><?= htmlspecialchars($u['department']); ?></td>
            <td><?= htmlspecialchars($u['year']); ?></td>
            <td><?= htmlspecialchars($u['section']); ?></td>
            <td><?= htmlspecialchars($u['status']); ?></td>
            <td><a class="btn" style="background:#2563EB;color:#fff" href="view_user.php?id=<?= (int)$u['id']; ?>">View</a></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

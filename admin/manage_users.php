<?php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$actionMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!empty($_POST['action']) && !empty($_POST['user_id'])) {
    $uid = (int)$_POST['user_id'];
    $action = $_POST['action'];

    // Get user real name
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id=?");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $stmt->bind_result($realName);
    $stmt->fetch();
    $stmt->close();

    if ($action === 'approve') {
      $stmt = $conn->prepare("UPDATE users SET status='approved' WHERE id=?");
      $stmt->bind_param("i", $uid); $stmt->execute();
      $actionMsg = "$realName has been approved.";
    } elseif ($action === 'reject') {
      $stmt = $conn->prepare("UPDATE users SET status='rejected' WHERE id=?");
      $stmt->bind_param("i", $uid); $stmt->execute();
      $actionMsg = "$realName has been rejected.";
    } elseif ($action === 'revoke') {
      $stmt = $conn->prepare("UPDATE users SET status='revoked' WHERE id=?");
      $stmt->bind_param("i", $uid); $stmt->execute();
      $actionMsg = "$realName's access has been revoked.";
    } elseif ($action === 'delete') {
      $stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role!='admin'");
      $stmt->bind_param("i", $uid); $stmt->execute();
      $actionMsg = "$realName has been deleted.";
    }
  }
}

// filters
$q = trim($_GET['q'] ?? '');
$role = $_GET['role'] ?? '';
$dept = $_GET['dept'] ?? '';
$year = $_GET['year'] ?? '';
$status = $_GET['status'] ?? '';

$where = "WHERE 1=1";
$params = []; $types = '';
if ($q !== '') { $where .= " AND (full_name LIKE CONCAT('%',?,'%') OR email LIKE CONCAT('%',?,'%'))"; $params[]=$q; $params[]=$q; $types .= 'ss'; }
if ($role !== '') { $where .= " AND role=?"; $params[]=$role; $types .= 's'; }
if ($dept !== '') { $where .= " AND department=?"; $params[]=$dept; $types .= 's'; }
if ($year !== '') { $where .= " AND year=?"; $params[]=(int)$year; $types .= 'i'; }
if ($status !== '') { $where .= " AND status=?"; $params[]=$status; $types .= 's'; }

$sql = "SELECT id, full_name, email, role, department, year, section, status FROM users $where ORDER BY created_at DESC LIMIT 1000";
$stmt = $conn->prepare($sql);
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute(); $res = $stmt->get_result();
?>
<main class="main">
  <h2>Manage Users</h2>

  <?php if ($actionMsg): ?>
    <div class="card small" style="background:#e5f9e7;padding:10px;margin-bottom:10px;">
      <?= htmlspecialchars($actionMsg); ?>
    </div>
  <?php endif; ?>

  <div class="card">
    <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
      <input name="q" placeholder="Search name or email" value="<?= htmlspecialchars($q); ?>" class="input" style="min-width:220px">
      <select name="role" class="input" style="width:160px">
        <option value="">All roles</option>
        <?php foreach(['student','faculty','class_incharge','hod','admin'] as $r): ?>
          <option value="<?= $r; ?>" <?= $role===$r ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$r)); ?></option>
        <?php endforeach;?>
      </select>
      <select name="dept" class="input" style="width:140px">
        <option value="">Department</option>
        <?php foreach(['CSE','EEE','ECE'] as $d): ?>
          <option value="<?= $d; ?>" <?= $dept===$d ? 'selected' : '' ?>><?= $d; ?></option>
        <?php endforeach;?>
      </select>
      <select name="year" class="input" style="width:120px">
        <option value="">Year</option>
        <?php foreach([1,2,3] as $y): ?>
          <option value="<?= $y; ?>" <?= ((string)$y)===$year ? 'selected' : '' ?>><?= $y; ?></option>
        <?php endforeach;?>
      </select>
      <select name="status" class="input" style="width:160px">
        <option value="">All status</option>
        <?php foreach(['approved','rejected','revoked','pending'] as $s): ?>
          <option value="<?= $s; ?>" <?= $status===$s ? 'selected' : '' ?>><?= ucfirst($s); ?></option>
        <?php endforeach;?>
      </select>
      <button class="btn btn-primary" type="submit">Filter</button>
    </form>
  </div>

  <div class="card">
    <table class="table">
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Dept</th><th>Year</th><th>Section</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php while($u = $res->fetch_assoc()): ?>
          <tr>
            <td><?= htmlspecialchars($u['full_name']); ?></td>
            <td><?= htmlspecialchars($u['email']); ?></td>
            <td><?= htmlspecialchars($u['role']); ?></td>
            <td><?= htmlspecialchars($u['department']); ?></td>
            <td><?= htmlspecialchars($u['year']); ?></td>
            <td><?= htmlspecialchars($u['section']); ?></td>
            <td><span class="badge"><?= htmlspecialchars($u['status']); ?></span></td>
            <td>
              <div class="dropdown" style="position:relative;display:inline-block;">
                <button class="btn btn-primary">Edit ▼</button>
                <div class="dropdown-content" style="display:none;position:absolute;background:#fff;box-shadow:0 2px 6px rgba(0,0,0,0.2);min-width:120px;z-index:1000;">
                  <form method="post">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id']; ?>">
                    <button name="action" value="approve" class="dropdown-btn">Approve</button>
                  </form>
                  <form method="post">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id']; ?>">
                    <button name="action" value="reject" class="dropdown-btn">Reject</button>
                  </form>
                  <form method="post">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id']; ?>">
                    <button name="action" value="revoke" class="dropdown-btn">Revoke</button>
                  </form>
                  <?php if($u['role']!=='admin'): ?>
                  <form method="post" onsubmit="return confirm('Delete <?= htmlspecialchars($u['full_name']); ?>?')">
                    <input type="hidden" name="user_id" value="<?= (int)$u['id']; ?>">
                    <button name="action" value="delete" class="dropdown-btn" style="color:red;">Delete</button>
                  </form>
                  <?php endif; ?>
                </div>
              </div>
              <a class="btn" style="background:#6b7280;color:#fff" href="view_user.php?id=<?= (int)$u['id']; ?>">View</a>
            </td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</main>

<script>
document.querySelectorAll('.dropdown').forEach(dd=>{
  const btn = dd.querySelector('button');
  const menu = dd.querySelector('.dropdown-content');
  btn.addEventListener('mouseover', ()=>menu.style.display='block');
  dd.addEventListener('mouseleave', ()=>menu.style.display='none');
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

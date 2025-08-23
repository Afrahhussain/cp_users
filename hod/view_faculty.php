<?php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$dept = $_SESSION['department'] ?? '';
$q = trim($_GET['q'] ?? '');

// Base query
$sql = "SELECT id, full_name, email, department FROM users WHERE role='faculty' AND department=?";
$params = [$dept];
$types = "s";

// Add search filter
if ($q !== '') {
  $sql .= " AND (full_name LIKE CONCAT('%', ?, '%') OR email LIKE CONCAT('%', ?, '%') OR id=?)";
  $idAsInt = ctype_digit($q) ? (int)$q : 0;
  $params[] = $q;
  $params[] = $q;
  $params[] = $idAsInt;
  $types .= "ssi";
}
$sql .= " ORDER BY full_name";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<main class="main" role="main">

  <!-- Page Header + Search in ONE card -->
  <div class="card" style="padding:20px;margin-bottom:20px">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
      <div>
        <h2 style="margin:0;font-size:22px;font-weight:700;color:#1e293b">
          Faculty <span style="color:#3b82f6">(<?= htmlspecialchars($dept); ?>)</span>
        </h2>
        <p style="margin:4px 0 0;color:#64748b;font-size:14px">
          Manage and view faculty members in your department
        </p>
      </div>
      <form method="get" style="display:flex;gap:8px;align-items:center">
        <input 
          class="input" 
          name="q" 
          value="<?= htmlspecialchars($q); ?>" 
          placeholder="Search by name, email, or ID" 
          style="flex:1;min-width:220px;padding:8px 12px;border:1px solid #d1d5db;
                 border-radius:6px;font-size:14px"
        >
        <button type="submit" 
          style="padding:8px 16px;border:1px solid #d1d5db;background:#fff;
                 border-radius:6px;cursor:pointer;font-size:14px">
          Search
        </button>
        <a href="view_faculty.php" 
          style="padding:8px 16px;border:1px solid #d1d5db;background:#fff;
                 border-radius:6px;cursor:pointer;font-size:14px;text-decoration:none;color:#111">
          Reset
        </a>
      </form>
    </div>
  </div>

  <!-- Faculty List -->
  <div class="card" style="padding:0">
    <table class="table" style="width:100%;border-collapse:collapse">
      <thead style="background:#f9fafb;border-bottom:2px solid #e5e7eb">
        <tr>
          <th style="padding:12px;text-align:left;width:5%">#</th>
          <th style="padding:12px;text-align:left;width:25%">Name</th>
          <th style="padding:12px;text-align:left;width:25%">Email</th>
          <th style="padding:12px;text-align:left;width:20%">Department</th>
          <th style="padding:12px;text-align:left;width:15%">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr>
            <td colspan="5" style="padding:16px;text-align:center;color:#6b7280">
              No faculty found.
            </td>
          </tr>
        <?php else: $i=1; foreach ($rows as $r): ?>
          <tr style="border-bottom:1px solid #f1f5f9">
            <td style="padding:12px"><?= $i++; ?></td>
            <td style="padding:12px"><?= htmlspecialchars($r['full_name']); ?></td>
            <td style="padding:12px"><?= htmlspecialchars($r['email']); ?></td>
            <td style="padding:12px"><?= htmlspecialchars($r['department']); ?></td>
            <td style="padding:12px">
              <a href="view_profile.php?id=<?= $r['id']; ?>" 
                 style="padding:6px 12px;border:1px solid #3b82f6;color:#3b82f6;
                        border-radius:6px;text-decoration:none;font-size:13px">
                View Profile
              </a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>

</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

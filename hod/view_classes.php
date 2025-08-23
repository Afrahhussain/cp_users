<?php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$dept    = $_SESSION['department'] ?? '';
$year    = $_GET['year'] ?? '';
$section = $_GET['section'] ?? '';
$q       = trim($_GET['q'] ?? '');

// Build query
$sql = "
  SELECT ca.id, ca.department, ca.year, ca.section, ca.subject, ca.created_at,
         u.full_name AS faculty_name, u.email AS faculty_email
  FROM class_allotments ca
  LEFT JOIN users u ON u.id = ca.faculty_id
  WHERE ca.department=?
";
$params = [$dept];
$types  = "s";

if ($year !== '') {
  $sql .= " AND ca.year=?";
  $params[] = $year; $types .= "s";
}
if ($section !== '') {
  $sql .= " AND ca.section=?";
  $params[] = $section; $types .= "s";
}
if ($q !== '') {
  $sql .= " AND (u.full_name LIKE CONCAT('%', ?, '%') OR ca.subject LIKE CONCAT('%', ?, '%'))";
  $params[] = $q; $params[] = $q; $types .= "ss";
}
$sql .= " ORDER BY ca.year, ca.section, ca.subject";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<main class="main" role="main">

  <div class="page-title" style="margin:0 0 10px 0">
    <h2 style="font-size:20px;font-weight:700;color:#1e293b;margin:0">
      Classes <span style="color:#3b82f6">(<?= htmlspecialchars($dept); ?>)</span>
    </h2>
  </div>

  <div class="card" style="padding:10px 12px;margin-bottom:10px">
    <form method="get" style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:0">
      <input
        class="input"
        name="q"
        value="<?= htmlspecialchars($q); ?>"
        placeholder="Search by faculty or subject"
        style="flex:1;min-width:220px;padding:7px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px"
      >
      <select name="year" class="input" style="padding:7px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px">
        <option value="">Year (all)</option>
        <option value="1" <?= $year==='1'?'selected':''; ?>>1</option>
        <option value="2" <?= $year==='2'?'selected':''; ?>>2</option>
        <option value="3" <?= $year==='3'?'selected':''; ?>>3</option>
      </select>
      <select name="section" class="input" style="padding:7px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px">
        <option value="">Section (all)</option>
        <option value="A" <?= $section==='A'?'selected':''; ?>>A</option>
        <option value="B" <?= $section==='B'?'selected':''; ?>>B</option>
        <option value="C" <?= $section==='C'?'selected':''; ?>>C</option>
      </select>

      <button type="submit"
        style="padding:7px 14px;border:1px solid #d1d5db;background:#fff;border-radius:6px;cursor:pointer;font-size:14px">
        Search
      </button>
      <a href="view_classes.php"
        style="padding:7px 14px;border:1px solid #d1d5db;background:#fff;border-radius:6px;cursor:pointer;font-size:14px;text-decoration:none;color:#111">
        Reset
      </a>
    </form>
  </div>

  <div class="card" style="padding:0">
    <table class="table" style="width:100%;border-collapse:collapse">
      <thead style="background:#f9fafb;border-bottom:2px solid #e5e7eb">
        <tr>
          <th style="padding:10px;text-align:left;font-size:14px">#</th>
          <th style="padding:10px;text-align:left;font-size:14px">Year</th>
          <th style="padding:10px;text-align:left;font-size:14px">Section</th>
          <th style="padding:10px;text-align:left;font-size:14px">Subject</th>
          <th style="padding:10px;text-align:left;font-size:14px">Faculty</th>
          <th style="padding:10px;text-align:left;font-size:14px">Created</th>
          <th style="padding:10px;text-align:left;font-size:14px">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$rows): ?>
          <tr>
            <td colspan="7" style="padding:14px;text-align:center;color:#6b7280;font-size:14px">No classes found.</td>
          </tr>
        <?php else: $i=1; foreach ($rows as $r): ?>
          <tr style="border-bottom:1px solid #f1f5f9">
            <td style="padding:10px;font-size:14px"><?= $i++; ?></td>
            <td style="padding:10px;font-size:14px"><?= htmlspecialchars($r['year']); ?></td>
            <td style="padding:10px;font-size:14px"><?= htmlspecialchars($r['section']); ?></td>
            <td style="padding:10px;font-size:14px"><?= htmlspecialchars($r['subject'] ?? '—'); ?></td>
            <td style="padding:10px;font-size:14px">
              <?= htmlspecialchars($r['faculty_name'] ?: '—'); ?>
              <?php if ($r['faculty_email']): ?>
                <div style="font-size:12px;color:#6b7280"><?= htmlspecialchars($r['faculty_email']); ?></div>
              <?php endif; ?>
            </td>
            <td style="padding:10px;font-size:14px"><?= htmlspecialchars($r['created_at']); ?></td>
            <td style="padding:10px">
              <a href="view_class.php?id=<?= (int)$r['id']; ?>" class="btn-outline">View</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<style>
.btn-outline {
  display:inline-block;padding:6px 12px;font-size:13px;border:1px solid #3b82f6;
  border-radius:6px;color:#3b82f6;text-decoration:none;background:transparent;transition:.2s;
}
.btn-outline:hover { background:#3b82f6;color:#fff; }
</style>

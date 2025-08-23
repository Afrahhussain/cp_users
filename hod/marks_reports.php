<?php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$dept = $_SESSION['department'] ?? '';
$marks = [];
$subject = "";

// Fetch classes
$classes = [];
$stmt = $conn->prepare("SELECT id, department, year, section, subject FROM class_allotments WHERE department=? ORDER BY year, section, subject");
$stmt->bind_param("s", $dept);
$stmt->execute();
$classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$class_id = $_GET['class_id'] ?? '';

if ($class_id) {
    // Get subject of this class
    $stmt = $conn->prepare("SELECT subject FROM class_allotments WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $subject = $res['subject'] ?? '';
    $stmt->close();

    // Fetch marks
    $sql = "
      SELECT u.full_name, u.email, m.exam_type, m.marks_obtained
      FROM marks m
      JOIN users u ON u.id = m.student_id
      WHERE m.class_id=?
      ORDER BY u.full_name, m.exam_type
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $marks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<main class="main">

  <!-- Page Header -->
  <div class="page-title" style="margin-bottom:12px">
    <h2 style="font-size:20px;font-weight:700;color:#1e293b;margin:0">
      Marks Reports <span style="color:#3b82f6">(<?= htmlspecialchars($dept); ?>)</span>
    </h2>
  </div>

  <!-- Filter Form -->
  <div class="card" style="padding:14px 16px;margin-bottom:16px">
    <form method="get" style="display:flex;flex-wrap:wrap;gap:10px;align-items:end;margin:0">
      <div style="flex:1;min-width:220px">
        <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:4px">Select Class</label>
        <select name="class_id" class="input" required
          style="width:100%;padding:7px 10px;border:1px solid #d1d5db;border-radius:6px;font-size:14px">
          <option value="">-- Choose a class --</option>
          <?php foreach($classes as $c): ?>
            <option value="<?= $c['id']; ?>" <?= $c['id']==$class_id?'selected':''; ?>>
              <?= $c['department']." • Year ".$c['year']." • Sec ".$c['section']." • ".$c['subject']; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="display:flex;gap:8px">
        <button type="submit" class="btn-outline">Load Report</button>
        <?php if ($class_id): ?>
          <button type="button" onclick="window.print()" class="btn-outline">Export PDF</button>
          <button type="button" onclick="exportCSV()" class="btn-outline">Export CSV</button>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Marks Table -->
  <?php if ($marks): ?>
    <div class="card" style="padding:0">
      <table class="table" style="width:100%;border-collapse:collapse">
        <thead style="background:#f9fafb;border-bottom:2px solid #e5e7eb">
          <tr>
            <th style="padding:10px;text-align:left;font-size:14px">#</th>
            <th style="padding:10px;text-align:left;font-size:14px">Student</th>
            <th style="padding:10px;text-align:left;font-size:14px">Email</th>
            <th style="padding:10px;text-align:left;font-size:14px">Exam</th>
            <th style="padding:10px;text-align:left;font-size:14px">Subject</th>
            <th style="padding:10px;text-align:left;font-size:14px">Marks</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($marks as $i => $row): ?>
            <tr style="border-bottom:1px solid #f1f5f9">
              <td style="padding:10px;font-size:14px"><?= $i+1; ?></td>
              <td style="padding:10px;font-size:14px"><?= htmlspecialchars($row['full_name']); ?></td>
              <td style="padding:10px;font-size:14px"><?= htmlspecialchars($row['email']); ?></td>
              <td style="padding:10px;font-size:14px"><?= htmlspecialchars($row['exam_type']); ?></td>
              <td style="padding:10px;font-size:14px"><?= htmlspecialchars($subject); ?></td>
              <td style="padding:10px;font-size:14px;font-weight:600;color:#111827">
                <?= htmlspecialchars($row['marks_obtained']); ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php elseif($class_id): ?>
    <div class="card" style="padding:16px;text-align:center;color:#6b7280">
      No marks records found for this class.
    </div>
  <?php endif; ?>
</main>

<!-- CSV Export Script -->
<script>
function exportCSV() {
  let table = document.querySelector("table");
  if (!table) {
    alert("No data available to export");
    return;
  }
  let rows = Array.from(table.querySelectorAll("tr")).map(r =>
    Array.from(r.querySelectorAll("th,td")).map(td => `"${td.innerText}"`)
  );
  let csv = rows.map(r => r.join(",")).join("\n");
  let blob = new Blob([csv], { type: "text/csv" });
  let url = window.URL.createObjectURL(blob);
  let a = document.createElement("a");
  a.href = url;
  a.download = "marks_report.csv";
  a.click();
  window.URL.revokeObjectURL(url);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

<style>
.btn-outline {
  display:inline-block;
  padding:7px 14px;
  font-size:14px;
  border:1px solid #d1d5db;
  border-radius:6px;
  color:#1e293b;
  background:#fff;
  text-decoration:none;
  cursor:pointer;
  transition:.2s;
}
.btn-outline:hover {
  background:#f3f4f6;
}
</style>

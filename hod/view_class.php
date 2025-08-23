<?php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$cls = null;
if ($id > 0) {
  $sql = "
    SELECT ca.id, ca.department, ca.year, ca.section, ca.subject, ca.created_at,
           u.full_name AS faculty_name, u.email AS faculty_email
    FROM class_allotments ca
    LEFT JOIN users u ON u.id = ca.faculty_id
    WHERE ca.id=? LIMIT 1
  ";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $cls = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}
?>

<main class="main" role="main">
  <?php if (!$id || !$cls): ?>
    <div class="alert-error">Class not found.</div>
  <?php else: ?>
    <div class="page-title">
      <h2>Class Details</h2>
    </div>

    <!-- Info Grid -->
    <div class="card-grid">
      <div class="info-block">
        <div class="label">Department</div>
        <div class="value"><?= htmlspecialchars($cls['department']); ?></div>
      </div>
      <div class="info-block">
        <div class="label">Year</div>
        <div class="value"><?= htmlspecialchars($cls['year']); ?></div>
      </div>
      <div class="info-block">
        <div class="label">Section</div>
        <div class="value"><?= htmlspecialchars($cls['section']); ?></div>
      </div>
      <div class="info-block">
        <div class="label">Created At</div>
        <div class="value"><?= htmlspecialchars($cls['created_at']); ?></div>
      </div>
    </div>

    <!-- Subject Card -->
    <div class="card">
      <div class="label">Subject</div>
      <div class="value subject"><?= htmlspecialchars($cls['subject']); ?></div>
    </div>

    <!-- Faculty Card -->
    <div class="card">
      <div class="label">Faculty</div>
      <div class="value faculty">
        <?= htmlspecialchars($cls['faculty_name'] ?: '—'); ?>
        <?php if (!empty($cls['faculty_email'])): ?>
          <a href="mailto:<?= htmlspecialchars($cls['faculty_email']); ?>" class="email">
            (<?= htmlspecialchars($cls['faculty_email']); ?>)
          </a>
        <?php endif; ?>
      </div>
    </div>

    <div style="margin-top:15px">
      <a href="view_classes.php" class="btn-primary">← Back to Classes</a>
    </div>
  <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>

<style>
/* Page title */
.page-title h2 {
  font-size: 22px;
  font-weight: 700;
  color: #1e293b;
  margin: 0 0 15px 0;
}

/* Error message */
.alert-error {
  padding: 12px 16px;
  border-left: 4px solid #dc2626;
  background: #fef2f2;
  color: #b91c1c;
  font-weight: 600;
  border-radius: 6px;
}

/* Card container */
.card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 14px 16px;
  margin-bottom: 12px;
  box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}

/* Grid for details */
.card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit,minmax(220px,1fr));
  gap: 12px;
  margin-bottom: 12px;
}
.info-block {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 12px;
}

/* Labels and values */
.label {
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 4px;
}
.value {
  font-size: 15px;
  font-weight: 600;
  color: #111827;
}
.value.subject {
  font-size: 16px;
  color: #1d4ed8;
}
.value.faculty {
  font-size: 15px;
}
.value .email {
  font-size: 13px;
  color: #2563eb;
  margin-left: 6px;
  text-decoration: none;
}
.value .email:hover {
  text-decoration: underline;
}

/* Buttons */
.btn-primary {
  display: inline-block;
  padding: 8px 14px;
  font-size: 14px;
  font-weight: 500;
  border-radius: 6px;
  border: 1px solid #3b82f6;
  color: #fff;
  background: #3b82f6;
  text-decoration: none;
  transition: background 0.2s ease;
}
.btn-primary:hover {
  background: #2563eb;
}
</style>

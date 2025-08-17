<?php
// faculty/marks.php
include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';

$faculty_id = $_SESSION['user_id'] ?? 0;
$errors = []; $messages = [];

/* fetch my classes */
$classes = [];
$q = $conn->prepare("SELECT id, department, year, section, subject FROM class_allotments WHERE faculty_id=? ORDER BY department, year, section, subject");
$q->bind_param("i", $faculty_id);
$q->execute();
$classes = $q->get_result()->fetch_all(MYSQLI_ASSOC);
$q->close();

$class_id  = (int)($_GET['class_id'] ?? $_POST['class_id'] ?? 0);
$exam_type = trim($_GET['exam_type'] ?? $_POST['exam_type'] ?? 'Midterm');

/* students in selected class */
$students = [];
$clsMeta  = null;
if ($class_id) {
  $meta = $conn->prepare("SELECT department,year,section,subject FROM class_allotments WHERE id=? AND faculty_id=? LIMIT 1");
  $meta->bind_param("ii", $class_id, $faculty_id);
  $meta->execute();
  $clsMeta = $meta->get_result()->fetch_assoc();
  $meta->close();

  if ($clsMeta) {
    $s = $conn->prepare("
      SELECT id, full_name, email
      FROM users
      WHERE role='student' AND department=? AND year=? AND section=? AND (status='approved' OR status IS NULL)
      ORDER BY full_name
    ");
    $s->bind_param("sss", $clsMeta['department'], $clsMeta['year'], $clsMeta['section']);
    $s->execute();
    $students = $s->get_result()->fetch_all(MYSQLI_ASSOC);
    $s->close();
  } else {
    $errors[] = "Invalid class selection.";
  }
}

/* save marks */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $class_id && $exam_type !== '') {
  // For each student input name="mark[ID]"
  $marks = $_POST['mark'] ?? [];

  foreach ($students as $stu) {
    $sid = (int)$stu['id'];
    $valRaw = trim($marks[$sid] ?? '');
    if ($valRaw === '') { continue; } // skip empty

    $val = (float)$valRaw;

    // check if exists
    $chk = $conn->prepare("SELECT id FROM marks WHERE faculty_id=? AND class_id=? AND student_id=? AND exam_type=? LIMIT 1");
    $chk->bind_param("iiis", $faculty_id, $class_id, $sid, $exam_type);
    $chk->execute();
    $row = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($row) {
      $u = $conn->prepare("UPDATE marks SET marks_obtained=? WHERE id=?");
      $u->bind_param("di", $val, $row['id']);
      $u->execute(); $u->close();
    } else {
      $i = $conn->prepare("INSERT INTO marks (faculty_id,class_id,student_id,exam_type,marks_obtained) VALUES (?,?,?,?,?)");
      $i->bind_param("iiisd", $faculty_id, $class_id, $sid, $exam_type, $val);
      $i->execute(); $i->close();
    }
  }
  $messages[] = "Marks saved for ".htmlspecialchars($exam_type).".";
}
?>
<main class="main" role="main">
  <div class="page-title" style="font-weight:700;font-size:20px;margin-bottom:8px">Upload / Update Marks</div>

  <?php foreach ($errors as $e): ?>
    <div class="card" style="background:#fff3f2;color:#9C1C12;margin-bottom:10px"><?= htmlspecialchars($e); ?></div>
  <?php endforeach; ?>
  <?php foreach ($messages as $m): ?>
    <div class="card" style="background:#ecfdf5;color:#065f46;margin-bottom:10px"><?= htmlspecialchars($m); ?></div>
  <?php endforeach; ?>

  <div class="card">
    <form method="get" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;align-items:end">
      <div>
        <div class="label">Class</div>
        <select name="class_id" class="input" required>
          <option value="">Select class</option>
          <?php foreach ($classes as $c): ?>
            <option value="<?= (int)$c['id']; ?>" <?= $class_id==$c['id']?'selected':''; ?>>
              <?= htmlspecialchars($c['department']." • ".$c['year']." • ".$c['section'].($c['subject']? " • ".$c['subject']:"")); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <div class="label">Exam</div>
        <select name="exam_type" class="input" required>
          <?php
            $opts = ['Midterm','Internal-1','Internal-2','Final','Practical','Project'];
            foreach ($opts as $o) {
              $sel = ($exam_type===$o)?'selected':'';
              echo "<option $sel>".htmlspecialchars($o)."</option>";
            }
          ?>
        </select>
      </div>
      <div>
        <button class="btn btn-primary" type="submit"><i class="fas fa-arrow-right" style="margin-right:6px"></i>Load</button>
      </div>
    </form>
  </div>

  <?php if ($class_id && $students): ?>
    <form method="post" class="card" style="margin-top:12px">
      <input type="hidden" name="class_id" value="<?= (int)$class_id; ?>">
      <input type="hidden" name="exam_type" value="<?= htmlspecialchars($exam_type); ?>">
      <div style="margin-bottom:10px">
        <strong>Class:</strong>
        <?= htmlspecialchars($clsMeta['department']." • ".$clsMeta['year']." • ".$clsMeta['section'].($clsMeta['subject']? " • ".$clsMeta['subject']:"")); ?>
      </div>

      <table class="table">
        <thead><tr><th>#</th><th>Name</th><th>Email</th><th style="width:140px">Marks</th></tr></thead>
        <tbody>
          <?php
            // preload existing marks for that exam
            $pre = [];
            $pm = $conn->prepare("SELECT student_id, marks_obtained FROM marks WHERE faculty_id=? AND class_id=? AND exam_type=?");
            $pm->bind_param("iis", $faculty_id, $class_id, $exam_type);
            $pm->execute();
            $rs = $pm->get_result();
            while ($row = $rs->fetch_assoc()) { $pre[(int)$row['student_id']] = $row['marks_obtained']; }
            $pm->close();
          ?>
          <?php $i=1; foreach ($students as $stu): $sid=(int)$stu['id']; ?>
            <tr>
              <td><?= $i++; ?></td>
              <td><?= htmlspecialchars($stu['full_name']); ?></td>
              <td><?= htmlspecialchars($stu['email']); ?></td>
              <td>
                <input type="number" name="mark[<?= $sid; ?>]" step="0.01" class="input" value="<?= htmlspecialchars($pre[$sid] ?? ''); ?>" placeholder="e.g., 85">
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <div style="margin-top:12px">
        <button class="btn btn-ghost" type="submit"><i class="fas fa-save" style="margin-right:6px"></i>Save Marks</button>
      </div>
    </form>
  <?php elseif ($class_id): ?>
    <div class="card" style="margin-top:12px">No students in this class.</div>
  <?php endif; ?>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>

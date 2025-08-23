<?php
include __DIR__ . '/../config.php';  // go up 1 level to cp_users/config.php
session_start();


$dept = $_SESSION['department'] ?? '';
$class_id = $_GET['class_id'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$type = $_GET['type'] ?? 'csv'; // default export type

if (!$class_id || !$from || !$to) {
    die("Missing required parameters.");
}

// Fetch class details
$stmt = $conn->prepare("SELECT department, year, section FROM class_allotments WHERE id=? AND department=?");
$stmt->bind_param("is", $class_id, $dept);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$class) {
    die("Class not found.");
}

// Fetch attendance records
$sql = "
  SELECT a.date, u.full_name, u.email, a.status
  FROM attendance a
  JOIN users u ON u.id = a.student_id
  WHERE a.class_id=? AND a.date BETWEEN ? AND ?
  ORDER BY a.date, u.full_name
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iss", $class_id, $from, $to);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($type === 'csv') {
    // --- Export as CSV ---
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance.csv"');

    $out = fopen("php://output", "w");
    fputcsv($out, ["Date", "Student", "Email", "Status"]);

    foreach ($rows as $r) {
        fputcsv($out, [$r['date'], $r['full_name'], $r['email'], $r['status']]);
    }
    fclose($out);
    exit;
} elseif ($type === 'pdf') {
    // --- Export as PDF ---
    require_once __DIR__ . '/../vendor/autoload.php'; // if you use Dompdf via Composer

    $html = "
      <h2 style='text-align:center;'>Attendance Report</h2>
      <p><b>Class:</b> {$class['department']} - Year {$class['year']} - Section {$class['section']}</p>
      <p><b>From:</b> {$from} &nbsp;&nbsp; <b>To:</b> {$to}</p>
      <table border='1' cellspacing='0' cellpadding='5' width='100%'>
        <thead>
          <tr>
            <th>Date</th><th>Student</th><th>Email</th><th>Status</th>
          </tr>
        </thead>
        <tbody>";

    foreach ($rows as $r) {
        $html .= "<tr>
          <td>{$r['date']}</td>
          <td>{$r['full_name']}</td>
          <td>{$r['email']}</td>
          <td>{$r['status']}</td>
        </tr>";
    }

    $html .= "</tbody></table>";

    // generate PDF
    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("attendance.pdf", ["Attachment" => 1]);
    exit;
} else {
    die("Invalid export type.");
}

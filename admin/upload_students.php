<?php
// admin/upload_students.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// try to include config (project root), adjust if your config is elsewhere
if (file_exists(__DIR__ . '/../config.php')) {
    include __DIR__ . '/../config.php';
} else {
    // If config missing, stop with a clear message
    die("Missing config.php in project root. Please put config.php one level above admin/ (admin/../config.php).");
}

// simple admin guard - change to match your session usage
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // not logged in as admin
    header("Location: ../index.php");
    exit();
}

/**
 * Helper: returns index of a header field among aliases or -1 if not found
 */
function header_index(array $hdrs, array $aliases) {
    foreach ($aliases as $a) {
        $pos = array_search($a, $hdrs, true);
        if ($pos !== false) return $pos;
    }
    return -1;
}

// collect messages
$errors = [];
$messages = [];

// Allowed values
$DEPTS = ['CSE','EEE','ECE'];
$YEARS = [1,2,3];
$SECTIONS = ['A','B','C'];

// ---------- CSV UPLOAD ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_csv'])) {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "No file uploaded or upload error.";
    } else {
        $tmp = $_FILES['csv_file']['tmp_name'];
        if (($f = fopen($tmp, 'r')) === false) {
            $errors[] = "Unable to open uploaded file.";
        } else {
            $raw_header = fgetcsv($f);
            if ($raw_header === false) {
                $errors[] = "CSV appears empty.";
            } else {
                // normalize header: trim, lowercase and remove BOM
                foreach ($raw_header as $k => $v) {
                    $v = (string)$v;
                    // remove BOM from first cell if present
                    if ($k === 0) $v = preg_replace('/^\xEF\xBB\xBF/', '', $v);
                    $raw_header[$k] = strtolower(trim($v));
                }

                // We'll accept flexible aliases for common column names
                $aliases_map = [
                    'full_name'  => ['full_name','fullname','name'],
                    'email'      => ['email','e-mail','mail'],
                    'password'   => ['password','pwd','pass'],
                    'role'       => ['role'],
                    'department' => ['department','dept'],
                    'year'       => ['year'],
                    'section'    => ['section','sec']
                ];

                // Build indexes for expected keys (we require at least full_name & email & role & department & year & section)
                $indexes = [];
                foreach ($aliases_map as $key => $aliases) {
                    $idx = header_index($raw_header, $aliases);
                    $indexes[$key] = $idx;
                }

                // required fields for students
                $requiredKeys = ['full_name','email','password','role','department','year','section'];
                $missing = [];
                foreach ($requiredKeys as $k) {
                    if (!isset($indexes[$k]) || $indexes[$k] === -1) $missing[] = $k;
                }

                if ($missing) {
                    $errors[] = "CSV missing required columns: " . implode(', ', $missing) . ". Expected headers (aliases accepted): full_name,email,password,role,department,year,section";
                } else {
                    $inserted = 0;
                    $skipped = 0;
                    $line = 1;
                    while (($row = fgetcsv($f)) !== false) {
                        $line++;
                        // skip empty rows
                        $empty = true;
                        foreach ($row as $c) { if (trim((string)$c) !== '') { $empty = false; break; } }
                        if ($empty) continue;

                        // read values by index
                        $full_name  = trim((string)($row[$indexes['full_name']] ?? ''));
                        $email      = trim((string)($row[$indexes['email']] ?? ''));
                        $password   = trim((string)($row[$indexes['password']] ?? ''));
                        $role       = trim((string)($row[$indexes['role']] ?? 'student'));
                        $department = strtoupper(trim((string)($row[$indexes['department']] ?? '')));
                        $year_raw   = trim((string)($row[$indexes['year']] ?? ''));
                        $section    = strtoupper(trim((string)($row[$indexes['section']] ?? '')));

                        // basic validation
                        if ($full_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $skipped++;
                            continue;
                        }

                        // Optional: enforce gmail only (uncomment if you want this)
                        // if (!preg_match('/@gmail\.com$/i', $email)) { $skipped++; continue; }

                        if (!in_array($department, $DEPTS, true)) { $skipped++; continue; }
                        $year = (int)$year_raw;
                        if (!in_array($year, $YEARS, true)) { $skipped++; continue; }
                        if (!in_array($section, $SECTIONS, true)) { $skipped++; continue; }

                        // check duplicate email
                        $chk = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
                        if ($chk === false) { $errors[] = "DB prepare error: " . $conn->error; break; }
                        $chk->bind_param("s", $email);
                        $chk->execute();
                        $chk->store_result();
                        if ($chk->num_rows > 0) { $skipped++; $chk->close(); continue; }
                        $chk->close();

                        // password: if already hashed (bcrypt/argon2 prefix), keep; else hash
                        if (strpos($password, '$2y$') === 0 || strpos($password, '$argon2') === 0) {
                            $pwd_db = $password;
                        } else {
                            $pwd_db = password_hash($password ?: 'student123', PASSWORD_DEFAULT);
                        }

                        // Insert
                        $stmt = $conn->prepare("INSERT INTO users (full_name,email,password,role,department,year,section,status,created_at) VALUES (?,?,?,?,?,?,?,'approved',NOW())");
                        if ($stmt === false) { $errors[] = "DB prepare error (insert): " . $conn->error; break; }
                        $stmt->bind_param("sssssis", $full_name, $email, $pwd_db, $role, $department, $year, $section);
                        if ($stmt->execute()) { $inserted++; } else { $skipped++; }
                        $stmt->close();
                    } // end while

                    $messages[] = "CSV processed. Inserted: $inserted. Skipped: $skipped.";
                } // end missing check
            } // end header exists

            fclose($f);
        } // end fopen
    } // end file uploaded
} // end POST upload_csv

// ---------- MANUAL ADD ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_manual'])) {
    $full_name = trim((string)($_POST['full_name'] ?? ''));
    $email     = trim((string)($_POST['email'] ?? ''));
    $password_raw = trim((string)($_POST['password'] ?? ''));
    $department = strtoupper(trim((string)($_POST['department'] ?? '')));
    $year = (int)($_POST['year'] ?? 0);
    $section = strtoupper(trim((string)($_POST['section'] ?? '')));

    if ($full_name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($department, $DEPTS, true) || !in_array($year, $YEARS, true) || !in_array($section, $SECTIONS, true)) {
        $errors[] = "Please fill all fields correctly.";
    } else {
        // duplicate
        $chk = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $chk->bind_param("s", $email);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $errors[] = "A user with that email already exists.";
            $chk->close();
        } else {
            $chk->close();
            $pwd_db = password_hash($password_raw ?: 'student123', PASSWORD_DEFAULT);
            $role = 'student';
            $stmt = $conn->prepare("INSERT INTO users (full_name,email,password,role,department,year,section,status,created_at) VALUES (?,?,?,?,?,?,?,'approved',NOW())");
            if ($stmt === false) { $errors[] = "DB prepare error: " . $conn->error; }
            else {
                $stmt->bind_param("sssssis", $full_name, $email, $pwd_db, $role, $department, $year, $section);
                if ($stmt->execute()) $messages[] = "Student added successfully.";
                else $errors[] = "DB insert error: " . $stmt->error;
                $stmt->close();
            }
        }
    }
}

// HTML output: try to include common includes/header.php and sidebar if they exist; otherwise render header + sidebar inline
$hasHeaderInclude = file_exists(__DIR__ . '/includes/header.php');
$hasSidebarInclude = file_exists(__DIR__ . '/includes/sidebar.php');
$hasFooterInclude = file_exists(__DIR__ . '/includes/footer.php');

if ($hasHeaderInclude) {
    include __DIR__ . '/includes/header.php';
} else {
    // basic header + link to styles.js
    ?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8"/>
      <meta name="viewport" content="width=device-width,initial-scale=1" />
      <title>Upload Students - Admin</title>
      <link rel="stylesheet" href="assets/styles.css">
      <script defer src="assets/scripts.js"></script>
    </head>
    <body>
    <header class="topbar" style="position:fixed;left:0;right:0;top:0;height:64px;background:#fff;display:flex;align-items:center;padding:0 18px;z-index:1000;">
      <div class="topbar-left">
        <button id="hamburger" class="hamburger" aria-label="Toggle menu" style="font-size:18px;padding:6px 10px;border-radius:6px;border:1px solid #eee;background:#fafafa;cursor:pointer"><i class="fas fa-bars"></i></button>
        <a class="brand" href="dashboard.php" style="margin-left:12px;font-weight:700;color:#0f172a">College Portal</a>
      </div>
      <div class="topbar-right" style="display:flex;align-items:center;gap:12px">
        <span class="role-pill" style="background:#eef2ff;color:#1e3a8a;padding:6px 10px;border-radius:999px;font-weight:600">Admin</span>
        <a class="logout" href="../logout.php" title="Logout" style="color:#6b7280"><i class="fas fa-sign-out-alt"></i></a>
      </div>
    </header>
    <?php
}

// sidebar
if ($hasSidebarInclude) {
    include __DIR__ . '/includes/sidebar.php';
} else {
    // simple sidebar inline
    ?>
    <aside id="sidebar" class="sidebar" style="position:fixed;top:64px;left:0;width:260px;height:calc(100vh - 64px);background:#0f172a;color:#fff;padding:16px;overflow:auto;">
      <div style="font-weight:700;margin-bottom:12px">Admin Panel</div>
      <nav>
        <a href="dashboard.php" style="display:block;color:#fff;padding:8px 0">Dashboard</a>
        <a href="manage_users.php" style="display:block;color:#fff;padding:8px 0">Manage Users</a>
        <a href="upload_students.php" style="display:block;color:#fff;padding:8px 0">Upload Students</a>
        <a href="upload_faculty.php" style="display:block;color:#fff;padding:8px 0">Upload Faculty</a>
      </nav>
    </aside>
    <?php
}

// main content container (main class matches earlier CSS)
?>
<main class="main" role="main" style="margin-left:260px;margin-top:84px;padding:22px;min-height:calc(100vh - 84px);">
  <div class="page-title" style="font-weight:700;font-size:20px;margin-bottom:12px">Upload Students</div>

  <?php foreach ($errors as $e): ?>
    <div class="card" style="background:#fff3f2;color:#9C1C12;margin-bottom:14px;padding:12px;border-radius:8px"><?= htmlspecialchars($e); ?></div>
  <?php endforeach; ?>

  <?php foreach ($messages as $m): ?>
    <div class="card" style="background:#ecfdf5;color:#064e3b;margin-bottom:14px;padding:12px;border-radius:8px"><?= htmlspecialchars($m); ?></div>
  <?php endforeach; ?>

  <div class="card" style="padding:16px;margin-bottom:14px">
    <h3 style="margin-bottom:8px">Upload via CSV</h3>
    <form method="POST" enctype="multipart/form-data">
      <div class="field">
        <input type="file" name="csv_file" accept=".csv" required>
        <div class="small" style="margin-top:8px;color:#64748b">CSV must contain columns (aliases accepted): <code>full_name,email,password,role,department,year,section</code></div>
      </div>
      <div style="margin-top:12px">
        <button type="submit" name="upload_csv" class="btn btn-primary" style="padding:8px 12px;background:#2563EB;color:#fff;border-radius:8px;border:0">Upload CSV</button>
      </div>
    </form>
  </div>

  <div class="card" style="padding:16px">
    <h3 style="margin-bottom:8px">Add Student Manually</h3>
    <form method="POST" class="form">
      <div class="form-row" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px">
        <input name="full_name" placeholder="Full name" class="input" required style="padding:10px;border-radius:8px;border:1px solid #e6eef6">
        <input name="email" type="email" placeholder="Email (e.g. name@gmail.com)" class="input" required style="padding:10px;border-radius:8px;border:1px solid #e6eef6">
      </div>
      <div class="form-row" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-top:12px">
        <input name="password" type="password" placeholder="Password" class="input" style="padding:10px;border-radius:8px;border:1px solid #e6eef6">
        <select name="department" class="input" required style="padding:10px;border-radius:8px;border:1px solid #e6eef6">
          <option value="">Department</option><option>CSE</option><option>EEE</option><option>ECE</option>
        </select>
      </div>
      <div class="form-row" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-top:12px">
        <select name="year" class="input" required style="padding:10px;border-radius:8px;border:1px solid #e6eef6">
          <option value="">Year</option><option value="1">1</option><option value="2">2</option><option value="3">3</option>
        </select>
        <select name="section" class="input" required style="padding:10px;border-radius:8px;border:1px solid #e6eef6">
          <option value="">Section</option><option>A</option><option>B</option><option>C</option>
        </select>
      </div>

      <div style="margin-top:12px">
        <button type="submit" name="add_manual" class="btn btn-success" style="padding:8px 12px;background:#14B8A6;color:#fff;border-radius:8px;border:0">Add Student</button>
      </div>
    </form>
    <p class="small" style="margin-top:10px;color:#64748b">If password left blank, a default password <code>student123</code> is used (hashed before storing).</p>
  </div>

</main>

<?php
if ($hasFooterInclude) {
    include __DIR__ . '/includes/footer.php';
} else {
    ?>
    <footer class="footer" style="padding:12px;text-align:center;color:#64748b;margin-top:12px">© <?= date("Y"); ?> College Portal</footer>
    </body>
    </html>
    <?php
}

<?php
include "includes/header.php";
include "includes/sidebar.php";

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    echo "<div class='main'><p class='text-red-600'>Invalid request! No user selected.</p></div>";
    include "includes/footer.php";
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id=? AND department=? LIMIT 1");
$stmt->bind_param("is", $user_id, $dept);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();
?>
<div class="main">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-2xl font-bold">User Profile</h1>
    <a href="dashboard.php" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm">
      ← Back to Dashboard
    </a>
  </div>

  <?php if ($user): ?>
    <div class="bg-white shadow rounded-lg p-6 flex gap-6 items-start">
      
      <!-- Avatar / Placeholder -->
      <div class="flex-shrink-0">
        <div class="w-28 h-28 rounded-full bg-gradient-to-br from-blue-600 to-teal-500 flex items-center justify-center text-white text-3xl font-bold">
          <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
        </div>
      </div>

      <!-- Profile Info -->
      <div class="flex-1">
        <table class="w-full border text-left">
          <tr><th class="p-2 border w-40">Name</th><td class="p-2 border"><?= htmlspecialchars($user['full_name']) ?></td></tr>
          <tr><th class="p-2 border">Email</th><td class="p-2 border"><?= htmlspecialchars($user['email']) ?></td></tr>
          <tr><th class="p-2 border">Role</th><td class="p-2 border capitalize"><?= htmlspecialchars($user['role']) ?></td></tr>
          <tr><th class="p-2 border">Department</th><td class="p-2 border"><?= htmlspecialchars($user['department']) ?></td></tr>
          <?php if (!empty($user['year'])): ?>
            <tr><th class="p-2 border">Year</th><td class="p-2 border"><?= htmlspecialchars($user['year']) ?></td></tr>
          <?php endif; ?>
          <?php if (!empty($user['section'])): ?>
            <tr><th class="p-2 border">Section</th><td class="p-2 border"><?= htmlspecialchars($user['section']) ?></td></tr>
          <?php endif; ?>
          <tr><th class="p-2 border">Status</th><td class="p-2 border"><?= htmlspecialchars($user['status']) ?></td></tr>
        </table>
      </div>
    </div>
  <?php else: ?>
    <p class="text-red-600">No user found in <?= htmlspecialchars($dept) ?> department.</p>
  <?php endif; ?>
</div>

<?php include "includes/footer.php"; ?>

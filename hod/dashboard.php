<?php include "includes/header.php"; ?>
<?php include "includes/sidebar.php"; ?>

<div class="main">
  <h1 class="text-2xl font-bold mb-4">Welcome, <?= htmlspecialchars($hod_name) ?> </h1>

  <!-- Search Bar -->
  <form method="get" class="mb-6 flex">
    <input type="text" name="search" placeholder="Search faculty or student..."
      value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
      class="w-full p-3 border rounded-l-lg focus:outline-none">
    <button type="submit" class="bg-blue-600 text-white px-4 rounded-r-lg hover:bg-blue-800"><i class="fas fa-search"></i></button>
  </form>

  <!-- Search Results -->
  <?php
  $search_term = $_GET['search'] ?? '';
  if (!empty($search_term)) {
      $like = "%$search_term%";
      $q = $conn->prepare("SELECT id, full_name, email, role FROM users 
                           WHERE department=? AND role IN ('student','faculty') AND full_name LIKE ?
                           ORDER BY role, full_name");
      $q->bind_param("ss", $dept, $like);
      $q->execute();
      $search_results = $q->get_result()->fetch_all(MYSQLI_ASSOC);
      $q->close();
  ?>
    <div class="bg-white shadow rounded-lg p-4 mb-6">
      <h2 class="font-semibold mb-3">Search results for "<?= htmlspecialchars($search_term) ?>"</h2>
      <?php if (!empty($search_results)): ?>
        <table class="w-full text-left border">
          <thead class="bg-gray-100">
            <tr><th class="p-2">#</th><th class="p-2">Name</th><th class="p-2">Email</th><th class="p-2">Role</th><th class="p-2">Action</th></tr>
          </thead>
          <tbody>
            <?php $i=1; foreach ($search_results as $r): ?>
              <tr class="border-t">
                <td class="p-2"><?= $i++ ?></td>
                <td class="p-2"><?= htmlspecialchars($r['full_name']) ?></td>
                <td class="p-2"><?= htmlspecialchars($r['email']) ?></td>
                <td class="p-2 capitalize"><?= htmlspecialchars($r['role']) ?></td>
                <td class="p-2"><a href="view_profile.php?id=<?= $r['id'] ?>" class="text-blue-600 hover:underline">View Profile</a></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <p class="text-gray-600">No results found in <?= htmlspecialchars($dept) ?>.</p>
      <?php endif; ?>
    </div>
  <?php } ?>

  <!-- Stats -->
  <?php
  $stats = [
      'students' => (int)($conn->query("SELECT COUNT(*) c FROM users WHERE role='student' AND department='$dept'")->fetch_assoc()['c'] ?? 0),
      'faculty' => (int)($conn->query("SELECT COUNT(*) c FROM users WHERE role='faculty' AND department='$dept'")->fetch_assoc()['c'] ?? 0),
      'classes' => (int)($conn->query("SELECT COUNT(DISTINCT year, section) c FROM users WHERE role='student' AND department='$dept'")->fetch_assoc()['c'] ?? 0),
  ];
  ?>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white shadow rounded-lg p-4 text-center">
      <div class="text-sm text-gray-500">Students in <?= htmlspecialchars($dept) ?></div>
      <div class="text-2xl font-bold"><?= $stats['students'] ?></div>
    </div>
    <div class="bg-white shadow rounded-lg p-4 text-center">
      <div class="text-sm text-gray-500">Faculty in <?= htmlspecialchars($dept) ?></div>
      <div class="text-2xl font-bold"><?= $stats['faculty'] ?></div>
    </div>
    <div class="bg-white shadow rounded-lg p-4 text-center">
      <div class="text-sm text-gray-500">Classes in <?= htmlspecialchars($dept) ?></div>
      <div class="text-2xl font-bold"><?= $stats['classes'] ?></div>
    </div>
  </div>
</div>

<?php include "includes/footer.php"; ?>

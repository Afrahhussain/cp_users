<?php include("includes/header.php"); ?>
<?php include("includes/sidebar.php"); ?>

<h2 class="text-xl font-bold mb-4">Announcements</h2>

<form method="post">
  <textarea name="message" class="w-full border rounded p-3 mb-3" placeholder="Write announcement..."></textarea>
  <button type="submit" name="post" class="px-4 py-2 bg-teal-600 text-white rounded hover:bg-teal-700">Post</button>
</form>

<?php
if (isset($_POST['post'])) {
    $msg = trim($_POST['message']);
    if ($msg != "") {
        $fid = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO announcements (faculty_id, message, created_at) VALUES (?,?,NOW())");
        $stmt->bind_param("is", $fid, $msg);
        $stmt->execute();
        echo "<p class='mt-3 text-green-600'>Announcement posted!</p>";
    }
}
?>

<h3 class="mt-6 font-semibold">My Announcements</h3>
<ul class="space-y-2 mt-2">
<?php
$fid = $_SESSION['user_id'];
$res = $conn->query("SELECT message, created_at FROM announcements WHERE faculty_id=$fid ORDER BY created_at DESC");
while ($row = $res->fetch_assoc()) {
    echo "<li class='bg-white shadow p-3 rounded'>{$row['message']} <span class='text-xs text-gray-500'>({$row['created_at']})</span></li>";
}
?>
</ul>

<?php include("includes/footer.php"); ?>

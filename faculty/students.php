<?php include("includes/header.php"); ?>
<?php include("includes/sidebar.php"); ?>

<h2 class="text-xl font-bold mb-4">Students in My Classes</h2>
<table class="w-full border">
  <thead class="bg-blue-900 text-white">
    <tr>
      <th class="p-2 border">Name</th>
      <th class="p-2 border">Roll No</th>
      <th class="p-2 border">Department</th>
      <th class="p-2 border">Year</th>
      <th class="p-2 border">Section</th>
    </tr>
  </thead>
  <tbody>
  <?php
  $fid = $_SESSION['user_id'];
  $res = $conn->query("SELECT s.name, s.roll_no, s.department, s.year, s.section 
                       FROM students s
                       JOIN class_allotments ca ON s.class_id=ca.class_id
                       WHERE ca.faculty_id=$fid");
  while ($row = $res->fetch_assoc()) {
      echo "<tr class='bg-white hover:bg-gray-50'>
              <td class='p-2 border'>{$row['name']}</td>
              <td class='p-2 border'>{$row['roll_no']}</td>
              <td class='p-2 border'>{$row['department']}</td>
              <td class='p-2 border'>{$row['year']}</td>
              <td class='p-2 border'>{$row['section']}</td>
            </tr>";
  }
  ?>
  </tbody>
</table>

<?php include("includes/footer.php"); ?>

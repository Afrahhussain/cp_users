<?php
session_start();
include "config.php";

$login_error = "";
$register_error = "";
$register_success = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            if ($row['status'] == 'approved') {
                // ✅ Store all important session values
                $_SESSION['user_id']   = $row['id'];
                $_SESSION['role']      = $row['role'];
                $_SESSION['full_name'] = $row['full_name'];
                $_SESSION['department'] = $row['department'];

                header("Location: dashboard_redirect.php");
                exit();
            } else {
                $login_error = "Your account is not approved yet!";
            }
        } else {
            $login_error = "Invalid password!";
        }
    } else {
        $login_error = "No user found with this email!";
    }
}

if (isset($_POST['register'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $department = (!empty($_POST['department'])) ? $_POST['department'] : NULL;

    // 🚫 Block self-register as Admin or HOD
    if ($role === "admin" || $role === "hod") {
        $register_error = "You cannot self-register as Admin or HOD.";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $check->bind_param("s", $email);
        $check->execute();
        $check_result = $check->get_result();

        if ($check_result->num_rows > 0) {
            $register_error = "Email already exists!";
        } else {
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, role, department, status) 
                                    VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("sssss", $full_name, $email, $password, $role, $department);

            if ($stmt->execute()) {
                $register_success = "Registration successful! Awaiting admin approval.";
            } else {
                $register_error = "Error: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>College Portal - Login & Register</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="flex items-center justify-center min-h-screen">
  <div class="w-full max-w-4xl bg-white shadow-lg rounded-2xl grid grid-cols-1 md:grid-cols-2 overflow-hidden">

    <!-- Left Banner -->
    <div class="hidden md:flex items-center justify-center bg-gradient-to-br from-blue-900 to-teal-500 text-white p-8">
      <div class="text-center">
        <h1 class="text-3xl font-bold mb-4">College Portal</h1>
        <p class="text-lg">A complete Academic Management System</p>
      </div>
    </div>

    <!-- Right: Forms -->
    <div class="p-8">
      <div class="mb-6 flex justify-center space-x-6">
        <button id="loginTab" class="tab-btn font-semibold text-blue-900 border-b-2 border-blue-900 pb-1">Login</button>
        <button id="registerTab" class="tab-btn text-gray-500">Register</button>
      </div>

      <!-- Login Form -->
      <?php if (!empty($login_error)) echo "<p class='text-red-600 mb-2'>$login_error</p>"; ?>
      <form id="loginForm" method="post">
        <input type="email" name="email" placeholder="Email" class="w-full p-3 mb-3 border rounded-lg" required>
        <input type="password" name="password" placeholder="Password" class="w-full p-3 mb-1 border rounded-lg" required>
        <div class="text-right mb-3">
          <a href="forgot_password.php" class="text-blue-600 text-sm hover:underline">Forgot Password?</a>
        </div>
        <button type="submit" name="login" class="w-full bg-teal-500 text-white p-3 rounded-lg hover:bg-blue-900">Login</button>
      </form>

      <!-- Register Form -->
      <?php if (!empty($register_error)) echo "<p class='text-red-600 mb-2'>$register_error</p>"; ?>
      <?php if (!empty($register_success)) echo "<p class='text-green-600 mb-2'>$register_success</p>"; ?>
      <form id="registerForm" method="post" class="hidden">
        <input type="text" name="full_name" placeholder="Full Name" class="w-full p-3 mb-3 border rounded-lg" required>
        <input type="email" name="email" placeholder="Email" class="w-full p-3 mb-3 border rounded-lg" required>
        <input type="password" name="password" placeholder="Password" class="w-full p-3 mb-3 border rounded-lg" required>
        <select name="role" class="w-full p-3 mb-3 border rounded-lg" required>
          <option value="">Select Role</option>
          <option value="faculty">Faculty</option>
          <option value="class_incharge">Class Incharge</option>
          <option value="student">Student</option>
        </select>
        <select name="department" class="w-full p-3 mb-3 border rounded-lg">
          <option value="">Select Department (if applicable)</option>
          <option value="CSE">CSE</option>
          <option value="EEE">EEE</option>
          <option value="ECE">ECE</option>
        </select>
        <button type="submit" name="register" class="w-full bg-teal-500 text-white p-3 rounded-lg hover:bg-blue-900">Register</button>
      </form>
    </div>
  </div>
</div>

<script>
  const loginTab = document.getElementById("loginTab");
  const registerTab = document.getElementById("registerTab");
  const loginForm = document.getElementById("loginForm");
  const registerForm = document.getElementById("registerForm");

  loginTab.addEventListener("click", () => {
    loginForm.classList.remove("hidden");
    registerForm.classList.add("hidden");
    loginTab.classList.add("text-blue-900","border-b-2","border-blue-900");
    registerTab.classList.remove("text-blue-900","border-b-2","border-blue-900");
    registerTab.classList.add("text-gray-500");
  });

  registerTab.addEventListener("click", () => {
    registerForm.classList.remove("hidden");
    loginForm.classList.add("hidden");
    registerTab.classList.add("text-blue-900","border-b-2","border-blue-900");
    loginTab.classList.remove("text-blue-900","border-b-2","border-blue-900");
    loginTab.classList.add("text-gray-500");
  });
</script>

</body>
</html>

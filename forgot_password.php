<?php
include "config.php";
$message = "";

if (isset($_POST['submit'])) {
    $email = trim($_POST['email']);
    $stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $token = bin2hex(random_bytes(16));
        $expiry = date("Y-m-d H:i:s", strtotime("+15 minutes"));
        $update = $conn->prepare("UPDATE users SET reset_token=?, reset_expiry=? WHERE email=?");
        $update->bind_param("sss", $token, $expiry, $email);
        $update->execute();

        // Automatically detect folder name for correct URL
        $folder = basename(__DIR__);
        $reset_link = "http://localhost/$folder/reset_password.php?token=$token";

        $message = "Password reset link (valid 15 min): <a href='$reset_link' class='text-blue-600 hover:underline'>$reset_link</a>";
    } else {
        $message = "No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password - College Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
<div class="bg-white p-6 rounded-xl shadow-lg w-96">
    <h2 class="text-xl font-bold mb-4 text-center">Forgot Password</h2>
    <?php if(!empty($message)) echo "<p class='mb-3 text-sm'>$message</p>"; ?>
    <form method="post">
        <input type="email" name="email" placeholder="Enter your registered email" required
               class="w-full p-3 mb-3 border rounded-lg">
        <button type="submit" name="submit"
                class="w-full bg-teal-500 text-white p-3 rounded-lg hover:bg-blue-900">
            Send Reset Link
        </button>
    </form>
    <p class="mt-3 text-sm text-center">
        <a href="index.php" class="text-blue-600 hover:underline">Back to Login</a>
    </p>
</div>
</body>
</html>

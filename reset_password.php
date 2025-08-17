<?php
include "config.php";
$message = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $conn->prepare("SELECT id, reset_expiry FROM users WHERE reset_token=? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $user_id = $row['id'];
        $expiry = $row['reset_expiry'];

        if (strtotime($expiry) < time()) {
            $message = "Reset link has expired!";
        } elseif (isset($_POST['reset'])) {
            $new_pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expiry=NULL WHERE id=?");
            $update->bind_param("si", $new_pass, $user_id);
            $update->execute();
            $message = "Password reset successful! <a href='index.php' class='text-blue-600 hover:underline'>Login</a>";
        }
    } else {
        $message = "Invalid reset link!";
    }
} else {
    $message = "No reset token provided!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - College Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
<div class="bg-white p-6 rounded-xl shadow-lg w-96">
    <h2 class="text-xl font-bold mb-4 text-center">Reset Password</h2>
    <?php if(!empty($message)) echo "<p class='mb-3 text-sm'>$message</p>"; ?>

    <?php if(isset($user_id) && (empty($message) || isset($_POST['reset']))): ?>
    <form method="post">
        <input type="password" name="password" placeholder="New Password" required
               class="w-full p-3 mb-3 border rounded-lg">
        <button type="submit" name="reset"
                class="w-full bg-green-600 text-white p-3 rounded-lg hover:bg-green-800">
            Reset Password
        </button>
    </form>
    <?php endif; ?>

    <p class="mt-3 text-sm text-center">
        <a href="index.php" class="text-blue-600 hover:underline">Back to Login</a>
    </p>
</div>
</body>
</html>

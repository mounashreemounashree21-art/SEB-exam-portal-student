<?php
session_start();

// DB CONNECTION - Use mysqli with error reporting enabled for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli("localhost", "root", "", "seb_portal");
} catch (Exception $e) {
    die("Connection Failed: " . $e->getMessage());
}

$msg = "";
$step = 1; // Track if we are on Step 1 (Send OTP) or Step 2 (Verify & Reset)

// STEP 1: SEND OTP
if (isset($_POST['send_otp'])) {
    $value = trim($_POST['value']);
    
    if (empty($value)) {
        $msg = "❌ Please enter an email or phone number.";
    } else {
        $_SESSION['reset_value'] = $value;
        $_SESSION['otp'] = rand(100000, 999999); // 6-digit OTP is more secure
        $_SESSION['otp_time'] = time(); // Store time for expiration

        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['method'] = "email";
            $msg = "📧 (Demo) OTP sent to your email: " . $_SESSION['otp'];
        } else {
            $_SESSION['method'] = "phone";
            $msg = "📱 (Demo) OTP sent to your phone: " . $_SESSION['otp'];
        }
        $step = 2; // Move to the password reset form
    }
}

// STEP 2: RESET PASSWORD
if (isset($_POST['reset'])) {
    $user_otp = $_POST['otp'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // Validation
    if ($user_otp != $_SESSION['otp']) {
        $msg = "❌ Invalid OTP.";
        $step = 2;
    } elseif ($new_pass !== $confirm_pass) {
        $msg = "❌ Passwords do not match.";
        $step = 2;
    } elseif (strlen($new_pass) < 6) {
        $msg = "❌ Password must be at least 6 characters.";
        $step = 2;
    } else {
        $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
        $identifier = $_SESSION['reset_value'];

        // USE PREPARED STATEMENTS TO PREVENT SQL INJECTION
        if ($_SESSION['method'] == "email") {
            $stmt = $conn->prepare("UPDATE students SET password = ? WHERE email = ?");
        } else {
            $stmt = $conn->prepare("UPDATE students SET password = ? WHERE phone = ?");
        }

        $stmt->bind_param("ss", $hashed_pass, $identifier);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $msg = "✅ Password Reset Successful! <a href='login.php'>Login here</a>";
            session_destroy(); // Clear OTP session
            $step = 1; 
        } else {
            $msg = "❌ Reset Failed. Account not found.";
            $step = 1;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Password Reset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(to right, #141e30, #243b55); min-height: 100vh; color: #333; }
        .card { border-radius: 15px; border: none; }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card p-4 shadow-lg">
                <h3 class="text-center mb-4">Reset Password</h3>

                <?php if ($msg): ?>
                    <div class="alert alert-info"><?php echo $msg; ?></div>
                <?php endif; ?>

                <?php if ($step == 1): ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email or Phone</label>
                            <input type="text" class="form-control" name="value" placeholder="e.g. user@example.com" required>
                        </div>
                        <button type="submit" name="send_otp" class="btn btn-warning w-100">Send Reset Code</button>
                    </form>
                <?php else: ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Verification Code</label>
                            <input type="text" class="form-control" name="otp" placeholder="6-digit code" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" name="reset" class="btn btn-success w-100">Update Password</button>
                        <div class="text-center mt-2">
                            <small><a href="?" class="text-muted">Restart Process</a></small>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="text-center mt-4">
                    <a href="login.php" class="text-decoration-none">← Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
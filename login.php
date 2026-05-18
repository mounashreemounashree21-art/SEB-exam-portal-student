<?php
session_start();

// DB CONNECTION
$conn = new mysqli("localhost", "root", "", "seb_portal");

if($conn->connect_error){
    die("Connection Failed: " . $conn->connect_error);
}

// LOGIN
if(isset($_POST['login'])){

    $email = $_POST['email'];
    $password = $_POST['password'];

    // SECURE: Use Prepared Statements to prevent SQL Injection
    $stmt = $conn->prepare("SELECT id, name, password FROM students WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();

        if(password_verify($password, $row['password'])){
            $_SESSION['student_id'] = $row['id'];
            $_SESSION['name'] = $row['name'];

            header("Location: dashboard.php");
            exit(); // Always exit after a header redirect
        } else {
            $msg = "❌ Incorrect password. Please try again.";
            $msg_type = "danger";
        }
    } else {
        $msg = "❌ No account found with that email address.";
        $msg_type = "warning";
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SEB Candidate Login</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        body {
            background-color: #f4f7f6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-wrapper {
            max-width: 900px;
            width: 100%;
            margin: 2rem;
        }

        .login-card {
            border: none;
            border-radius: 1.5rem;
            overflow: hidden;
            background: #ffffff;
        }

        /* Left Side Branding Area */
        .brand-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }

        .brand-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
            transform: translate(30%, 30%);
        }

        /* Right Side Form Area */
        .form-section {
            padding: 3rem 4rem;
        }

        .form-control {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.5rem; /* Slightly smaller radius to match smaller size */
            padding: 0.5rem 0.75rem; /* Reduced padding here */
            transition: all 0.3s;
        }

        .form-control:focus {
            background-color: #ffffff;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
            border-color: #86b7fe;
        }

        .form-floating > label {
            padding-left: 1rem;
            color: #6c757d;
        }

        .btn-login {
            background-color: #0d6efd;
            border: none;
            border-radius: 0.5rem; /* Slightly smaller radius to match smaller size */
            padding: 0.5rem; /* Reduced padding here */
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            background-color: #0b5ed7;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
            font-size: 1.1rem; /* Slightly smaller icon */
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: #0d6efd;
        }

        .text-link {
            color: #0d6efd;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        .text-link:hover {
            color: #0a58ca;
            text-decoration: underline;
        }

        /* Mobile adjustments */
        @media (max-width: 768px) {
            .form-section { padding: 2rem; }
            .brand-section { padding: 2rem; text-align: center; }
        }
    </style>
</head>

<body>

<div class="login-wrapper">
    <div class="card login-card shadow-lg">
        <div class="row g-0">
            
            <div class="col-md-5 brand-section d-none d-md-flex">
                <div class="mb-4">
                    <i class="bi bi-mortarboard-fill" style="font-size: 3rem;"></i>
                </div>
                <h2 class="fw-bold mb-3">Welcome to SEB Portal</h2>
                <p class="opacity-75 mb-0">Access your candidate dashboard, manage your profile, and track your application status all in one place.</p>
            </div>

            <div class="col-md-7 form-section">
                
                <div class="mb-4 text-center text-md-start">
                    <h3 class="fw-bold mb-1">Sign In</h3>
                    <p class="text-muted small">Please enter your credentials to continue</p>
                </div>

                <?php if(isset($msg)){ ?>
                    <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo $msg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php } ?>

                <form method="POST">
                    
                    <div class="mb-3">
                        <label for="standardEmail" class="form-label text-muted small"><i class="bi bi-envelope me-2"></i>Email address</label>
                        <input type="email" 
                               class="form-control" 
                               id="standardEmail" 
                               name="email" 
                               placeholder="Enter your email" 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                               required>
                    </div>

                    <div class="mb-4">
                        <label for="floatingPassword" class="form-label text-muted small"><i class="bi bi-lock me-2"></i>Password</label>
                        
                        <div class="position-relative">
                            <input type="password" 
                                   class="form-control pe-5" 
                                   id="floatingPassword" 
                                   name="password" 
                                   placeholder="Enter your password" 
                                   value="<?php echo isset($_POST['password']) ? htmlspecialchars($_POST['password']) : ''; ?>" 
                                   required>
                            
                            <i class="bi bi-eye-slash password-toggle" id="togglePassword" onclick="toggleVisibility()"></i>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="rememberMe">
                            <label class="form-check-label text-muted small" for="rememberMe">
                                Remember me
                            </label>
                        </div>
                        <a href="forgot_password.php" class="text-link small">Forgot password?</a>
                    </div>

                    <button type="submit" name="login" class="btn btn-login btn-primary w-100 text-white mb-4">
                        Login to Portal
                    </button>
                </form>

                <div class="text-center mt-auto">
                    <p class="text-muted small mb-0">Don't have an account yet? <a href="register.php" class="text-link fw-bold">Register here</a></p>
                </div>

            </div>
            
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function toggleVisibility() {
        const passwordInput = document.getElementById('floatingPassword');
        const toggleIcon = document.getElementById('togglePassword');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('bi-eye-slash');
            toggleIcon.classList.add('bi-eye');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('bi-eye');
            toggleIcon.classList.add('bi-eye-slash');
        }
    }
</script>

</body>
</html>
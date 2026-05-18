<?php
session_start();

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "seb_portal");
if ($conn->connect_error) { die("Connection Failed: " . $conn->connect_error); }

$student_id = $_SESSION['student_id'];
$app_id = isset($_GET['id']) ? $conn->real_escape_string($_GET['id']) : '';
$msg = "";

// Handle Form Submission
if (isset($_POST['update_app'])) {
    $designation = $conn->real_escape_string($_POST['designation']);
    
    // Update query (Add any other fields you allow users to edit here)
    $update_sql = "UPDATE applications SET designation='$designation' WHERE application_id='$app_id' AND student_id='$student_id' AND status != 'Verified'";
    
    if ($conn->query($update_sql)) {
        $msg = "<div class='alert alert-success'>Application updated successfully!</div>";
    } else {
        $msg = "<div class='alert alert-danger'>Error updating application.</div>";
    }
}

// Fetch Current Data
$sql = "SELECT * FROM applications WHERE application_id = '$app_id' AND student_id = '$student_id'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("<div class='container mt-5'><div class='alert alert-danger'>Application not found. <a href='forms_status.php'>Go Back</a></div></div>");
}

$app = $result->fetch_assoc();

// Prevent editing if verified
if (strtolower($app['status']) == 'verified') {
    die("<div class='container mt-5'><div class='alert alert-warning'>Verified applications cannot be modified. <a href='forms_status.php'>Go Back</a></div></div>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Modify Application | SEB Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .navbar { background-color: #1a237e !important; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fas fa-shield-alt me-2"></i>SEB PORTAL</a>
            <a href="forms_status.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Cancel</a>
        </div>
    </nav>

    <div class="container py-5" style="max-width: 700px;">
        <?php echo $msg; ?>
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom p-4">
                <h4 class="mb-0 fw-bold text-dark"><i class="fas fa-edit text-warning me-2"></i> Modify Application</h4>
                <small class="text-muted">App ID: <?php echo htmlspecialchars($app['application_id']); ?></small>
            </div>
            <div class="card-body p-4 p-md-5">
                <form action="" method="POST">
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold">Examination Name (Read-Only)</label>
                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($app['exam_name']); ?>" readonly>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label text-dark fw-bold">Designation / Stream *</label>
                        <input type="text" name="designation" class="form-control" value="<?php echo htmlspecialchars($app['designation'] ?? ''); ?>" required>
                    </div>
                    
                    <button type="submit" name="update_app" class="btn btn-primary w-100 py-2 fw-bold" style="background-color: #1a237e;">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
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

// Fetch Application and Student Details
$sql = "SELECT a.*, s.name, s.email, s.phone 
        FROM applications a 
        JOIN students s ON a.student_id = s.id 
        WHERE a.application_id = '$app_id' AND a.student_id = '$student_id'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("<div class='container mt-5'><div class='alert alert-danger'>Application not found or unauthorized access. <a href='forms_status.php'>Go Back</a></div></div>");
}

$app = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Application | SEB Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .navbar { background-color: #1a237e !important; }
        .data-label { font-size: 0.8rem; color: #6c757d; text-transform: uppercase; font-weight: 600; }
        .data-value { font-size: 1rem; color: #212529; font-weight: 500; margin-bottom: 1.5rem; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fas fa-shield-alt me-2"></i>SEB PORTAL</a>
            <a href="forms_status.php" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
        </div>
    </nav>

    <div class="container py-5" style="max-width: 800px;">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                <h4 class="mb-0 fw-bold text-dark"><i class="fas fa-file-alt text-primary me-2"></i> Application Details</h4>
                <span class="badge bg-secondary px-3 py-2"><?php echo strtoupper($app['status']); ?></span>
            </div>
            <div class="card-body p-4 p-md-5">
                <div class="row">
                    <div class="col-md-6">
                        <div class="data-label">Application ID</div>
                        <div class="data-value"><?php echo htmlspecialchars($app['application_id']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="data-label">Applied Date</div>
                        <div class="data-value"><?php echo date('d M, Y', strtotime($app['created_at'])); ?></div>
                    </div>
                    <div class="col-md-12">
                        <div class="data-label">Examination Name</div>
                        <div class="data-value fs-5 text-primary"><?php echo htmlspecialchars($app['exam_name']); ?></div>
                    </div>
                    <hr class="my-4 text-muted">
                    <div class="col-md-6">
                        <div class="data-label">Candidate Name</div>
                        <div class="data-value"><?php echo htmlspecialchars($app['name']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="data-label">Designation Applied For</div>
                        <div class="data-value"><?php echo htmlspecialchars($app['designation'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="data-label">Email Address</div>
                        <div class="data-value"><?php echo htmlspecialchars($app['email']); ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="data-label">Phone Number</div>
                        <div class="data-value"><?php echo htmlspecialchars($app['phone']); ?></div>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-light p-3 text-center">
                <a href="print_form.php?id=<?php echo $app['application_id']; ?>" class="btn btn-secondary px-4"><i class="fas fa-print me-2"></i> Print Copy</a>
            </div>
        </div>
    </div>

</body>
</html>
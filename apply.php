<?php
session_start();

// 1. Database Connection
$conn = new mysqli("localhost","root","","seb_portal");
if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

// 2. Protection: Ensure user is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// 3. Fetch Candidate Info for the Header
$res = $conn->query("SELECT * FROM students WHERE id='$student_id'");
$student = $res->fetch_assoc();

$msg = "";
$msg_class = "";

// 4. Form Submission Logic
if (isset($_POST['submit_exam'])) {

    $exam_name     = mysqli_real_escape_string($conn, $_POST['exam_name'] ?? '');
    $session       = mysqli_real_escape_string($conn, $_POST['session'] ?? '2026 Session');
    $dob           = mysqli_real_escape_string($conn, $_POST['dob'] ?? '');
    $gender        = mysqli_real_escape_string($conn, $_POST['gender'] ?? '');
    $category      = mysqli_real_escape_string($conn, $_POST['category'] ?? '');
    $qualification = mysqli_real_escape_string($conn, $_POST['qualification'] ?? '');
    $district      = mysqli_real_escape_string($conn, $_POST['district'] ?? '');
    $aadhaar       = mysqli_real_escape_string($conn, $_POST['aadhaar_no'] ?? '');

    // File Upload Configuration
    $upload_path = "../uploads/";
    if (!is_dir($upload_path)) { mkdir($upload_path, 0777, true); }

    $photo_doc = "PHOTO_" . time() . "_" . ($_FILES['photo_doc']['name'] ?? 'file');
    $sig_doc   = "SIG_" . time() . "_" . ($_FILES['signature']['name'] ?? 'file');
    $id_doc    = "ID_" . time() . "_" . ($_FILES['id_doc']['name'] ?? 'file');

    $tmp_photo = $_FILES['photo_doc']['tmp_name'] ?? '';
    $tmp_sig   = $_FILES['signature']['tmp_name'] ?? '';
    $tmp_id    = $_FILES['id_doc']['tmp_name'] ?? '';

    if (!empty($tmp_photo) && !empty($tmp_sig) && !empty($tmp_id)) {

        move_uploaded_file($tmp_photo, $upload_path . $photo_doc);
        move_uploaded_file($tmp_sig, $upload_path . $sig_doc);
        move_uploaded_file($tmp_id, $upload_path . $id_doc);

        $app_id = "SEB-APP-" . date("Ymd") . "-" . rand(100, 999);

        $sql = "INSERT INTO applications 
                (student_id, application_id, exam_name, session, dob, gender, category, qualification, district, photo_doc, signature, id_doc, status, created_at)
                VALUES 
                ('$student_id', '$app_id', '$exam_name', '$session', '$dob', '$gender', '$category', '$qualification', '$district', '$photo_doc', '$sig_doc', '$id_doc', 'Pending', NOW())";

        if ($conn->query($sql)) {
            $msg = "Success: Application <strong>$app_id</strong> registered in the secure database.";
            $msg_class = "alert-success";
        } else {
            $msg = "System Error: " . $conn->error;
            $msg_class = "alert-danger";
        }
    } else {
        $msg = "Error: Please upload all required documents.";
        $msg_class = "alert-warning";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Application | SEB Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --seb-blue: #1a237e; }
        body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .seb-navbar { background-color: var(--seb-blue) !important; padding: 12px 0; min-height: 60px; }
        .navbar-brand { font-weight: bold; font-size: 1.5rem; display: flex; align-items: center; letter-spacing: 0.5px; }
        .dashboard-header { background: white; padding: 25px 0; border-bottom: 2px solid #e0e0e0; margin-bottom: 30px; }
        .status-badge { background-color: #2e7d32; color: white; padding: 4px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; }
        .application-container { background: white; border-radius: 12px; border: 1px solid #e0e0e0; }
        .section-bar { background: #f8f9fa; border-left: 5px solid var(--seb-blue); padding: 10px 15px; margin: 25px 0 15px 0; font-weight: bold; color: var(--seb-blue); text-transform: uppercase; font-size: 0.85rem; }
        .footer { background-color: #212121; color: #8d99ae; padding: 25px 0; margin-top: 50px; }
        
        /* Mobile specific adjustments */
        @media (max-width: 768px) {
            .btn-mobile-reduce {
                padding: 0.6rem 1rem !important;
                font-size: 0.95rem !important;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-dark seb-navbar shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">
            <i class="fas fa-shield-alt me-2"></i> SEB PORTAL
        </a>
    </div>
</nav>

<div class="dashboard-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2 class="fw-bold mb-1">Apply for Exam</h2>
                <p class="text-muted mb-0">Welcome back, <strong><?php echo htmlspecialchars($student['name']); ?></strong> (ID: SEB-2026-<?php echo $student_id; ?>)</p>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <div class="application-container shadow-sm p-4 p-md-5">

                <?php if($msg != ""): ?>
                    <div class="alert <?php echo $msg_class; ?> mb-4 shadow-sm" role="alert">
                         <i class="fas fa-info-circle me-2"></i> <?php echo $msg; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <div class="section-bar">1. Candidate Identity (System Verified)</div>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input class="form-control bg-light" value="<?php echo htmlspecialchars($student['name']); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Registered Email</label>
                            <input class="form-control bg-light" value="<?php echo htmlspecialchars($student['email']); ?>" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Registered Phone</label>
                            <input class="form-control bg-light" value="<?php echo htmlspecialchars($student['phone']); ?>" readonly>
                        </div>
                    </div>

                    <div class="section-bar">2. Demographic Details</div>
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Date of Birth</label>
                            <input type="date" class="form-control" name="dob" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Gender</label>
                            <select class="form-select" name="gender" required>
                                <option value="">--Select--</option>
                                <option>Male</option>
                                <option>Female</option>
                                <option>Non-Binary</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Category</label>
                            <select class="form-select" name="category" required>
                                <option value="">--Select--</option>
                                <option>General</option>
                                <option>OBC</option>
                                <option>SC/ST</option>
                                <option>EWS</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Aadhaar / National ID No.</label>
                            <input type="text" class="form-control" name="aadhaar_no" placeholder="0000 0000 0000" required>
                        </div>
                    </div>

                    <div class="section-bar">3. Exam & Local Government Selection</div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Select Examination Category</label>
                            <select class="form-select border-primary" name="exam_name" required>
                                <option value="">-- Select Available Exam --</option>
                                <optgroup label="Local Government (District level)">
                                    <option>Panchayat Executive Officer (PEO)</option>
                                    <option>Municipal Clerk Recruitment</option>
                                    <option>Village Revenue Assistant</option>
                                </optgroup>
                                <optgroup label="State Level Entrance">
                                    <option>Combined Competitive Exam (CCE)</option>
                                    <option>State Teacher Eligibility (STET)</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Academic Qualification</label>
                            <select class="form-select" name="qualification" required>
                                <option>High School</option>
                                <option>Intermediate</option>
                                <option selected>Graduate</option>
                                <option>Post-Graduate</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Target District</label>
                            <select class="form-select border-primary" name="district" required>
                                <option value="">Select Local Unit</option>
                                <option>District North</option>
                                <option>District South</option>
                                <option>District East</option>
                                <option>District West</option>
                            </select>
                        </div>
                    </div>

                    <input type="hidden" name="session" value="Academic 2026">

                    <div class="section-bar">4. Biometric & Document Uploads</div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Passport Photo</label>
                            <input type="file" class="form-control" name="photo_doc" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Digital Signature</label>
                            <input type="file" class="form-control" name="signature" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">ID Document (PDF/JPG)</label>
                            <input type="file" class="form-control" name="id_doc" required>
                        </div>
                    </div>

                    <div class="mt-5">
                        <button type="submit" name="submit_exam" class="btn btn-primary btn-lg fw-bold w-100 mb-3 btn-mobile-reduce" style="background-color: var(--seb-blue); border: none;">
                            FINAL SUBMIT APPLICATION
                        </button>
                        
                        <div class="text-start">
                            <a href="dashboard.php" class="text-decoration-none text-secondary small fw-bold">
                                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="container text-center text-white-50">
        <p class="mb-0 small">&copy; 2026 SEB Portal - Secure Exam Browser Management System</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
session_start();

// 1. Database Connection
$conn = new mysqli("localhost", "root", "", "seb_portal");
if ($conn->connect_error) { 
    die("Connection Failed: " . $conn->connect_error); 
}

// =========================================================
// AJAX Image Upload Handler (Runs in the background)
// =========================================================
if (isset($_FILES['ajax_profile_photo'])) {
    header('Content-Type: application/json');
    if (!isset($_SESSION['student_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Session expired.']);
        exit();
    }
    
    $student_id = $_SESSION['student_id'];
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

    if ($_FILES['ajax_profile_photo']['error'] == 0) {
        $filename = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES["ajax_profile_photo"]["name"]));
        $target_file = $target_dir . $filename;
        $ext = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        if (in_array($ext, ['jpg', 'jpeg', 'png']) && move_uploaded_file($_FILES["ajax_profile_photo"]["tmp_name"], $target_file)) {
            $conn->query("UPDATE students SET profile_photo='$target_file' WHERE id='$student_id'");
            echo json_encode(['status' => 'success', 'path' => $target_file]);
            exit();
        }
    }
    echo json_encode(['status' => 'error', 'message' => 'Invalid file or upload failed.']);
    exit();
}
// =========================================================

// 2. Protection: Ensure user is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// 3. Fetch Real Candidate Info from Database
$res = $conn->query("SELECT * FROM students WHERE id='$student_id'");
if($res->num_rows > 0){
    $student = $res->fetch_assoc();
    $candidate_name = htmlspecialchars($student['name']);
    $application_id = "SEB-2026-" . str_pad($student['id'], 5, "0", STR_PAD_LEFT);
    
    // Dynamic Profile Photo Check
    if (!empty($student['profile_photo'])) {
        $profile_photo = htmlspecialchars($student['profile_photo']) . "?v=" . time();
    } else {
        $profile_photo = "https://ui-avatars.com/api/?name=" . urlencode($candidate_name) . "&background=1a237e&color=fff";
    }
} else {
    $candidate_name = "Guest User";
    $application_id = "N/A";
    $profile_photo = "https://ui-avatars.com/api/?name=Guest&background=ccc&color=fff";
}

// 4. Fetch Notification Count
$notif_res = $conn->query("SELECT COUNT(*) as unread FROM notifications WHERE (student_id='$student_id' OR student_id=0) AND status='unread'");
$notif_count = ($notif_res) ? $notif_res->fetch_assoc()['unread'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidate Portal | SEB Portal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Minimal Custom CSS to supplement Bootstrap */
        :root { 
            --seb-blue: #1a237e; 
        }
        body { 
            background-color: #f4f7f6; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        
        /* SEB Custom Colors */
        .bg-seb-blue { background-color: var(--seb-blue) !important; }
        .text-seb-blue { color: var(--seb-blue) !important; }
        .btn-seb-blue { background-color: var(--seb-blue); color: white; border: none; }
        .btn-seb-blue:hover { background-color: #121858; color: white; }

        /* Profile Image Edit Overlay */
        .profile-wrapper { position: relative; width: 80px; height: 80px; cursor: pointer; border-radius: 50%; }
        .profile-img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; border: 3px solid #eee; }
        .edit-overlay {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.5); border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            opacity: 0; transition: opacity 0.2s ease; color: white; font-size: 0.85rem;
        }
        .profile-wrapper:hover .edit-overlay { opacity: 1; }

        /* Card Hover Effect */
        .card-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; border-radius: 12px; }
        .card-hover:hover { transform: translateY(-5px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-seb-blue shadow-sm py-2">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">
                <i class="fas fa-shield-alt me-2"></i>SEB PORTAL
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-end align-items-lg-center py-3 py-lg-0">
                    <li class="nav-item me-lg-3">
                        <a class="nav-link active" href="dashboard.php"><i class="fas fa-home me-2"></i> Home</a>
                    </li>
                    <li class="nav-item me-lg-4 position-relative">
                        <a class="nav-link" href="notifications.php">
                            <i class="fas fa-bell me-2"></i> Notifications
                            <?php if($notif_count > 0): ?>
                                <span class="position-absolute top-25 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;"><?php echo $notif_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item mt-2 mt-lg-0">
                        <a class="btn btn-danger btn-sm px-4" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="bg-white py-4 border-bottom shadow-sm mb-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                
                <div class="d-flex align-items-center gap-3">
                    <div class="profile-wrapper shadow-sm" title="Update Profile Photo" onclick="document.getElementById('hidden-photo-input').click();">
                        <img id="user-profile-img" src="<?php echo $profile_photo; ?>" alt="Profile" class="profile-img">
                        <div class="edit-overlay" id="edit-overlay-ui"><i class="fas fa-camera"></i> Edit</div>
                    </div>
                    <input type="file" id="hidden-photo-input" accept="image/png, image/jpeg, image/jpg" class="d-none" onchange="uploadPhotoDirectly(this)">
                    <div>
                        <h4 class="fw-bold mb-0 text-dark"><?php echo $candidate_name; ?></h4>
                        <p class="text-muted mb-0 small">ID: <?php echo $application_id; ?></p>
                    </div>
                </div>

                <div class="d-none d-md-block text-seb-blue fw-bold" style="letter-spacing: 2px;">
                    CANDIDATE PORTAL
                </div>
                
                <div class="d-none d-md-block" style="width: 200px;"></div>

            </div>
        </div>
    </div>

    <div class="container pb-5">
        <div class="row g-4">
            
            <div class="col-md-4">
                <div class="card card-hover h-100 border-0 shadow-sm p-4 text-center">
                    <div class="display-5 text-secondary mb-3"><i class="fas fa-user-edit"></i></div>
                    <h5 class="fw-bold text-dark">Update Profile</h5>
                    <p class="text-muted small mb-4">Keep your personal details and category up to date.</p>
                    <a href="profile.php" class="btn btn-outline-secondary w-100 mt-auto rounded-3 py-2">Edit Profile</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-hover h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid var(--seb-blue) !important;">
                    <div class="display-5 text-seb-blue mb-3"><i class="fas fa-bell"></i></div>
                    <h5 class="fw-bold text-dark">Notifications</h5>
                    <p class="text-muted small mb-4">View upcoming exam details, syllabus, and important alerts.</p>
                    <a href="notifications.php" class="btn btn-seb-blue w-100 mt-auto rounded-3 py-2">View Updates</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-hover h-100 border-0 shadow-sm p-4 text-center">
                    <div class="display-5 text-primary mb-3"><i class="fas fa-file-signature"></i></div>
                    <h5 class="fw-bold text-dark">Apply for Exam</h5>
                    <p class="text-muted small mb-4">Fill out registration forms for upcoming examinations.</p>
                    <a href="apply.php" class="btn btn-outline-primary w-100 mt-auto rounded-3 py-2">Go to Application</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-hover h-100 border-0 shadow-sm p-4 text-center">
                    <div class="display-5 text-info mb-3"><i class="fas fa-folder-open"></i></div>
                    <h5 class="fw-bold text-dark">Forms & Status</h5>
                    <p class="text-muted small mb-4">Review your submitted applications and track verification progress.</p>
                    <a href="forms_status.php" class="btn btn-outline-info w-100 mt-auto rounded-3 py-2">View & Track</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-hover h-100 border-0 shadow-sm p-4 text-center" style="border-top: 4px solid #ffc107 !important;">
                    <div class="display-5 text-warning mb-3"><i class="fas fa-id-card-alt"></i></div>
                    <h5 class="fw-bold text-dark">Admit Card</h5>
                    <p class="text-muted small mb-4">Download and print your hall ticket for examinations.</p>
                    <a href="admit_card.php" class="btn btn-warning text-white w-100 mt-auto rounded-3 py-2 fw-bold">Download Now</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-hover h-100 border-0 shadow-sm p-4 text-center">
                    <div class="display-5 text-success mb-3"><i class="fas fa-poll"></i></div>
                    <h5 class="fw-bold text-dark">Exam Results</h5>
                    <p class="text-muted small mb-4">Check your scores, merit rank, and qualifying status.</p>
                    <a href="results.php" class="btn btn-outline-success w-100 mt-auto rounded-3 py-2">View Results</a>
                </div>
            </div>

        </div>
    </div>

    <footer class="bg-dark text-white-50 text-center py-4 mt-auto w-100">
        <div class="container">
            <p class="mb-1 text-white fw-bold">Secure exam browser (SEB)</p>
            <p class="small mb-2">Official Portal for Entrance and Competitive Examinations</p>
            <p class="small mb-0">&copy; <?php echo date("Y"); ?> SEB Portal. All data is secured.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function uploadPhotoDirectly(input) {
            if (input.files && input.files[0]) {
                let formData = new FormData();
                formData.append('ajax_profile_photo', input.files[0]);

                let overlay = document.getElementById('edit-overlay-ui');
                overlay.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                overlay.style.opacity = '1';

                fetch('dashboard.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        document.getElementById('user-profile-img').src = data.path + '?v=' + new Date().getTime();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    alert('Upload failed due to a network error.');
                })
                .finally(() => {
                    overlay.innerHTML = '<i class="fas fa-camera"></i> Edit';
                    overlay.style.opacity = '';
                    input.value = ''; 
                });
            }
        }
    </script>
</body>
</html>
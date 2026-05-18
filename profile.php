<?php
session_start();

// 1. Database Connection
$conn = new mysqli("localhost", "root", "", "seb_portal");
if ($conn->connect_error) { 
    die("Connection Failed: " . $conn->connect_error); 
}

// 2. Protection: Ensure user is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$msg = "";
$msg_class = "";

// 4. Fetch Current Student Data (Moved up to access current photo path)
$res_student = $conn->query("SELECT * FROM students WHERE id='$student_id'");
$student = $res_student->fetch_assoc();

// 3. Handle Form Update
if (isset($_POST['update_profile'])) {
    $name  = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    
    // Default to existing photo if no new photo is provided
    $photo_path = $student['profile_photo'] ?? ''; 
    $target_dir = "uploads/";

    // Create uploads directory if it doesn't exist
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    // A. Handle Manual File Upload
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $filename = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "", basename($_FILES["profile_photo"]["name"]));
        $target_file = $target_dir . $filename;
        
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
                $photo_path = $target_file;
            }
        } else {
            $msg = "Invalid file type. Only JPG, JPEG, PNG allowed.";
            $msg_class = "alert-danger";
        }
    } 
    // B. Handle Webcam Base64 Capture
    elseif (!empty($_POST['photo_base64'])) {
        $img = $_POST['photo_base64'];
        $img = str_replace('data:image/png;base64,', '', $img);
        $img = str_replace(' ', '+', $img);
        $data = base64_decode($img);
        $filename = time() . '_webcam_' . $student_id . '.png';
        $target_file = $target_dir . $filename;
        
        if (file_put_contents($target_file, $data)) {
            $photo_path = $target_file;
        }
    }

    // Only proceed to update if there are no errors so far
    if (empty($msg)) {
        $update_sql = "UPDATE students SET name='$name', email='$email', phone='$phone', profile_photo='$photo_path' WHERE id='$student_id'";

        if ($conn->query($update_sql)) {
            $msg = "Profile updated successfully!";
            $msg_class = "alert-success";
            // Refresh student data after update
            $res_student = $conn->query("SELECT * FROM students WHERE id='$student_id'");
            $student = $res_student->fetch_assoc();
        } else {
            $msg = "Error updating profile: " . $conn->error;
            $msg_class = "alert-danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Registration | SEB Portal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

    <style>
        /* (Keep your existing CSS exactly as it was) */
        :root {
            --seb-blue: #1a237e;
            --seb-orange: #f37021;
            --bg-light: #f4f7f6;
            --surface-container: #eceef0;
            --outline-variant: #c1c7d0;
        }

        body {
            font-family: 'Public Sans', sans-serif;
            background-color: var(--bg-light);
            color: #191c1e;
        }

        .seb-header {
            background-color: var(--seb-blue);
            color: white;
            padding: 1rem 1.5rem;
        }

        .form-label-custom {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #42474f;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control-custom {
            background-color: var(--surface-container);
            border: none;
            padding: 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .form-control-custom:focus {
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(26, 35, 126, 0.1);
            outline: none;
        }

        .capture-box {
            width: 100%;
            max-width: 260px;
            height: 320px;
            background-color: var(--surface-container);
            border: 2px dashed var(--outline-variant);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }

        #video-feed, #photo-result {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .btn-seb-main {
            background: var(--seb-blue);
            color: white;
            padding: 1.25rem 3rem;
            border-radius: 50px;
            font-weight: 700;
            border: none;
            box-shadow: 0 10px 20px rgba(26, 35, 126, 0.2);
        }

        .btn-seb-main:hover {
            transform: translateY(-2px);
            color: white;
            box-shadow: 0 15px 25px rgba(26, 35, 126, 0.3);
        }

        .section-number {
            color: var(--seb-orange);
            font-weight: 800;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.2em;
            display: block;
            margin-bottom: 0.5rem;
        }

        hr { opacity: 0.1; margin: 3rem 0; }
        
        .registration-card {
            background: white;
            border-radius: 1.5rem;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.03);
        }

        .seb-footer {
            background: #212121;
            color: #bbb;
            padding: 30px 0;
            margin-top: 50px;
        }

        /* Styling for the new back button */
        .back-link {
            color: #6c757d;
            transition: color 0.2s ease;
        }
        .back-link:hover {
            color: var(--seb-blue);
        }
    </style>
</head>
<body>

<header class="seb-header sticky-top shadow-sm">
     <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-shield-alt fa-lg"></i>
            <h1 class="h4 mb-0 fw-bold">SEB PORTAL</h1>
            <span class="ms-3 d-none d-md-inline opacity-75">| Candidate Portal</span>
        </div>
        <div>
            <a href="dashboard.php" class="btn btn-outline-light btn-sm me-2">Dashboard</a>
        </div>
    </div>
</header>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <div class="text-center mb-5">
                <h2 class="display-6 fw-bold" style="color: var(--seb-blue);">Complete your profile</h2>
                <p class="text-muted mx-auto" style="max-width: 600px;">Welcome to the Secure Examination Browser. Your accurate information helps us ensure a secure and verified network for all candidates.</p>
            </div>

            <?php if($msg != ""): ?>
                <div class="alert <?php echo $msg_class; ?> alert-dismissible fade show mb-4" role="alert">
                    <i class="fas fa-info-circle me-2"></i> <?php echo $msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="registration-card">
                <form action="#" method="POST" enctype="multipart/form-data">
                    
                    <section>
                        <span class="section-number">Section 01</span>
                        <h3 class="fw-bold mb-4" style="color: var(--seb-blue);">Personal Details</h3>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label-custom">Legal Full Name*</label>
                                <input type="text" name="name" class="form-control form-control-custom" value="<?php echo htmlspecialchars($student['name'] ?? ''); ?>" placeholder="As per Aadhaar" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Gender Identity*</label>
                                <select class="form-select form-control-custom" required>
                                    <option value="" selected disabled>Select Option</option>
                                    <option>Male</option>
                                    <option>Female</option>
                                    <option>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Phone Number*</label>
                                <input type="tel" name="phone" class="form-control form-control-custom" value="<?php echo htmlspecialchars($student['phone'] ?? ''); ?>" placeholder="+91" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Email Address*</label>
                                <input type="email" name="email" class="form-control form-control-custom" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                    </section>

                    <hr>

                    <section>
                        <span class="section-number">Section 02</span>
                        <h3 class="fw-bold mb-4" style="color: var(--seb-blue);">Identity & Address</h3>
                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label-custom">Full Address*</label>
                                <input type="text" class="form-control form-control-custom" placeholder="House/Flat No, Street Name" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">City*</label>
                                <input type="text" class="form-control form-control-custom" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">District*</label>
                                <input type="text" class="form-control form-control-custom" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label-custom">Pincode*</label>
                                <input type="text" class="form-control form-control-custom" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">Aadhaar (UID)*</label>
                                <input type="text" class="form-control form-control-custom" placeholder="0000 0000 0000" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label-custom">PAN Number*</label>
                                <input type="text" class="form-control form-control-custom" placeholder="ABCDE1234F" required>
                            </div>
                        </div>
                    </section>

                    <hr>

                    <section class="mb-5">
                        <span class="section-number">Section 03</span>
                        <h3 class="fw-bold mb-4" style="color: var(--seb-blue);">Verification Assets</h3>
                        <div class="row g-4">
                            <div class="col-md-5 text-center">
                                <label class="form-label-custom">ID Profile Photo*</label>
                                
                                <input type="hidden" name="photo_base64" id="photo_base64">

                                <div class="capture-box mx-auto">
                                    <video id="video-feed" autoplay playsinline></video>
                                    
                                    <img id="photo-result" alt="Result" 
                                         src="<?php echo !empty($student['profile_photo']) ? $student['profile_photo'] : ''; ?>" 
                                         style="display: <?php echo !empty($student['profile_photo']) ? 'block' : 'none'; ?>;">
                                    
                                    <div id="placeholder-ui" style="display: <?php echo !empty($student['profile_photo']) ? 'none' : 'block'; ?>;">
                                        <span class="material-symbols-outlined display-4 text-muted">photo_camera</span>
                                        <p class="small fw-bold text-muted mt-2">Take live photo</p>
                                    </div>
                                </div>
                                <div class="mt-3 d-grid gap-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="initCamera()">Open Camera</button>
                                    <button type="button" id="snap-btn" class="btn btn-sm btn-success d-none" onclick="takePhoto()">Capture Snapshot</button>
                                    
                                    <input type="file" id="file-photo" name="profile_photo" class="d-none" accept="image/*" onchange="previewUpload(event)">
                                    <button type="button" class="btn btn-sm btn-link text-muted" onclick="document.getElementById('file-photo').click()">Or upload existing</button>
                                </div>
                            </div>
                            
                            <div class="col-md-7">
                                <div class="p-4 bg-light rounded-3 border h-100">
                                    <label class="form-label-custom">Resume / CV (Mandatory PDF)*</label>
                                    <input type="file" name="resume" class="form-control form-control-custom" accept=".pdf" required>
                                </div>
                            </div>
                        </div>
                    </section>

                    <footer class="pt-5 border-top d-md-flex justify-content-between align-items-center">
                        <div class="form-check mb-3 mb-md-0" style="max-width: 450px;">
                            <input class="form-check-input" type="checkbox" id="certify" required>
                            <label class="form-check-label small text-muted" for="certify">
                                I solemnly declare that all information including address, identity, and documents provided are true and accurate to the best of my knowledge.
                            </label>
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-seb-main shadow">Update Profile</button>
                    </footer>
                </form>
            </div>
            
            <div class="mt-4 text-start">
                <a href="dashboard.php" class="text-decoration-none small fw-semibold back-link">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>

        </div>
    </div>
</main>

<footer class="seb-footer text-center">
    <div class="container">
        <p class="mb-1 fw-bold">Secure Examination Browser (SEB)</p>
        <p class="small mb-4">Official Portal for Entrance and Competitive Examinations</p>
        <hr class="bg-secondary opacity-25">
        <p class="x-small text-secondary mb-0">&copy; <?php echo date("Y"); ?> SEB Portal. All data is encrypted and secured by NIC.</p>
    </div>
</footer>

<script>
    let video = document.getElementById('video-feed');
    let photoResult = document.getElementById('photo-result');
    let placeholder = document.getElementById('placeholder-ui');
    let snapBtn = document.getElementById('snap-btn');
    let base64Input = document.getElementById('photo_base64');
    let fileInput = document.getElementById('file-photo');

    async function initCamera() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            video.style.display = 'block';
            placeholder.style.display = 'none';
            photoResult.style.display = 'none';
            snapBtn.classList.remove('d-none');
            
            // Clear manual file input if camera is opened
            fileInput.value = ""; 
        } catch (err) {
            alert("Camera access denied or not available.");
        }
    }

    function takePhoto() {
        let canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        canvas.getContext('2d').drawImage(video, 0, 0);
        
        // Convert to Base64
        let dataUrl = canvas.toDataURL('image/png');
        
        // Show result on screen
        photoResult.src = dataUrl;
        photoResult.style.display = 'block';
        
        // STORE BASE64 DATA IN HIDDEN INPUT TO SUBMIT TO PHP
        base64Input.value = dataUrl;

        // Stop camera feed
        const stream = video.srcObject;
        stream.getTracks().forEach(track => track.stop());
        video.style.display = 'none';
        snapBtn.classList.add('d-none');
    }

    // Function to preview manually uploaded files
    function previewUpload(event) {
        let reader = new FileReader();
        reader.onload = function(){
            photoResult.src = reader.result;
            photoResult.style.display = 'block';
            placeholder.style.display = 'none';
            video.style.display = 'none';
            snapBtn.classList.add('d-none');
            
            // Clear webcam data if a file is manually uploaded
            base64Input.value = ""; 
        };
        if(event.target.files[0]) {
            reader.readAsDataURL(event.target.files[0]);
        }
    }
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
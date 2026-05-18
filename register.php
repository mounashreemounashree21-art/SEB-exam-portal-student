<?php
session_start();

// DB CONNECTION
$conn = new mysqli("localhost", "root", "", "seb_portal");

if($conn->connect_error){
    die("Connection Failed: " . $conn->connect_error);
}

// SEND OTP
if(isset($_POST['send_otp'])){
    $method = $_POST['otp_method'];
    $_SESSION['otp'] = rand(1000,9999);
    $_SESSION['method'] = $method;

    if($method == "email"){
        $_SESSION['value'] = $_POST['email'];
        $msg = "📧 Email OTP (Demo): ".$_SESSION['otp'];
        $msg_type = "success";
    } else {
        $_SESSION['value'] = $_POST['phone'];
        $msg = "📱 Phone OTP (Demo): ".$_SESSION['otp'];
        $msg_type = "success";
    }
}

// REGISTER
if(isset($_POST['register'])){
    if(!isset($_SESSION['otp'])){
        $msg = "❌ Please send OTP first";
        $msg_type = "danger";
    }
    elseif($_POST['otp'] != $_SESSION['otp']){
        $msg = "❌ Invalid OTP";
        $msg_type = "danger";
    }
    else{
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $dob = $_POST['dob'];
        $gender = isset($_POST['gender']) ? $_POST['gender'] : '';

        $current_address = trim($_POST['current_address']);
        $permanent_address = trim($_POST['permanent_address']);

        // COUNTRY, STATE, CITY LOGIC
        $country = ($_POST['country_select'] == "other") ? $_POST['country_other'] : $_POST['country_select'];
        $state = ($_POST['state_select'] == "other") ? $_POST['state_other'] : $_POST['state_select'];
        $city = ($_POST['city_select'] == "other") ? $_POST['city_other'] : $_POST['city_select'];

        $pincode = trim($_POST['pincode']);
        $aadhaar = trim($_POST['aadhaar']);

        // SERVER-SIDE VALIDATION
        if(empty($name) || empty($email) || empty($phone) || empty($current_address) || empty($aadhaar) || empty($_POST['password'])) {
            $msg = "❌ All mandatory fields must be filled.";
            $msg_type = "danger";
        } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
            $msg = "❌ Phone number must be exactly 10 digits.";
            $msg_type = "danger";
        } elseif (!preg_match('/^[0-9]{12}$/', $aadhaar)) {
            $msg = "❌ Aadhaar number must be exactly 12 digits.";
            $msg_type = "danger";
        } else {
            // PASSWORD HASH
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

            // PHOTO UPLOAD
            $photo = $_FILES['photo']['name'];
            $tmp = $_FILES['photo']['tmp_name'];
            $ext = strtolower(pathinfo($photo, PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png'];

            if(!in_array($ext, $allowed)){
                $msg = "❌ Only JPG, JPEG, PNG formats are allowed";
                $msg_type = "danger";
            }
            else{
                $newname = time().".".$ext;
                // Ensure the uploads directory exists
                if (!file_exists('../uploads')) {
                    mkdir('../uploads', 0777, true);
                }
                
                move_uploaded_file($tmp, "../uploads/".$newname);
                $address = $current_address." | ".$permanent_address.", ".$city.", ".$state.", ".$country." - ".$pincode;

                $stmt = $conn->prepare("INSERT INTO students (name, email, phone, dob, gender, address, aadhaar, password, photo) VALUES (?,?,?,?,?,?,?,?,?)");
                $stmt->bind_param("sssssssss", $name, $email, $phone, $dob, $gender, $address, $aadhaar, $password, $newname);

                if($stmt->execute()){
                    $msg = "✅ Registration Successful!";
                    $msg_type = "success";
                    unset($_SESSION['otp']); // Clear OTP on success
                } else {
                    $msg = "Error: ".$conn->error;
                    $msg_type = "danger";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal | Advanced Registration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .hidden-input { display: none; margin-top: 10px; }
        .section-title { font-size: 1.1rem; color: #0d6efd; border-bottom: 2px solid #e9ecef; padding-bottom: 5px; margin-bottom: 15px; margin-top: 20px; }
        .password-toggle { cursor: pointer; }
    </style>
</head>
<body class="bg-light py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card shadow-sm border-0 rounded-3">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h3 class="mb-0">Student Registration</h3>
                    <p class="mb-0 small">Please fill in the details below to create your account.</p>
                </div>
                
                <div class="card-body p-4">
                    <?php if(isset($msg)){ ?>
                        <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible fade show" role="alert">
                            <?php echo $msg; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php } ?>

                    <form id="regForm" method="POST" enctype="multipart/form-data" onsubmit="return validateForm(event)" novalidate>

                        <h4 class="section-title mt-0">Personal Details</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="name" placeholder="John Doe">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date of Birth <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="dob" id="dob">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="email" id="email" placeholder="john@example.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" name="phone" id="phone" placeholder="10-digit number" maxlength="10" onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gender <span class="text-danger">*</span></label>
                                <select class="form-select" name="gender" id="gender">
                                    <option value="" selected disabled>Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Aadhaar Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="aadhaar" id="aadhaar" placeholder="12-digit number" maxlength="12" onkeypress="return event.charCode >= 48 && event.charCode <= 57">
                            </div>
                        </div>

                        <h4 class="section-title">Address Details</h4>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Current Address <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="current_address" id="current_address" placeholder="Street, Apartment, or Floor">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Permanent Address <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="permanent_address" id="permanent_address" placeholder="Street, Apartment, or Floor">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">Country <span class="text-danger">*</span></label>
                                <select class="form-select" name="country_select" id="country_select" onchange="toggleOther('country_select', 'country_other')">
                                    <option value="" selected disabled>Select...</option>
                                    <option value="India">India</option>
                                    <option value="USA">USA</option>
                                    <option value="UK">UK</option>
                                    <option value="other">Other (Specify)</option>
                                </select>
                                <input type="text" class="form-control hidden-input" name="country_other" id="country_other" placeholder="Enter Country">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">State <span class="text-danger">*</span></label>
                                <select class="form-select" name="state_select" id="state_select" onchange="toggleOther('state_select', 'state_other')">
                                    <option value="" selected disabled>Select...</option>
                                    <option value="Karnataka">Karnataka</option>
                                    <option value="Tamil Nadu">Tamil Nadu</option>
                                    <option value="Maharashtra">Maharashtra</option>
                                    <option value="other">Other (Specify)</option>
                                </select>
                                <input type="text" class="form-control hidden-input" name="state_other" id="state_other" placeholder="Enter State">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">City <span class="text-danger">*</span></label>
                                <select class="form-select" name="city_select" id="city_select" onchange="toggleOther('city_select', 'city_other')">
                                    <option value="" selected disabled>Select...</option>
                                    <option value="Bangalore">Bangalore</option>
                                    <option value="Mysore">Mysore</option>
                                    <option value="Chennai">Chennai</option>
                                    <option value="Mumbai">Mumbai</option>
                                    <option value="other">Other (Specify)</option>
                                </select>
                                <input type="text" class="form-control hidden-input" name="city_other" id="city_other" placeholder="Enter City">
                            </div>

                            <div class="col-md-12">
                                <label class="form-label">Pincode / Zip <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="pincode" id="pincode" placeholder="e.g. 560001">
                            </div>
                        </div>

                        <h4 class="section-title">Account Setup & Verification</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Profile Photo <span class="text-danger">*</span></label>
                                <input type="file" class="form-control" name="photo" id="photo" accept=".jpg, .jpeg, .png">
                                <small class="text-muted">Allowed: JPG, JPEG, PNG.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Create Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" name="password" id="password" placeholder="••••••••">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye" id="eyeIcon"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-light border rounded mt-4">
                            <label class="form-label fw-bold">Step 1: Generate OTP</label>
                            <div class="input-group mb-3">
                                <select class="form-select" name="otp_method">
                                    <option value="email">Send via Email</option>
                                    <option value="phone">Send via Phone</option>
                                </select>
                                <button type="submit" name="send_otp" class="btn btn-warning px-4" formnovalidate>Send OTP</button>
                            </div>

                            <label class="form-label fw-bold">Step 2: Verify & Submit</label>
                            <input type="text" class="form-control mb-3" name="otp" id="otp" placeholder="Enter 4-digit OTP generated above">
                            
                            <div class="d-flex gap-2">
                                <button type="submit" name="register" class="btn btn-success w-100">Complete Registration</button>
                                <button type="reset" class="btn btn-secondary w-50">Reset Form</button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Toggle "Other" input fields
    function toggleOther(selectId, inputId) {
        var selectElement = document.getElementById(selectId);
        var inputElement = document.getElementById(inputId);
        
        if(selectElement.value === 'other') {
            inputElement.style.display = 'block';
        } else {
            inputElement.style.display = 'none';
            inputElement.value = ''; 
        }
    }

    // Toggle Password Visibility
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('bi-eye');
            eyeIcon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('bi-eye-slash');
            eyeIcon.classList.add('bi-eye');
        }
    });

    // Custom Pop-up Alerts for Validation
    function validateForm(event) {
        if (event.submitter && event.submitter.name === 'register') {
            
            // Define an array of fields and their specific error messages
            const fieldsToCheck = [
                { id: 'name', message: 'Please enter your Full Name.' },
                { id: 'dob', message: 'Please select your Date of Birth.' },
                { id: 'email', message: 'Please enter your Email Address.' },
                { id: 'phone', message: 'Please enter your Phone Number.' },
                { id: 'gender', message: 'Please select your Gender.' },
                { id: 'aadhaar', message: 'Please enter your Aadhaar Number.' },
                { id: 'current_address', message: 'Please enter your Current Address.' },
                { id: 'permanent_address', message: 'Please enter your Permanent Address.' },
                { id: 'country_select', message: 'Please select your Country.' },
                { id: 'state_select', message: 'Please select your State.' },
                { id: 'city_select', message: 'Please select your City.' },
                { id: 'pincode', message: 'Please enter your Pincode.' },
                { id: 'photo', message: 'Please upload a Profile Photo.' },
                { id: 'password', message: 'Please create a Password.' },
                { id: 'otp', message: 'Please enter the 4-digit OTP sent to your Email/Phone.' }
            ];

            for (let i = 0; i < fieldsToCheck.length; i++) {
                let element = document.getElementById(fieldsToCheck[i].id);
                
                // Check if field is empty
                if (!element.value || element.value.trim() === '') {
                    alert(fieldsToCheck[i].message);
                    element.focus();
                    return false; // Stop submission immediately
                }

                // Nested checks for the "Other" dropdown fields
                if (fieldsToCheck[i].id === 'country_select' && element.value === 'other') {
                    let otherElement = document.getElementById('country_other');
                    if(otherElement.value.trim() === ''){ alert("Please specify your Country."); otherElement.focus(); return false; }
                }
                if (fieldsToCheck[i].id === 'state_select' && element.value === 'other') {
                    let otherElement = document.getElementById('state_other');
                    if(otherElement.value.trim() === ''){ alert("Please specify your State."); otherElement.focus(); return false; }
                }
                if (fieldsToCheck[i].id === 'city_select' && element.value === 'other') {
                    let otherElement = document.getElementById('city_other');
                    if(otherElement.value.trim() === ''){ alert("Please specify your City."); otherElement.focus(); return false; }
                }
            }

            // Check exact 10 digits for Phone
            const phone = document.getElementById('phone').value;
            if (!/^\d{10}$/.test(phone)) {
                alert("Phone number must be exactly 10 digits.");
                document.getElementById('phone').focus();
                return false; 
            }

            // Check exact 12 digits for Aadhaar
            const aadhaar = document.getElementById('aadhaar').value;
            if (!/^\d{12}$/.test(aadhaar)) {
                alert("Aadhaar number must be exactly 12 digits.");
                document.getElementById('aadhaar').focus();
                return false; 
            }
        }
        return true; 
    }
</script>
</body>
</html>
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

// 3. Fetch Candidate Info for the Header
$res_student = $conn->query("SELECT * FROM students WHERE id='$student_id'");
$student = $res_student->fetch_assoc();

// 4. Fetch All Applications ONCE for both tabs
$sql_apps = "SELECT * FROM applications WHERE student_id = '$student_id' ORDER BY created_at DESC";
$result_apps = $conn->query($sql_apps);

// Store results in an array so we can loop through them twice (once for table, once for tracker)
$applications = [];
if ($result_apps && $result_apps->num_rows > 0) {
    while($row = $result_apps->fetch_assoc()) {
        $applications[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forms & Status | SEB Portal</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { 
            --seb-blue: #1e3a8a; 
            --seb-light: #eff6ff;
            --seb-success: #10b981;
            --bg-color: #f3f4f6;
        }
        
        body { 
            background-color: var(--bg-color); 
            font-family: 'Inter', sans-serif; 
            color: #1f2937;
        }
        
        /* Navbar */
        .navbar { background-color: var(--seb-blue) !important; padding: 15px 0; }
        .navbar-brand { font-weight: 700; letter-spacing: 0.5px; }
        .btn-top-back { color: rgba(255,255,255,0.8); text-decoration: none; transition: color 0.2s; }
        .btn-top-back:hover { color: white; }

        /* Dashboard Header */
        .dashboard-header { background: white; padding: 30px 0; border-bottom: 1px solid #e5e7eb; margin-bottom: 30px; }

        /* Custom Tabs */
        .nav-pills .nav-link {
            color: #4b5563;
            font-weight: 600;
            border-radius: 50px;
            padding: 10px 25px;
            margin-right: 10px;
            transition: all 0.3s ease;
            background-color: white;
            border: 1px solid #e5e7eb;
        }
        .nav-pills .nav-link.active {
            background-color: var(--seb-blue);
            color: white;
            border-color: var(--seb-blue);
            box-shadow: 0 4px 6px rgba(30, 58, 138, 0.2);
        }

        /* Content Card */
        .content-card { border: none; border-radius: 16px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); background: white; }
        
        /* Table Styling */
        .table thead th { background-color: #f9fafb; color: #6b7280; font-weight: 600; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; padding: 15px; border-bottom: 2px solid #e5e7eb; }
        .table tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #f3f4f6; }
        
        /* Status Badges */
        .status-pill { padding: 6px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 700; letter-spacing: 0.5px; }
        .status-pending { background-color: #fef3c7; color: #d97706; }
        .status-verified { background-color: #d1fae5; color: #059669; }
        .status-rejected { background-color: #fee2e2; color: #dc2626; }

        /* Tracker Styling */
        .tracker-card { 
            border: 1px solid #e5e7eb; 
            border-radius: 12px; 
            background: white; 
            margin-bottom: 20px; 
            border-left: 5px solid var(--seb-blue); 
            transition: transform 0.2s;
        }
        .tracker-card:hover { transform: translateY(-3px); box-shadow: 0 10px 15px rgba(0,0,0,0.05); }
        
        .step-container { display: flex; justify-content: space-between; position: relative; margin-top: 30px; }
        .step-line { position: absolute; top: 15px; left: 10%; width: 80%; height: 3px; background: #e5e7eb; z-index: 1; }
        .step-item { z-index: 2; text-align: center; width: 25%; }
        
        .step-icon { 
            width: 35px; height: 35px; border-radius: 50%; background: #e5e7eb; color: #9ca3af; 
            display: inline-flex; align-items: center; justify-content: center; font-size: 14px; margin-bottom: 8px; font-weight: bold;
            transition: all 0.3s; border: 3px solid white; box-shadow: 0 0 0 1px #e5e7eb;
        }
        
        /* Tracker Progress Colors */
        .active .step-icon { background: var(--seb-blue); color: white; box-shadow: 0 0 0 2px var(--seb-light); }
        .active .step-text { color: var(--seb-blue); font-weight: 700; }
        .completed .step-icon { background: var(--seb-success); color: white; box-shadow: 0 0 0 2px #d1fae5; }
        .completed .step-text { color: var(--seb-success); font-weight: 600; }

        /* Bottom Back Link - Updated to match image */
        .back-link { display: inline-flex; align-items: center; color: #5a6b7c; font-size: 0.95rem; font-weight: 500; text-decoration: none; transition: color 0.2s ease; }
        .back-link:hover { color: var(--seb-blue); }

        .footer { background-color: #1f2937; color: #9ca3af; padding: 25px 0; margin-top: 50px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><i class="fas fa-shield-alt me-2 text-primary-light"></i> SEB PORTAL</a>
            <div class="ms-auto d-none d-md-block">
                <a href="dashboard.php" class="btn-top-back"><i class="fas fa-arrow-left me-1"></i> Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="fw-bold mb-1">Forms & Application Status</h2>
                    <p class="text-muted mb-0">Manage and track applications for <strong><?php echo htmlspecialchars($student['name']); ?></strong></p>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        
        <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active shadow-sm" id="pills-forms-tab" data-bs-toggle="pill" data-bs-target="#pills-forms" type="button" role="tab"><i class="fas fa-list me-2"></i>Submitted Forms</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link shadow-sm" id="pills-status-tab" data-bs-toggle="pill" data-bs-target="#pills-status" type="button" role="tab"><i class="fas fa-tasks me-2"></i>Track Status</button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            
            <div class="tab-pane fade show active" id="pills-forms" role="tabpanel">
                <div class="content-card p-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>App ID</th>
                                    <th>Examination Name</th>
                                    <th>Designation</th>
                                    <th>Applied Date</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($applications)): ?>
                                    <?php foreach($applications as $row): ?>
                                        <tr>
                                            <td><span class="fw-bold text-secondary"><?php echo htmlspecialchars($row['application_id']); ?></span></td>
                                            <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['exam_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['designation'] ?? 'N/A'); ?></td>
                                            <td class="text-muted"><?php echo date('d M, Y', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <?php 
                                                    $st = strtolower($row['status']);
                                                    $class = ($st == 'verified') ? 'status-verified' : (($st == 'rejected') ? 'status-rejected' : 'status-pending');
                                                ?>
                                                <span class="status-pill <?php echo $class; ?>">
                                                    <i class="fas fa-circle me-1" style="font-size: 6px; vertical-align: middle;"></i> <?php echo strtoupper(htmlspecialchars($row['status'])); ?>
                                                </span>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group shadow-sm" role="group">
                                                    <a href="view_form.php?id=<?php echo $row['application_id']; ?>" class="btn btn-sm btn-light border" title="View">
                                                        <i class="fas fa-eye text-primary"></i>
                                                    </a>
                                                    <?php if ($st != 'verified'): ?>
                                                    <a href="edit_form.php?id=<?php echo $row['application_id']; ?>" class="btn btn-sm btn-light border" title="Modify">
                                                        <i class="fas fa-edit text-warning"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <a href="print_form.php?id=<?php echo $row['application_id']; ?>" class="btn btn-sm btn-light border" title="Print">
                                                        <i class="fas fa-print text-secondary"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="mb-3"><i class="fas fa-folder-open fa-3x text-muted opacity-25"></i></div>
                                            <p class="text-muted mb-2">You have not submitted any applications yet.</p>
                                            <a href="apply.php" class="btn btn-primary btn-sm px-4 rounded-pill" style="background-color: var(--seb-blue); border: none;">Apply Now</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="pills-status" role="tabpanel">
                <?php if (!empty($applications)): ?>
                    <div class="row">
                        <?php foreach($applications as $row): 
                            $status = strtolower($row['status']); 
                            $isVerified = ($status == 'verified');
                        ?>
                        <div class="col-12 mb-4">
                            <div class="tracker-card p-4 shadow-sm">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($row['exam_name']); ?></h5>
                                        <div class="text-muted small">
                                            <span class="me-3"><i class="fas fa-hashtag me-1"></i> ID: <?php echo htmlspecialchars($row['application_id']); ?></span>
                                            <span><i class="fas fa-calendar-alt me-1"></i> Applied: <?php echo date('d M, Y', strtotime($row['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <?php if($status == 'rejected'): ?>
                                        <span class="badge bg-danger px-3 py-2 rounded-pill">REJECTED</span>
                                    <?php endif; ?>
                                </div>

                                <?php if($status != 'rejected'): ?>
                                <div class="step-container">
                                    <div class="step-line"></div>
                                    
                                    <div class="step-item completed">
                                        <div class="step-icon"><i class="fas fa-check"></i></div>
                                        <div class="step-text small">Submitted</div>
                                    </div>

                                    <div class="step-item <?php echo ($status == 'pending' || $isVerified) ? 'active' : ''; ?> <?php echo $isVerified ? 'completed' : ''; ?>">
                                        <div class="step-icon"><?php echo $isVerified ? '<i class="fas fa-check"></i>' : '2'; ?></div>
                                        <div class="step-text small">Under Review</div>
                                    </div>

                                    <div class="step-item <?php echo $isVerified ? 'active completed' : ''; ?>">
                                        <div class="step-icon"><?php echo $isVerified ? '<i class="fas fa-check"></i>' : '3'; ?></div>
                                        <div class="step-text small">Verified</div>
                                    </div>

                                    <div class="step-item <?php echo $isVerified ? 'active' : ''; ?>">
                                        <div class="step-icon"><i class="fas fa-file-download"></i></div>
                                        <div class="step-text small">Admit Card</div>
                                    </div>
                                </div>
                                <?php else: ?>
                                    <div class="alert alert-danger mt-3 mb-0 border-0 bg-opacity-10 py-2">
                                        <i class="fas fa-exclamation-circle me-2"></i> Application was rejected during the review process. Please check notifications.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="content-card text-center py-5">
                        <i class="fas fa-search fa-3x text-muted mb-3 opacity-25"></i>
                        <p class="text-muted mb-0">No active applications found to track.</p>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>

        <div class="mt-4">
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
            </a>
        </div>
        
    </div>

    <footer class="footer text-center">
        <div class="container text-white-50">
            <p class="mb-0 small">&copy; <?php echo date("Y"); ?> Secure Exam Browser - Secure Management System</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
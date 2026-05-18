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

// 4. Fetch only VERIFIED applications (Admit cards are usually only for verified users)
$sql_admit = "SELECT * FROM applications WHERE student_id = '$student_id' AND status = 'Verified' ORDER BY created_at DESC";
$result_admit = $conn->query($sql_admit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Admit Card | SEB Portal</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { 
            --seb-blue: #1e3a8a; 
            --seb-blue-light: #3b82f6;
            --seb-yellow: #f59e0b;
            --bg-color: #f3f4f6;
        }
        
        body { 
            background-color: var(--bg-color); 
            font-family: 'Inter', sans-serif; 
            color: #1f2937;
        }
        
        /* Navbar */
        .navbar { background-color: var(--seb-blue) !important; padding: 15px 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .navbar-brand { font-weight: 700; letter-spacing: 0.5px; }
        .btn-top-back { color: rgba(255,255,255,0.8); text-decoration: none; transition: color 0.2s; font-size: 0.95rem; }
        .btn-top-back:hover { color: white; }

        /* Hero Banner */
        .hero-banner {
            background: linear-gradient(135deg, var(--seb-blue) 0%, #312e81 100%);
            color: white;
            padding: 3rem 0 5rem 0;
        }

        /* Content Wrapper */
        .content-wrapper {
            margin-top: -3rem;
            position: relative;
            z-index: 10;
        }
        
        /* -------------------------------------
           TICKET CARD STYLING 
        -------------------------------------- */
        .ticket-card {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            overflow: hidden;
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        
        .ticket-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
        }

        .ticket-header {
            padding: 1.5rem;
            background: linear-gradient(to right, #f8fafc, #f1f5f9);
            border-bottom: 1px solid #e2e8f0;
        }

        .ticket-body {
            padding: 1.5rem;
        }

        /* The "Tear-off" Dashed Divider with Notches */
        .ticket-divider {
            border-top: 2px dashed #cbd5e1;
            margin: 0;
            position: relative;
            background: white;
        }
        .ticket-divider::before, .ticket-divider::after {
            content: '';
            position: absolute;
            top: -12px;
            width: 24px;
            height: 24px;
            background-color: var(--bg-color); /* Matches page background */
            border-radius: 50%;
            border: 1px solid #e5e7eb;
        }
        .ticket-divider::before { left: -13px; border-right-color: transparent; border-top-color: transparent; border-bottom-color: transparent; box-shadow: inset -3px 0 5px rgba(0,0,0,0.02); }
        .ticket-divider::after { right: -13px; border-left-color: transparent; border-top-color: transparent; border-bottom-color: transparent; box-shadow: inset 3px 0 5px rgba(0,0,0,0.02); }

        .ticket-footer {
            padding: 1.5rem;
            background: white;
        }

        /* Badges & Text */
        .status-badge {
            background: #d1fae5;
            color: #059669;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .data-label { color: #64748b; font-size: 0.75rem; text-transform: uppercase; font-weight: 600; letter-spacing: 0.5px; margin-bottom: 0.25rem; }
        .data-value { color: #0f172a; font-weight: 700; font-size: 1rem; }

        /* Download Button */
        .btn-download {
            background-color: var(--seb-blue);
            color: white;
            font-weight: 600;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
            border: none;
        }
        .btn-download:hover {
            background-color: var(--seb-blue-light);
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        /* Empty State */
        .empty-state {
            background: white;
            border-radius: 16px;
            padding: 4rem 2rem;
            text-align: center;
            border: 1px dashed #cbd5e1;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }

        .footer { background-color: #1f2937; color: #9ca3af; padding: 25px 0; margin-top: 50px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-shield-alt me-2 text-primary-light"></i> SEB PORTAL
            </a>
            <div class="ms-auto d-none d-md-block">
                <a href="dashboard.php" class="btn-top-back">
                    <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="hero-banner">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="fw-bold mb-2">Hall Tickets & Admit Cards</h2>
                    <p class="mb-0 opacity-75">Securely download official entry passes for <strong><?php echo htmlspecialchars($student['name']); ?></strong></p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0 d-none d-md-block">
                    <i class="fas fa-ticket-alt fa-3x opacity-25" style="transform: rotate(-15deg);"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="container content-wrapper pb-5">
        <div class="row g-4">
            
            <?php if ($result_admit && $result_admit->num_rows > 0): ?>
                <?php while($row = $result_admit->fetch_assoc()): ?>
                    <div class="col-lg-6">
                        <div class="ticket-card">
                            
                            <div class="ticket-header d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center">
                                    <div class="bg-white rounded p-2 border shadow-sm me-3 text-center" style="width: 48px; height: 48px;">
                                        <i class="fas fa-id-badge text-primary fs-4 mt-1"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($row['exam_name']); ?></h5>
                                        <div class="text-muted small fw-medium"><i class="fas fa-hashtag me-1"></i> <?php echo htmlspecialchars($row['application_id']); ?></div>
                                    </div>
                                </div>
                                <span class="status-badge"><i class="fas fa-check-circle me-1"></i> VERIFIED</span>
                            </div>

                            <div class="ticket-body">
                                <div class="row g-3">
                                    <div class="col-6">
                                        <div class="data-label">Examination District</div>
                                        <div class="data-value"><i class="fas fa-map-marker-alt text-danger me-1"></i> <?php echo htmlspecialchars($row['district'] ?? 'N/A'); ?></div>
                                    </div>
                                    <div class="col-6">
                                        <div class="data-label">Academic Session</div>
                                        <div class="data-value"><i class="fas fa-calendar-alt text-primary me-1"></i> 2026</div>
                                    </div>
                                    <div class="col-12 mt-3">
                                        <div class="data-label">Candidate Name</div>
                                        <div class="data-value"><?php echo htmlspecialchars($student['name']); ?></div>
                                    </div>
                                </div>
                            </div>

                            <div class="ticket-divider"></div>

                            <div class="ticket-footer text-center">
                                <div class="mb-3 opacity-50">
                                    <i class="fas fa-barcode fa-3x" style="font-family: 'Barcode 39', cursive; letter-spacing: -2px;"></i>
                                </div>
                                <a href="generate_admit_card.php?app_id=<?php echo htmlspecialchars($row['application_id']); ?>" class="btn w-100 btn-download shadow-sm">
                                    <i class="fas fa-cloud-download-alt me-2"></i> DOWNLOAD ADMIT CARD (PDF)
                                </a>
                            </div>

                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="empty-state">
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border-radius: 50%; background-color: #fef2f2; color: #ef4444;">
                                <i class="fas fa-ticket-alt fa-2x"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">No Admit Cards Available Yet</h4>
                        <p class="text-muted mx-auto mb-4" style="max-width: 500px;">
                            Admit cards are only generated once your application is officially marked as <strong>"Verified"</strong> by the examination board.
                        </p>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="forms_status.php" class="btn btn-outline-primary fw-medium px-4 py-2 rounded-pill">Check Status</a>
                            <a href="dashboard.php" class="btn btn-primary fw-medium px-4 py-2 rounded-pill" style="background-color: var(--seb-blue); border: none;">Back to Dashboard</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <footer class="footer text-center">
        <div class="container text-white-50">
            <p class="mb-0 small">&copy; <?php echo date("Y"); ?> State Examination Board - Secure Admit Card System</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
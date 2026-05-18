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

// 4. Fetch Results (JOINing with applications to get the specific details)
$sql_results = "SELECT r.*, a.application_id as reg_id, a.post, a.designation 
                FROM results r 
                JOIN applications a ON r.application_id = a.application_id 
                WHERE r.student_id = '$student_id'";
$result_data = $conn->query($sql_results);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Exam Results | SEB Portal</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root { 
            --seb-blue: #1e3a8a; /* Deeper, richer blue */
            --seb-blue-light: #2563eb;
            --bg-color: #f3f4f6;
        }
        
        body { 
            background-color: var(--bg-color); 
            font-family: 'Inter', sans-serif; 
            color: #1f2937;
        }
        
        /* Navbar */
        .navbar { 
            background-color: var(--seb-blue) !important; 
            padding: 15px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .navbar-brand { font-weight: 700; letter-spacing: 0.5px; }
        
        /* Hero Section (Background Gradient) */
        .hero-banner {
            background: linear-gradient(135deg, var(--seb-blue) 0%, #312e81 100%);
            color: white;
            padding: 3rem 0 6rem 0; /* Extra padding at bottom for overlap */
        }

        /* Content Area Overlap */
        .content-wrapper {
            margin-top: -3.5rem; /* Pulls content up over the hero banner */
            position: relative;
            z-index: 10;
        }

        /* Floating Table Design */
        .table-responsive { overflow-x: auto; }
        .table-custom { border-collapse: separate; border-spacing: 0 12px; }
        .table-custom thead th {
            border: none;
            color: #6b7280;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0 1.25rem 0.5rem 1.25rem;
        }
        .table-custom tbody tr {
            background: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02), 0 4px 12px rgba(0,0,0,0.04);
            border-radius: 12px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .table-custom tbody tr:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.08);
        }
        .table-custom td {
            border: none;
            padding: 1.25rem;
            vertical-align: middle;
            font-size: 0.95rem;
        }
        
        /* Rounded corners for first and last cells in the floating row */
        .table-custom td:first-child { border-top-left-radius: 12px; border-bottom-left-radius: 12px; }
        .table-custom td:last-child { border-top-right-radius: 12px; border-bottom-right-radius: 12px; }

        /* Status Badges */
        .status-badge {
            padding: 0.35rem 0.8rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            display: inline-block;
        }
        .status-qualified { background: #d1fae5; color: #059669; }
        .status-disqualified { background: #fee2e2; color: #dc2626; }

        /* Typography Enhancements */
        .val-highlight { font-weight: 700; color: #111827; }
        .text-sub { color: #6b7280; font-size: 0.85rem; }

        /* Buttons */
        .btn-print {
            background-color: white;
            color: var(--seb-blue-light);
            border: 1px solid #bfdbfe;
            font-weight: 500;
            transition: all 0.2s;
            border-radius: 8px;
        }
        .btn-print:hover {
            background-color: #eff6ff;
            border-color: var(--seb-blue-light);
        }
        
        /* UPDATED BACK LINK STYLE */
        .back-link {
            display: inline-flex;
            align-items: center;
            color: #5a6b7c;
            font-size: 0.95rem;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .back-link:hover { color: var(--seb-blue-light); }

        .btn-top-back { 
            color: rgba(255,255,255,0.8); 
            text-decoration: none; 
            font-size: 0.9rem;
            transition: color 0.2s;
        }
        .btn-top-back:hover { color: white; }

        /* Empty State */
        .empty-state {
            background: white;
            border-radius: 16px;
            padding: 4rem 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
            text-align: center;
        }
        
        /* -------------------------------------
            PRINT SPECIFIC STYLES
        -------------------------------------- */
        @media print {
            body { background: white; color: black; }
            .navbar, .hero-banner p, .btn-top-back, .back-link, .btn-print { display: none !important; }
            .hero-banner { 
                background: white !important; 
                color: black !important; 
                padding: 1rem 0 !important; 
                border-bottom: 2px solid black; 
                margin-bottom: 2rem;
            }
            .content-wrapper { margin-top: 0; box-shadow: none; }
            .table-custom tbody tr { box-shadow: none; border-bottom: 1px solid #ccc; border-radius: 0; }
            .table-custom td, .table-custom th { border-bottom: 1px solid #eee; padding: 0.5rem; }
            .status-badge { border: 1px solid black; background: transparent !important; color: black !important; }
        }
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

    <div class="hero-banner">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="fw-bold mb-2">Performance & Results</h2>
                    <p class="mb-0 opacity-75">Official examination records for <strong><?php echo htmlspecialchars($student['name']); ?></strong></p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0 d-none d-md-block">
                    <i class="fas fa-award fa-3x opacity-25"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="container content-wrapper pb-5">
        
        <?php if ($result_data && $result_data->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-custom w-100">
                    <thead>
                        <tr>
                            <th>Registration Details</th>
                            <th>Examination Post</th>
                            <th>Designation</th>
                            <th class="text-center">Score Data</th>
                            <th class="text-center">Final %</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result_data->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="val-highlight"><?php echo htmlspecialchars($row['reg_id'] ?? 'N/A'); ?></div>
                                    <div class="text-sub">ID Number</div>
                                </td>
                                
                                <td>
                                    <div class="val-highlight"><?php echo htmlspecialchars($row['post'] ?? 'N/A'); ?></div>
                                    <div class="text-sub">Applied Post</div>
                                </td>
                                
                                <td>
                                    <div class="val-highlight"><?php echo htmlspecialchars($row['designation'] ?? 'N/A'); ?></div>
                                </td>
                                
                                <td class="text-center">
                                    <span class="fs-5 fw-bold text-primary"><?php echo htmlspecialchars($row['obtained_marks'] ?? '0'); ?></span>
                                    <span class="text-muted">/ <?php echo htmlspecialchars($row['total_marks'] ?? '0'); ?></span>
                                </td>
                                
                                <td class="text-center">
                                    <div class="val-highlight fs-6"><?php echo htmlspecialchars($row['percentage'] ?? '0'); ?>%</div>
                                </td>
                                
                                <td class="text-center">
                                    <?php if(strtolower($row['result_status'] ?? '') == 'qualified'): ?>
                                        <span class="status-badge status-qualified"><i class="fas fa-check-circle me-1"></i> QUALIFIED</span>
                                    <?php else: ?>
                                        <span class="status-badge status-disqualified"><i class="fas fa-times-circle me-1"></i> NOT QUALIFIED</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="text-end">
                                    <button onclick="window.print()" class="btn btn-sm btn-print px-3">
                                        <i class="fas fa-print me-1"></i> Print
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border-radius: 50%; background-color: #eff6ff; color: #3b82f6;">
                        <i class="fas fa-file-invoice fa-2x"></i>
                    </div>
                </div>
                <h4 class="fw-bold text-dark mb-2">No Results Available Yet</h4>
                <p class="text-muted mb-0 mx-auto" style="max-width: 400px;">Your examination results have not been officially published. Please check back approximately 15 days after your exam date.</p>
            </div>
        <?php endif; ?>

        <div class="mt-4 text-start">
            <a href="dashboard.php" class="back-link">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
        
    </div>

    <footer class="text-center py-4 mt-5 border-top">
        <div class="container">
            <p class="mb-0 text-muted small">&copy; <?php echo date("Y"); ?> State Examination Board (SEB). All data is secured & encrypted.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
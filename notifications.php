<?php
session_start();

// Protection: Ensure user is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications & Updates | SEB Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { 
            --seb-blue: #1a237e; 
            --seb-light: #e8eaf6;
            --seb-accent: #ff4081;
        }
        
        body { 
            background-color: #f4f7f6; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        
        .navbar { 
            background-color: var(--seb-blue) !important; 
            padding: 12px 0; 
        }

        /* Page Header Graphic */
        .page-header {
            background: linear-gradient(135deg, var(--seb-blue) 0%, #3949ab 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 20px rgba(26, 35, 126, 0.15);
        }

        /* Notification Card Styling */
        .notification-card { 
            border: none; 
            border-radius: 12px; 
            border-left: 5px solid var(--seb-accent);
            transition: all 0.3s ease; 
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.04);
        }
        
        .notification-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 12px 25px rgba(0,0,0,0.08); 
        }

        /* Calendar Date Badge */
        .date-badge {
            background: var(--seb-light);
            border-radius: 10px;
            text-align: center;
            min-width: 80px;
            padding: 10px 0;
            border: 1px solid rgba(26, 35, 126, 0.1);
        }
        .date-badge .month { font-size: 0.8rem; font-weight: 800; color: var(--seb-accent); text-transform: uppercase; letter-spacing: 1px; }
        .date-badge .day { font-size: 1.8rem; font-weight: 900; color: var(--seb-blue); line-height: 1; margin: 2px 0; }
        .date-badge .year { font-size: 0.75rem; font-weight: 600; color: #6c757d; }

        /* Syllabus Grid Cards */
        .syllabus-item {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 10px;
            padding: 1.25rem;
            height: 100%;
            transition: border-color 0.2s ease;
        }
        .syllabus-item:hover {
            border-color: var(--seb-blue);
        }
        .syllabus-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: var(--seb-light);
            color: var(--seb-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        .weightage-badge {
            font-size: 0.7rem;
            background: #e0f2f1;
            color: #00695c;
            padding: 3px 8px;
            border-radius: 20px;
            font-weight: bold;
        }

        /* Small Simple Back Link */
        .back-link {
            color: #6c757d;
            transition: color 0.2s ease;
        }
        .back-link:hover {
            color: var(--seb-blue);
        }

        footer { background: #212121; color: #bbb; padding: 30px 0; margin-top: 50px; }

        /* Page Load Animation */
        .animate-fade-up {
            animation: fadeUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
            transform: translateY(20px);
        }
        @keyframes fadeUp {
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php"><i class="fas fa-shield-alt me-2"></i>SEB PORTAL</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-3"><a class="nav-link" href="dashboard.php"><i class="fas fa-home me-1"></i> Home</a></li>
                    <li class="nav-item me-3"><a class="nav-link active" href="notifications.php"><i class="fas fa-bell me-1"></i> Notifications</a></li>
                    <li class="nav-item"><a class="btn btn-danger btn-sm px-3" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5" style="max-width: 900px;">
        
        <div class="page-header d-flex justify-content-between align-items-center animate-fade-up">
            <div>
                <h3 class="fw-bold mb-1"><i class="fas fa-bullhorn me-2 text-warning"></i> Official Announcements</h3>
                <p class="mb-0 text-white-50 small">Stay updated with the latest examination schedules and alerts.</p>
            </div>
            <div class="d-none d-md-block">
                <i class="fas fa-bell fa-3x text-white opacity-25"></i>
            </div>
        </div>

        <div class="card notification-card mb-4 animate-fade-up" style="animation-delay: 0.1s;">
            <div class="card-body p-4">
                
                <div class="d-flex flex-column flex-md-row gap-4">
                    
                    <div class="flex-shrink-0">
                        <div class="date-badge">
                            <span class="d-block month">Aug</span>
                            <span class="d-block day">15</span>
                            <span class="d-block year">2026</span>
                        </div>
                    </div>

                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h5 class="fw-bold text-dark mb-0">State Entrance Examination 2026</h5>
                            <span class="badge bg-danger rounded-pill px-3 py-2 shadow-sm"><i class="fas fa-star me-1 text-warning"></i> NEW</span>
                        </div>
                        
                        <p class="text-muted mb-4 small">
                            <span class="me-3"><i class="fas fa-clock me-1 text-primary"></i> 10:00 AM - 01:00 PM</span>
                            <span><i class="fas fa-map-marker-alt me-1 text-danger"></i> Refer to Admit Card</span>
                        </p>
                        
                        <div class="bg-light p-4 rounded-4 border">
                            <h6 class="fw-bold mb-3" style="color: var(--seb-blue);">
                                <i class="fas fa-book-reader me-2"></i> Official Examination Syllabus Overview
                            </h6>
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <div class="syllabus-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="syllabus-icon"><i class="fas fa-globe"></i></div>
                                            <span class="weightage-badge">20%</span>
                                        </div>
                                        <h6 class="fw-bold fs-6">Section A</h6>
                                        <p class="text-muted small mb-0">General Knowledge, Current Affairs & Logical Reasoning.</p>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="syllabus-item">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="syllabus-icon"><i class="fas fa-chart-pie"></i></div>
                                            <span class="weightage-badge">30%</span>
                                        </div>
                                        <h6 class="fw-bold fs-6">Section B</h6>
                                        <p class="text-muted small mb-0">Quantitative Aptitude & Data Interpretation.</p>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="syllabus-item border-primary" style="background-color: #f8f9fa;">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="syllabus-icon bg-primary text-white"><i class="fas fa-graduation-cap"></i></div>
                                            <span class="weightage-badge bg-primary text-white">50%</span>
                                        </div>
                                        <h6 class="fw-bold fs-6 text-primary">Section C</h6>
                                        <p class="text-muted small mb-0">Core Subject Knowledge (As per chosen stream).</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="text-start mt-4 animate-fade-up" style="animation-delay: 0.2s;">
            <a href="dashboard.php" class="text-decoration-none small fw-semibold back-link">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>

    </div>

    <footer class="text-center">
        <div class="container">
            <p class="mb-1 fw-bold">State Examination Board (SEB)</p>
            <p class="x-small text-secondary mb-0">&copy; <?php echo date("Y"); ?> SEB Portal. All data is secured.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
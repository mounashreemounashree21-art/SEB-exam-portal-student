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

$sql = "SELECT a.*, s.name, s.email, s.phone 
        FROM applications a 
        JOIN students s ON a.student_id = s.id 
        WHERE a.application_id = '$app_id' AND a.student_id = '$student_id'";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    die("Application not found.");
}

$app = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Print Application - <?php echo $app['application_id']; ?></title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #000; margin: 0; padding: 20px; }
        .print-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 30px; }
        .print-header h1 { margin: 0 0 5px 0; text-transform: uppercase; font-size: 24px; }
        .print-header p { margin: 0; font-size: 14px; color: #555; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .data-table th, .data-table td { border: 1px solid #ccc; padding: 12px; text-align: left; }
        .data-table th { background-color: #f4f4f4; width: 40%; font-weight: bold; }
        .footer-note { text-align: center; font-size: 12px; margin-top: 50px; color: #555; }
        
        /* Auto print and hide buttons on actual paper */
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Print Document</button>
        <a href="forms_status.php" style="margin-left: 10px;">Go Back</a>
    </div>

    <div class="print-header">
        <h1>Secure Exam Board</h1>
        <p>Official Candidate Application Record</p>
    </div>

    <table class="data-table">
        <tr>
            <th>Application ID</th>
            <td><strong><?php echo htmlspecialchars($app['application_id']); ?></strong></td>
        </tr>
        <tr>
            <th>Examination Name</th>
            <td><?php echo htmlspecialchars($app['exam_name']); ?></td>
        </tr>
        <tr>
            <th>Applied Designation/Post</th>
            <td><?php echo htmlspecialchars($app['designation'] ?? 'N/A'); ?></td>
        </tr>
        <tr>
            <th>Application Status</th>
            <td><?php echo strtoupper(htmlspecialchars($app['status'])); ?></td>
        </tr>
        <tr>
            <th>Date of Submission</th>
            <td><?php echo date('d F Y, h:i A', strtotime($app['created_at'])); ?></td>
        </tr>
        <tr>
            <th>Candidate Full Name</th>
            <td><?php echo htmlspecialchars($app['name']); ?></td>
        </tr>
        <tr>
            <th>Registered Email</th>
            <td><?php echo htmlspecialchars($app['email']); ?></td>
        </tr>
        <tr>
            <th>Registered Phone</th>
            <td><?php echo htmlspecialchars($app['phone']); ?></td>
        </tr>
    </table>

    <div class="footer-note">
        <p>This is a computer-generated document and does not require a physical signature.</p>
        <p>Generated on: <?php echo date('d F Y, h:i A'); ?></p>
    </div>

</body>
</html>
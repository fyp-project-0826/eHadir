<?php
// attendance_api/get_class_report.php
require_once 'auth_check.php';
require_once 'db.php';

// Allow Teachers and Admins
if ($_SESSION['user_role'] === 'Student') { exit(); }

$class_id = $_GET['class_id'];

try {
    // Fetch all students and their attendance status for a specific class
    $sql = "SELECT u.full_name, u.user_id, 
        COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as present_count,
        COUNT(s.id) as total_sessions
        FROM users u
        JOIN student_enrollment se ON u.id = se.student_id
        LEFT JOIN class_sessions s ON se.class_id = s.class_id
        LEFT JOIN attendance a ON (u.id = a.student_id AND s.id = a.session_id)
        WHERE se.class_id = ?
        GROUP BY u.id";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$class_id]);
    $report = $stmt->fetchAll();

    echo json_encode(["status" => "success", "data" => $report]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
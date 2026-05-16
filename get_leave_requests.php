<?php
// attendance_api/get_leave_requests.php
require_once 'auth_check.php';
require_once 'db.php';

// Allow both Teachers and Admins
if ($_SESSION['user_role'] === 'Student') {
    exit(json_encode(["status" => "error", "message" => "Unauthorized"]));
}

try {
    $stmt = $pdo->prepare("SELECT lr.*, u.full_name, u.user_id as student_reg_no 
                           FROM leave_requests lr 
                           JOIN users u ON lr.student_id = u.id 
                           WHERE lr.status = 'Pending'");
    $stmt->execute();
    $requests = $stmt->fetchAll();

    echo json_encode(["status" => "success", "requests" => $requests]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
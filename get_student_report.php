<?php
// attendance_api/get_student_report.php
require_once 'auth_check.php';
require_once 'db.php';

$student_db_id = $_SESSION['user_db_id'];

try {
    // 1. Get total classes held for this student's enrolled classes
    $stmtTotal = $pdo->prepare("SELECT COUNT(*) as total FROM class_sessions s 
                                JOIN classes c ON s.class_id = c.id 
                                WHERE c.id IN (SELECT class_id FROM student_enrollment WHERE student_id = ?)");
    $stmtTotal->execute([$student_db_id]);
    $total_sessions = $stmtTotal->fetch()['total'];

    // 2. Get total attended (Present, Sick, Emergency)
    $stmtAttended = $pdo->prepare("SELECT COUNT(*) as attended FROM attendance 
                                   WHERE student_id = ? AND status IN ('Present', 'Sick', 'Emergency')");
    $stmtAttended->execute([$student_db_id]);
    $attended_count = $stmtAttended->fetch()['attended'];

    // 3. Calculate percentage
    $percentage = ($total_sessions > 0) ? ($attended_count / $total_sessions) * 100 : 100;
    $eligible = ($percentage >= 70);

    echo json_encode([
        "status" => "success",
        "total_classes" => $total_sessions,
        "attended" => $attended_count,
        "percentage" => round($percentage, 2),
        "eligible_for_exam" => $eligible,
        "warning" => !$eligible ? "Attendance below 70%. You are not eligible for the exam." : ""
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
<?php
// attendance_api/get_live_attendance.php
require_once 'db.php';
require_once 'auth_check.php';

$session_id = $_GET['session_id'] ?? null;

if (!$session_id) {
    echo json_encode(["status" => "error", "message" => "Tiada ID sesi."]);
    exit();
}

try {
    // 1. Dapatkan ID Kelas untuk sesi ini
    $stmt1 = $pdo->prepare("SELECT class_id FROM class_sessions WHERE id = ?");
    $stmt1->execute([$session_id]);
    $session = $stmt1->fetch();

    if (!$session) {
        echo json_encode(["status" => "error", "message" => "Sesi tidak wujud."]);
        exit();
    }
    $class_id = $session['class_id'];

    // 2. Kira JUMLAH pelajar yang berdaftar untuk kelas ini
    $stmt2 = $pdo->prepare("SELECT COUNT(*) as total_students FROM student_enrollment WHERE class_id = ?");
    $stmt2->execute([$class_id]);
    $total_students = $stmt2->fetch()['total_students'];

    // 3. Kira JUMLAH pelajar yang HADIR untuk sesi ini
    $stmt3 = $pdo->prepare("SELECT COUNT(*) as total_present FROM attendance WHERE session_id = ? AND status = 'Present'");
    $stmt3->execute([$session_id]);
    $total_present = $stmt3->fetch()['total_present'];

    echo json_encode([
        "status" => "success",
        "total_students" => $total_students,
        "total_present" => $total_present
    ]);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
<?php
// attendance_api/get_classes.php
require_once 'auth_check.php';
require_once 'db.php';

// Ensure only teachers or admins can access this
if ($_SESSION['user_role'] !== 'Teacher' && $_SESSION['user_role'] !== 'Admin') {
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

$teacher_db_id = $_SESSION['user_db_id'];

try {
    $stmt = $pdo->prepare("SELECT id, class_code, class_name FROM classes WHERE teacher_id = ?");
    $stmt->execute([$teacher_db_id]);
    $classes = $stmt->fetchAll();

    echo json_encode(["status" => "success", "classes" => $classes]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
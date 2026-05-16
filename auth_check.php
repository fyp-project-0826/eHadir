<?php
// attendance_api/auth_check.php
session_start();

function checkRole($requiredRole) {
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $requiredRole) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
        exit();
    }
}
?>
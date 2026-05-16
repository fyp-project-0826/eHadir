<?php
// attendance_api/upload_mc.php
require_once 'auth_check.php';
require_once 'db.php';

checkRole('Student'); // Only students should upload their own MC

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['mc_file'])) {
    $student_id = $_SESSION['user_db_id'];
    $leave_type = $_POST['leave_type']; // 'Sick' or 'Emergency'
    $session_id = $_POST['session_id'] ?? null; // Optional: link to a specific missed class

    $target_dir = "uploads/";
    $file_extension = strtolower(pathinfo($_FILES["mc_file"]["name"], PATHINFO_EXTENSION));
    
    // 1. Validate file type
    $allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($file_extension, $allowed_types)) {
        echo json_encode(["status" => "error", "message" => "Only JPG, PNG, and PDF files are allowed."]);
        exit();
    }

    // 2. Generate a unique filename to prevent overwriting
    $new_filename = "MC_" . $student_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["mc_file"]["tmp_name"], $target_file)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO leave_requests (student_id, session_id, mc_file_path, leave_type, status) VALUES (?, ?, ?, ?, 'Pending')");
            $stmt->execute([$student_id, $session_id, $target_file, $leave_type]);
            
            echo json_encode(["status" => "success", "message" => "MC uploaded successfully. Waiting for approval."]);
        } catch (PDOException $e) {
            echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to upload file."]);
    }
}
?>
<?php
// attendance_api/generate_session.php
require_once 'auth_check.php';
require_once 'db.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->class_id)) {
    $class_id = $data->class_id;
    $date = date('Y-m-d');
    $time = date('H:i:s');
    
    // Generate a high-entropy random token
    $qr_token = bin2hex(random_bytes(16)); 

    try {
        $stmt = $pdo->prepare("INSERT INTO class_sessions (class_id, session_date, start_time, qr_token) VALUES (?, ?, ?, ?)");
        $stmt->execute([$class_id, $date, $time, $qr_token]);
        
        $session_id = $pdo->lastInsertId();

        echo json_encode([
            "status" => "success",
            "session_id" => $session_id,
            "qr_token" => $qr_token
        ]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
<?php
require_once 'db.php';
require_once 'auth_check.php';

$data = json_decode(file_get_contents("php://input"));
if (!$data) {
    echo json_encode(["status" => "error", "message" => "Data tidak sah."]);
    exit();
}

$action = $data->action ?? '';

try {
    if ($action === 'delete_student') {
        $pdo->beginTransaction();
        // Padam rekod kehadiran dahulu untuk elak ralat Foreign Key
        $stmt1 = $pdo->prepare("DELETE FROM attendance WHERE student_id = ?");
        $stmt1->execute([$data->id]);
        
        $stmt2 = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'Student'");
        $stmt2->execute([$data->id]);
        
        $pdo->commit();
        echo json_encode(["status" => "success", "message" => "Pelajar dan rekod kehadiran telah dipadam."]);

    } elseif ($action === 'edit_attendance') {
        $stmt = $pdo->prepare("UPDATE attendance SET status = ? WHERE student_id = ? AND session_id = ?");
        $stmt->execute([$data->status, $data->student_id, $data->session_id]);
        echo json_encode(["status" => "success", "message" => "Status dikemaskini kepada: " . $data->status]);
    }
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => "Ralat Database: " . $e->getMessage()]);
}
?>
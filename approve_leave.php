<?php
// attendance_api/approve_leave.php
require_once 'auth_check.php';
require_once 'db.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->request_id) && !empty($data->action)) {
    $request_id = $data->request_id;
    $action = ($data->action === 'Approve') ? 'Approved' : 'Rejected';

    try {
        $pdo->beginTransaction();

        // 1. Update the leave request status
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
        $stmt->execute([$action, $request_id]);

        // 2. If approved, update the attendance record
        if ($action === 'Approved') {
            // Get details of the request
            $detailsStmt = $pdo->prepare("SELECT student_id, session_id, leave_type FROM leave_requests WHERE id = ?");
            $detailsStmt->execute([$request_id]);
            $req = $detailsStmt->fetch();

            if ($req['session_id']) {
                // Update specific session
                $updateAtt = $pdo->prepare("UPDATE attendance SET status = ? WHERE student_id = ? AND session_id = ?");
                $updateAtt->execute([$req['leave_type'], $req['student_id'], $req['session_id']]);
            }
        }

        $pdo->commit();
        echo json_encode(["status" => "success", "message" => "Request has been $action."]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
}
?>
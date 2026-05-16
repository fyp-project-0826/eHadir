<?php
// attendance_api/checkin.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once 'db.php';

// Get the raw JSON data sent from the PWA frontend
$data = json_decode(file_get_contents("php://input"));

// Ensure all required data is present
if(isset($data->student_id) && isset($data->session_id) && isset($data->qr_token)) {
    
    $student_id = $data->student_id;
    $session_id = $data->session_id;
    $qr_token = $data->qr_token;
    $student_lat = $data->lat ?? null; // Optional, depending on if you force location
    $student_long = $data->lng ?? null;

    try {
        // 1. Verify the QR token matches the session
        $stmt = $pdo->prepare("SELECT * FROM class_sessions WHERE id = ? AND qr_token = ?");
        $stmt->execute([$session_id, $qr_token]);
        $session = $stmt->fetch();

        if(!$session) {
            echo json_encode(["status" => "error", "message" => "Invalid or expired QR code."]);
            exit();
        }

        // 2. Insert Attendance Record
        // We use INSERT IGNORE or catch the duplicate error thanks to our UNIQUE constraint
        $insertStmt = $pdo->prepare("INSERT INTO attendance (student_id, session_id, status, scan_lat, scan_long) VALUES (?, ?, 'Present', ?, ?)");
        
        if($insertStmt->execute([$student_id, $session_id, $student_lat, $student_long])) {
            echo json_encode(["status" => "success", "message" => "Attendance marked successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to mark attendance."]);
        }

    } catch(PDOException $e) {
        // Handle duplicate check-in attempt
        if($e->getCode() == 23000) {
            echo json_encode(["status" => "error", "message" => "You have already checked in for this class."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(["status" => "error", "message" => "Incomplete data provided."]);
}

// Haversine Formula Function
function getDistance($lat1, $lon1, $lat2, $lon2) {
    $earth_radius = 6371000; // in meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $earth_radius * $c;
}

// Inside your try-catch block:
$stmt = $pdo->prepare("SELECT c.geo_lat, c.geo_long FROM class_sessions s 
                       JOIN classes c ON s.class_id = c.id 
                       WHERE s.id = ?");
$stmt->execute([$session_id]);
$class_loc = $stmt->fetch();

$distance = getDistance($student_lat, $student_long, $class_loc['geo_lat'], $class_loc['geo_long']);

if ($distance > 50) { // 50 meters limit
    echo json_encode(["status" => "error", "message" => "You are too far from the classroom!"]);
    exit();
}
?>
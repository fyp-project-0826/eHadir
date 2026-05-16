<?php
// attendance_api/register_student.php
require_once 'auth_check.php';
require_once 'db.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

// Security Check: Only Teachers or Admins should be able to register new students
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] === 'Student') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit();
}

// Get the JSON payload sent from the JavaScript fetch()
$data = json_decode(file_get_contents("php://input"));

// Verify that all required fields are present
if (!empty($data->user_id) && !empty($data->password) && !empty($data->full_name)) {
    
    // Sanitize and assign variables
    $user_id = htmlspecialchars(strip_tags($data->user_id));
    $full_name = htmlspecialchars(strip_tags($data->full_name));
    $password = $data->password;
    
    // Hardcode the role to 'Student' so this endpoint cannot be abused to create Admins
    $role = 'Student'; 

    // Hash the password for security
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    try {
        // Insert into the database
        $stmt = $pdo->prepare("INSERT INTO users (user_id, password_hash, full_name, role) VALUES (?, ?, ?, ?)");
        
        if ($stmt->execute([$user_id, $hashed_password, $full_name, $role])) {
            echo json_encode(["status" => "success", "message" => "Student '$full_name' registered successfully!"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to register student."]);
        }

    } catch (PDOException $e) {
        // Catch duplicate entry errors (e.g., if a teacher tries to register an ID that already exists)
        if ($e->getCode() == 23000) { 
            echo json_encode(["status" => "error", "message" => "Error: A user with this Student ID already exists."]);
        } else {
            // Catch any other database errors
            echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        }
    }
} else {
    // If the Javascript sent empty values
    echo json_encode(["status" => "error", "message" => "Please fill in all fields (Name, ID, and Password)."]);
}
?>
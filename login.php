<?php
// attendance_api/login.php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");

require_once 'db.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->user_id) && !empty($data->password)) {
    $user_id = $data->user_id;
    $password = $data->password;

    try {
        // Fetch user by their ID
        $stmt = $pdo->prepare("SELECT id, user_id, password_hash, full_name, role FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        // Verify if user exists and password is correct
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // Set session variables for the backend to remember the user
            $_SESSION['user_db_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            echo json_encode([
                "status" => "success",
                "message" => "Login successful",
                "user" => [
                    "id" => $user['id'],
                    "name" => $user['full_name'],
                    "role" => $user['role']
                ]
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid ID or Password."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Please provide both ID and Password."]);
}
?>
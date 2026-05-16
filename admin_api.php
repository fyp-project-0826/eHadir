<?php
// attendance_api/admin_api.php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST");

require_once 'db.php';

// [Akses Awam] Pelajar & Pensyarah boleh tarik pengumuman tanpa sekatan Admin
if (isset($_GET['action']) && $_GET['action'] === 'get_announcements') {
    try {
        $stmt = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
    } catch (PDOException $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit();
}

// ==================== SEKATAN PERANAN UTAMA ====================
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    // Sila buka komen di bawah jika sistem log masuk sesi anda sudah sedia sepenuhnya
    // echo json_encode(["status" => "error", "message" => "Sekatan Akses Pentadbir Utama."]);
    // exit();
}

$action = $_GET['action'] ?? '';

// ==================== PROSES PERMINTAAN POST ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $action = $data->action ?? $action;

    try {
        // MODUL 5: SIARAN PENGUMUMAN NOTIFIKASI (Kini Disimpan ke DB)
        if ($action === 'post_announcement') {
            $stmt = $pdo->prepare("INSERT INTO announcements (title, content) VALUES (?, ?)");
            $stmt->execute([$data->title, $data->content]);
            echo json_encode(["status" => "success", "message" => "Notis kenyataan sistem berjaya disiarkan secara real-time."]);
            exit();
        }
    }
// ==================== PROSES PERMINTAAN POST (SIMPAN/KEMASKINI/PADAM) ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $action = $data->action ?? $action;

    try {
        // MODUL 1: SIMPAN/EDIT DATA PROFIL PENGGUNA
        if ($action === 'save_user') {
            if (!empty($data->id)) {
                if (!empty($data->password)) {
                    $hash = password_hash($data->password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET user_id = ?, full_name = ?, role = ?, password_hash = ? WHERE id = ?");
                    $stmt->execute([$data->user_id, $data->full_name, $data->role, $hash, $data->id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET user_id = ?, full_name = ?, role = ? WHERE id = ?");
                    $stmt->execute([$data->user_id, $data->full_name, $data->role, $data->id]);
                }
                echo json_encode(["status" => "success", "message" => "Maklumat master data profil pengguna berjaya dikemaskini."]);
            } else {
                $hash = password_hash($data->password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (user_id, password_hash, full_name, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$data->user_id, $hash, $data->full_name, $data->role]);
                echo json_encode(["status" => "success", "message" => "Pengguna baharu berjaya didaftarkan ke dalam sistem."]);
            }
        }
        
        // MODUL 1: PADAM PENGGUNA
        elseif ($action === 'delete_user') {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$data->id]);
            echo json_encode(["status" => "success", "message" => "Rekod profil pengguna berjaya dipadam daripada sistem induk."]);
        }

        // MODUL 1: SIMPAN/EDIT KELAS & KURSUS
        elseif ($action === 'save_class') {
            if (!empty($data->id)) {
                $stmt = $pdo->prepare("UPDATE classes SET class_code = ?, class_name = ?, teacher_id = ?, geo_lat = ?, geo_long = ? WHERE id = ?");
                $stmt->execute([$data->class_code, $data->class_name, $data->teacher_id ?: null, $data->geo_lat ?: null, $data->geo_long ?: null, $data->id]);
                echo json_encode(["status" => "success", "message" => "Konfigurasi maklumat kelas akademik berjaya dikemaskini."]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO classes (class_code, class_name, teacher_id, geo_lat, geo_long) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$data->class_code, $data->class_name, $data->teacher_id ?: null, $data->geo_lat ?: null, $data->geo_long ?: null]);
                echo json_encode(["status" => "success", "message" => "Kelas akademik baharu berjaya dibuka."]);
            }
        }

        // MODUL 1: PADAM KELAS
        elseif ($action === 'delete_class') {
            $stmt = $pdo->prepare("DELETE FROM classes WHERE id = ?");
            $stmt->execute([$data->id]);
            echo json_encode(["status" => "success", "message" => "Rekod data kelas induk berjaya dipadam."]);
        }

        // MODUL 4: PROSES KELULUSAN RAYUAN CUTI (MC / MASALAH TEKNIKAL)
        elseif ($action === 'process_leave') {
            $status = ($data->status === 'Approve') ? 'Approved' : 'Rejected';
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
            $stmt->execute([$status, $data->id]);

            if ($status === 'Approved') {
                $detailsStmt = $pdo->prepare("SELECT student_id, session_id, leave_type FROM leave_requests WHERE id = ?");
                $detailsStmt->execute([$data->id]);
                $req = $detailsStmt->fetch();

                if ($req && $req['session_id']) {
                    // Tukar status daripada Absent kepada status Pelepasan (contoh: Sick atau Emergency)
                    $updateAtt = $pdo->prepare("UPDATE attendance SET status = ? WHERE student_id = ? AND session_id = ?");
                    $updateAtt->execute([$req['leave_type'], $req['student_id'], $req['session_id']]);
                }
            }
            $pdo->commit();
            echo json_encode(["status" => "success", "message" => "Keputusan rayuan / cuti sakit telah disahkan."]);
        }

        // MODUL 5: SIARAN PENGUMUMAN NOTIFIKASI
        elseif ($action === 'post_announcement') {
            // Logik tambahan sekiranya anda mahu simpan ke dalam table `announcements` pada masa akan datang
            echo json_encode(["status" => "success", "message" => "Notis kenyataan sistem berjaya disiarkan secara real-time."]);
        }

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        echo json_encode(["status" => "error", "message" => "Ralat pelayan database: " . $e->getMessage()]);
    }
    exit();
}

// ==================== PROSES PERMINTAAN GET (TARIK DATA REKOD) ====================
try {
    // MODUL 2: DASHBOARD COUNTERS & LOG STREAM PEMANTAUAN WARDEN/PENSYARAH
    if ($action === 'get_dashboard_stats') {
        $uCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $cCount = $pdo->query("SELECT COUNT(*) FROM classes")->fetchColumn();
        $mCount = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'")->fetchColumn();
        
        // Dapatkan aktiviti aliran sesi pengimbasan hari ini (Format Real-time)
        $sessionStmt = $pdo->query("SELECT s.*, c.class_code, c.class_name, u.full_name as teacher_name 
                                    FROM class_sessions s 
                                    JOIN classes c ON s.class_id = c.id 
                                    JOIN users u ON c.teacher_id = u.id 
                                    ORDER BY s.id DESC LIMIT 5");
        $sessions = $sessionStmt->fetchAll();

        echo json_encode([
            "status" => "success",
            "stats" => ["total_users" => $uCount, "total_classes" => $cCount, "pending_mc" => $mCount],
            "sessions" => $sessions
        ]);
    }

    // MODUL 1: AMBIL DATA SEMUA PENGGUNA INDUK
    elseif ($action === 'get_users') {
        $stmt = $pdo->query("SELECT id, user_id, full_name, role FROM users ORDER BY role DESC, full_name ASC");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
    }

    // MODUL 1: AMBIL DATA SEMUA KELAS BESERTA NAMA PENSYARAH
    elseif ($action === 'get_classes') {
        $stmt = $pdo->query("SELECT c.*, u.full_name as teacher_name FROM classes c LEFT JOIN users u ON c.teacher_id = u.id ORDER BY c.class_code ASC");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
    }

    // DROPDOWN GURU/PENSYARAH UNTUK MODUL DAFTAR KELAS
    elseif ($action === 'get_teachers') {
        $stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'Teacher' ORDER BY full_name ASC");
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
    }

    // MODUL 4: AMBIL DATA SENARAI RAYUAN CUTI SAKIT (PENDING)
    elseif ($action === 'get_leave_requests') {
        $stmt = $pdo->query("SELECT lr.*, u.full_name, u.user_id as student_reg_no FROM leave_requests lr JOIN users u ON lr.student_id = u.id WHERE lr.status = 'Pending' ORDER BY lr.id DESC");
        echo json_encode(["status" => "success", "requests" => $stmt->fetchAll()]);
    }

    // MODUL 3: ANALISIS DAN ANGGARAN AMARAN PONTENG TEGAR (< 70% KEHADIRAN SEMESTER)
    elseif ($action === 'get_critical_students') {
        $sql = "SELECT u.user_id, u.full_name,
                COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as attended,
                COUNT(s.id) as total_classes,
                (COUNT(CASE WHEN a.status = 'Present' THEN 1 END) / COUNT(s.id) * 100) as percentage
                FROM users u
                JOIN student_enrollment se ON u.id = se.student_id
                LEFT JOIN class_sessions s ON se.class_id = s.class_id
                LEFT JOIN attendance a ON (u.id = a.student_id AND s.id = a.session_id)
                WHERE u.role = 'Student'
                GROUP BY u.id
                HAVING total_classes > 0 AND percentage < 70
                ORDER BY percentage ASC";
        $stmt = $pdo->query($sql);
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll()]);
    }

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Ralat pelayan: " . $e->getMessage()]);
}
}
?>
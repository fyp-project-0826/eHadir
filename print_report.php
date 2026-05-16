<?php
require_once 'db.php';
require_once 'auth_check.php';

// Ambil parameter dari URL
$class_id = $_GET['class_id'] ?? null;
$filter_date = $_GET['date'] ?? null;
$filter_month = $_GET['month'] ?? null;

if (!$class_id) {
    die("Sila pilih kelas terlebih dahulu.");
}

try {
    // 1. Ambil maklumat kelas & guru
    $stmtClass = $pdo->prepare("SELECT c.*, u.full_name as teacher_name 
                                FROM classes c 
                                JOIN users u ON c.teacher_id = u.id 
                                WHERE c.id = ?");
    $stmtClass->execute([$class_id]);
    $class_info = $stmtClass->fetch();

    // 2. Bina Query Kehadiran dengan Filter
    $query = "SELECT u.user_id, u.full_name, 
              COUNT(CASE WHEN a.status = 'Present' THEN 1 END) as total_present,
              COUNT(s.id) as total_sessions
              FROM users u
              JOIN student_enrollment se ON u.id = se.student_id
              LEFT JOIN class_sessions s ON se.class_id = s.class_id
              LEFT JOIN attendance a ON (u.id = a.student_id AND s.id = a.session_id)
              WHERE se.class_id = ?";

    $params = [$class_id];

    if ($filter_date) {
        $query .= " AND s.session_date = ?";
        $params[] = $filter_date;
    } elseif ($filter_month) {
        $query .= " AND MONTH(s.session_date) = ?";
        $params[] = $filter_month;
    }

    $query .= " GROUP BY u.id ORDER BY u.full_name ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $students = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Ralat: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <title>Laporan Kehadiran - <?php echo $class_info['class_name']; ?></title>
    <style>
    /* Style umum untuk skrin komputer */
    body { 
        font-family: 'Arial', sans-serif; 
        color: #333; 
        background: #f4f7f6;
        padding: 20px;
    }
    .report-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        padding: 40px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .header { text-align: center; border-bottom: 3px double #333; padding-bottom: 20px; margin-bottom: 30px; }
    .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; }
    .header h2 { margin: 5px 0 0 0; font-size: 18px; color: #555; }
    
    .info-table { width: 100%; margin-bottom: 20px; font-size: 14px; }
    .info-table td { padding: 5px 0; }
    
    table.report-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
    table.report-table th, table.report-table td { border: 1px solid #000; padding: 8px; text-align: center; }
    table.report-table th { background-color: #e9ecef; font-weight: bold; }
    
    .status-bad { color: #dc3545; font-weight: bold; }
    .status-good { color: #28a745; font-weight: bold; }
    
    .signature-box { margin-top: 60px; display: flex; justify-content: space-between; }
    .sign-line { width: 200px; border-top: 1px solid #000; text-align: center; padding-top: 5px; font-size: 14px; }

    .no-print-zone { text-align: right; margin-bottom: 20px; }
    .btn-print { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; font-weight: bold;}
    .btn-print:hover { background: #0056b3; }

    /* MAGIC: Style KHAS apabila butang "Cetak" ditekan */
    @media print {
        body { background: white; padding: 0; }
        .report-container { box-shadow: none; max-width: 100%; padding: 0; }
        .no-print-zone { display: none !important; } /* Hilangkan butang print dalam kertas */
        table.report-table th { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; }
        .status-bad { color: black; } /* Print hitam putih lebih jelas */
        .status-good { color: black; }
        @page { margin: 1.5cm; } /* Margin kertas A4 */
    }
</style>
</head>
<body>
<div class="report-container">
<div class="no-print-zone">
    <button class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
</div>

<div class="header">
    <h1>LAPORAN KEHADIRAN PELAJAR</h1>
    <h2>KOLEJ VOKASIONAL MALAYSIA</h2>
</div>

<table class="info-table">
    <tr>
        <td>KOD KELAS: <?php echo $class_info['class_code']; ?></td>
        <td style="text-align: right;">GURU: <?php echo $class_info['teacher_name']; ?></td>
    </tr>
    <tr>
        <td>NAMA KELAS: <?php echo $class_info['class_name']; ?></td>
        <td style="text-align: right;">TARIKH LAPORAN: <?php echo date('d/m/Y'); ?></td>
    </tr>
</table>

<table class="report-table">
    <thead>
        <tr>
            <th>No. Matrik</th>
            <th>Nama Penuh</th>
            <th>Hadir</th>
            <th>Sesi</th>
            <th>Peratus (%)</th>
            <th>Kelayakan Exam</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($students as $row): 
            $peratus = ($row['total_sessions'] > 0) ? ($row['total_present'] / $row['total_sessions']) * 100 : 0;
            $layak = ($peratus >= 70);
        ?>
        <tr>
            <td><?php echo $row['user_id']; ?></td>
            <td style="text-align: left;"><?php echo $row['full_name']; ?></td>
            <td><?php echo $row['total_present']; ?></td>
            <td><?php echo $row['total_sessions']; ?></td>
            <td><?php echo number_format($peratus, 1); ?>%</td>
            <td class="<?php echo $layak ? 'status-good' : 'status-bad'; ?>">
                <?php echo $layak ? 'LAYAK' : 'TIDAK LAYAK'; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div style="margin-top: 50px;">
    <div style="float: left; width: 200px; border-top: 1px solid #000; text-align: center;">
        Tandatangan Guru
    </div>
    <div style="float: right; width: 200px; border-top: 1px solid #000; text-align: center;">
        Pengesahan Admin
    </div>
</div>
</div>
</body>
</html>
<?php
require 'connector.php';

$trck_id = isset($_GET['trck_id']) ? $_GET['trck_id'] : null;
$week_no = isset($_GET['week_no']) ? $_GET['week_no'] : null;

if ($trck_id && $week_no) {
    $query = "
        SELECT 
            wt.wheel_type, 
            o.odometer
        FROM truck t
        JOIN type wt ON t.wheel_name = wt.w_id
        LEFT JOIN odometer o ON t.trck_id = o.trck_id AND o.week_no = :week_no
        WHERE t.trck_id = :trck_id
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute(['trck_id' => $trck_id, 'week_no' => $week_no]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode($data);
} else {
    echo json_encode(['error' => 'Missing truck ID or week number']);
}
?>

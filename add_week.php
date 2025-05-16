<?php
require 'connector.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['week_no'])) {
    echo json_encode(['success' => false, 'message' => 'Missing week number']);
    exit;
}

$week_no = intval($data['week_no']);

if ($week_no <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid week number']);
    exit;
}

$checkWeek = $pdo->prepare("SELECT COUNT(*) FROM odometer WHERE week_no = ?");
$checkWeek->execute([$week_no]);

if ($checkWeek->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'message' => 'Week already exists']);
    exit;
}

$query = "INSERT INTO odometer (week_no) VALUES (?)";
$stmt = $pdo->prepare($query);

if ($stmt->execute([$week_no])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>

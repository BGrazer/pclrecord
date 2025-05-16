<?php
require 'connector.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['old_week_no']) || !isset($data['new_week_no'])) {
    echo json_encode(['success' => false, 'message' => 'Missing week numbers']);
    exit;
}

$old_week_no = intval($data['old_week_no']);
$new_week_no = intval($data['new_week_no']);

if ($new_week_no <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid new week number']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Check if new week_no already exists
    $checkWeek = $pdo->prepare("SELECT COUNT(*) FROM odometer WHERE week_no = ?");
    $checkWeek->execute([$new_week_no]);

    if ($checkWeek->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'New week number already exists']);
        $pdo->rollBack();
        exit;
    }

    // Update week_no
    $updateQuery = "UPDATE odometer SET week_no = ? WHERE week_no = ?";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute([$new_week_no, $old_week_no]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

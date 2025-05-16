<?php
require 'connector.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['old_column2']) || !isset($data['new_column2'])) {
    echo json_encode(['success' => false, 'message' => 'Missing column2 values']);
    exit;
}

$old_column2 = intval($data['old_column2']);
$new_column2 = intval($data['new_column2']);

if ($new_column2 <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid new column2 number']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Check if new column2 exists
    $checkWeek = $pdo->prepare("SELECT COUNT(*) FROM change_oil WHERE column2 = ?");
    $checkWeek->execute([$new_column2]);

    if ($checkWeek->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'New column2 already exists']);
        $pdo->rollBack();
        exit;
    }

    // Update column2
    $updateQuery = "UPDATE change_oil SET column2 = ? WHERE column2 = ?";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute([$new_column2, $old_column2]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

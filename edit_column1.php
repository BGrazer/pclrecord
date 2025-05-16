<?php
require 'connector.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['old_column1']) || !isset($data['new_column1'])) {
    echo json_encode(['success' => false, 'message' => 'Missing column1 values']);
    exit;
}

$old_column1 = intval($data['old_column1']);
$new_column1 = intval($data['new_column1']);

if ($new_column1 <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid new column1 number']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Check if new column1 exists
    $checkWeek = $pdo->prepare("SELECT COUNT(*) FROM change_oil WHERE column1 = ?");
    $checkWeek->execute([$new_column1]);

    if ($checkWeek->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'New column1 already exists']);
        $pdo->rollBack();
        exit;
    }

    // Update column1
    $updateQuery = "UPDATE change_oil SET column1 = ? WHERE column1 = ?";
    $updateStmt = $pdo->prepare($updateQuery);
    $updateStmt->execute([$new_column1, $old_column1]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

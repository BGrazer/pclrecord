<?php
require 'connector.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['column1'])) {
    echo json_encode(['success' => false, 'message' => 'Missing column1 number']);
    exit;
}

$column1 = intval($data['column1']);

if ($column1 <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid column1 number']);
    exit;
}

try {
    $pdo->beginTransaction();

    $maxQuery = $pdo->query("SELECT MAX(column1) FROM change_oil");
    $maxColumn1 = $maxQuery->fetchColumn();

    if ($maxColumn1 !== false && $column1 <= $maxColumn1) {
        echo json_encode(['success' => false, 'message' => 'New column1 must be higher than ' . $maxColumn1]);
        $pdo->rollBack();
        exit;
    }

    $checkWeek = $pdo->prepare("SELECT COUNT(*) FROM change_oil WHERE column1 = ?");
    $checkWeek->execute([$column1]);

    if ($checkWeek->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'column1 already exists']);
        $pdo->rollBack();
        exit;
    }

    $truckStmt = $pdo->query("SELECT trck_id FROM truck");
    $truckIds = $truckStmt->fetchAll(PDO::FETCH_COLUMN);

    $insertStmt = $pdo->prepare("INSERT INTO change_oil (trck_id, column1, column2, change_oil_odo) VALUES (?, ?, NULL, NULL)");

    foreach ($truckIds as $truckId) {
        $insertStmt->execute([$truckId, $column1]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

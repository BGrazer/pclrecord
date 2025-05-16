<?php
require 'connector.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['column2'])) {
    echo json_encode(['success' => false, 'message' => 'Missing column2 number']);
    exit;
}

$column2 = intval($data['column2']);

if ($column2 <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid column2 number']);
    exit;
}

try {
    $pdo->beginTransaction();

    $maxQuery = $pdo->query("SELECT MAX(column2) FROM change_oil");
    $maxColumn1 = $maxQuery->fetchColumn();

    if ($maxColumn1 !== false && $column2 <= $maxColumn1) {
        echo json_encode(['success' => false, 'message' => 'New column2 must be higher than ' . $maxColumn1]);
        $pdo->rollBack();
        exit;
    }

    $checkWeek = $pdo->prepare("SELECT COUNT(*) FROM change_oil WHERE column2 = ?");
    $checkWeek->execute([$column2]);

    if ($checkWeek->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'column2 already exists']);
        $pdo->rollBack();
        exit;
    }

    $truckStmt = $pdo->query("SELECT trck_id FROM truck");
    $truckIds = $truckStmt->fetchAll(PDO::FETCH_COLUMN);

    $insertStmt = $pdo->prepare("INSERT INTO change_oil (trck_id, column2, change_oil_odo) VALUES (?, ?, NULL)");

    foreach ($truckIds as $truckId) {
        $insertStmt->execute([$truckId, $column2]);
    }

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

<?php
require 'connector.php';

header('Content-Type: application/json');

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['trck_id']) || !isset($data['column2']) || !isset($data['change_oil_odo']) || !isset($data['date'])) {
        throw new Exception('Missing required data');
    }

    $trck_id = $data['trck_id'];
    $column2 = $data['column2'];
    $change_oil_odo = $data['change_oil_odo'];
    $date = $data['date'];

    $checkQuery = "SELECT COUNT(*) FROM change_oil WHERE trck_id = ? AND `column2` = ?";
    $checkStmt = $pdo->prepare($checkQuery);
    $checkStmt->execute([$trck_id, $column2]);
    $exists = $checkStmt->fetchColumn();

    if ($exists) {
        $query = "UPDATE change_oil SET change_oil_odo = ?, date = ? WHERE trck_id = ? AND `column2` = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$change_oil_odo, $date, $trck_id, $column2]);
    } else {
        $query = "INSERT INTO change_oil (trck_id, `column2`, change_oil_odo, date) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$trck_id, $column2, $change_oil_odo, $date]);
    }

    echo json_encode([ 'success' => true, 'message' => 'change_oil_odo updated successfully' ]);

} catch (Exception $e) {
    echo json_encode([ 'success' => false, 'message' => $e->getMessage() ]);
}
?>
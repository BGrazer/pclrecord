<?php
require 'connector.php';

header('Content-Type: application/json');

try {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['trck_id']) || !isset($data['week_no']) || !isset($data['odometer']) || !isset($data['record_date'])) {
        throw new Exception('Missing required data');
    }

    $trck_id = $data['trck_id'];
    $week_no = $data['week_no'];
    $odometer = $data['odometer'];
    $record_date = $data['record_date'];
    
    // Make sure status is properly set, even if empty
    $status = isset($data['status']) ? $data['status'] : '';

    // Check if record already exists
    $checkQuery = "SELECT COUNT(*) FROM odometer WHERE trck_id = :trck_id AND week_no = :week_no";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':trck_id', $trck_id);
    $checkStmt->bindParam(':week_no', $week_no);
    $checkStmt->execute();
    $exists = $checkStmt->fetchColumn();

    if ($exists) {
        throw new Exception('A record for this week already exists');
    }

    // Insert new record
    $query = "INSERT INTO odometer (trck_id, week_no, odometer, record_date, status) VALUES (:trck_id, :week_no, :odometer, :record_date, :status)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':trck_id', $trck_id);
    $stmt->bindParam(':week_no', $week_no);
    $stmt->bindParam(':odometer', $odometer);
    $stmt->bindParam(':record_date', $record_date);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR); // Explicitly specify string parameter
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'New week record added successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
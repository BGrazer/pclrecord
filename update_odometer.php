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

    // Update existing record
    $query = "UPDATE odometer SET odometer = :odometer, record_date = :record_date, status = :status WHERE trck_id = :trck_id AND week_no = :week_no";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':odometer', $odometer);
    $stmt->bindParam(':record_date', $record_date);
    $stmt->bindParam(':status', $status, PDO::PARAM_STR); // Explicitly specify string parameter
    $stmt->bindParam(':trck_id', $trck_id);
    $stmt->bindParam(':week_no', $week_no);
    $stmt->execute();

    // Check if any rows were affected
    if ($stmt->rowCount() === 0) {
        throw new Exception('No record found to update');
    }

    echo json_encode(['success' => true, 'message' => 'Odometer updated successfully']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
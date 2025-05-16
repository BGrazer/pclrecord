<?php
// get_maintenance_history.php - Retrieve maintenance history for a truck
session_start();
include('connector.php');

// Check if user is logged in
if (!isset($_SESSION['ad_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Check if truck ID is provided
if (!isset($_GET['trck_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing truck ID']);
    exit();
}

$truck_id = $_GET['trck_id'];

try {
    // Get truck information
    $truck_query = "SELECT trck_plate FROM truck WHERE trck_id = ?";
    $truck_stmt = $conn->prepare($truck_query);
    $truck_stmt->execute([$truck_id]);
    $truck_info = $truck_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$truck_info) {
        echo json_encode(['success' => false, 'message' => 'Truck not found']);
        exit();
    }

    // Get maintenance history - only resolved records
    $history_query = "SELECT mr.*, a.fname, a.lname 
                      FROM maintenance_resolutions mr
                      JOIN admin a ON mr.resolved_by = a.ad_id
                      WHERE mr.trck_id = ?
                      ORDER BY mr.resolved_date DESC";
    $history_stmt = $conn->prepare($history_query);
    $history_stmt->execute([$truck_id]);
    $history = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'truck' => $truck_info,
        'history' => $history
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
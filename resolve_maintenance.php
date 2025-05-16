<?php
// resolve_maintenance.php
session_start();
include('connector.php');

if (!isset($_SESSION['ad_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['trck_id']) || !isset($data['limit']) || !isset($data['current_odometer'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }

    $truck_id = $data['trck_id'];
    $limit = $data['limit'];
    $current_odometer = $data['current_odometer'];
    $maintenance_type = $data['maintenance_type'] ?? 'Oil Change'; 
    $resolved_by = $_SESSION['ad_id'];
    $resolved_date = date('Y-m-d H:i:s');

    try {
        // Check if this limit is already resolved for this truck
        $check_query = "SELECT resolution_id FROM maintenance_resolutions 
                        WHERE trck_id = ? AND odometer_limit = ? 
                        ORDER BY resolved_date DESC LIMIT 1";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->execute([$truck_id, $limit]);
        
        if ($check_stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'This maintenance limit is already marked as resolved']);
            exit();
        }

        // Insert new resolution record
        $query = "INSERT INTO maintenance_resolutions (trck_id, odometer_limit, current_odometer, maintenance_type, resolved_by, resolved_date) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$truck_id, $limit, $current_odometer, $maintenance_type, $resolved_by, $resolved_date]);

        echo json_encode(['success' => true, 'message' => 'Maintenance marked as resolved']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
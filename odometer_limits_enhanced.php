<?php
// Modified version of odometer_limits.php
// Add this to the end of your existing odometer_limits.php file

/**
 * Check if a maintenance limit has been marked as resolved
 * 
 * @param int $truck_id The truck ID
 * @param int $limit The odometer limit
 * @return bool True if maintenance has been marked as resolved
 */
function isMaintenanceLimitResolved($truck_id, $limit) {
    global $conn;
    
    $query = "SELECT resolution_id FROM maintenance_resolutions 
              WHERE trck_id = ? AND odometer_limit = ? 
              ORDER BY resolved_date DESC LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([$truck_id, $limit]);
    
    return $stmt->rowCount() > 0;
}

/**
 * Get all resolved maintenance limits for a truck
 * 
 * @param int $truck_id The truck ID
 * @return array List of resolved maintenance limits
 */
function getResolvedMaintenanceLimits($truck_id) {
    global $conn;
    
    $query = "SELECT odometer_limit FROM maintenance_resolutions 
              WHERE trck_id = ? 
              GROUP BY odometer_limit";
    $stmt = $conn->prepare($query);
    $stmt->execute([$truck_id]);
    
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Enhance the warning check to include maintenance status
 * 
 * @param string $plate_no The truck plate number
 * @param int $current_odometer The current odometer reading
 * @param int $truck_id The truck ID
 * @return array Contains warning level and maintenance status information
 */
function checkOdometerWarningWithMaintenance($plate_no, $current_odometer, $truck_id) {
    global $odometer_limits;
    
    // First get the basic warning info based on plate-specific limits
    $result = checkOdometerWarning($plate_no, $current_odometer);
    
    // Get resolved maintenance limits for this specific truck
    $resolved_limits = getResolvedMaintenanceLimits($truck_id);
    $result['resolved_limits'] = $resolved_limits;
    
    // Check if current odometer has passed any limits that are resolved
    $result['is_resolved'] = false;
    $result['highest_resolved_limit'] = 0;
    
    // Find the highest resolved limit that the odometer has passed
    foreach ($resolved_limits as $resolved_limit) {
        if ($current_odometer >= $resolved_limit && $resolved_limit > $result['highest_resolved_limit']) {
            $result['highest_resolved_limit'] = $resolved_limit;
            $result['is_resolved'] = true;
        }
    }
    
    // Check if the current odometer is below or equal to the highest resolved limit
    $result['within_resolved_range'] = ($result['highest_resolved_limit'] > 0 && 
                                      $current_odometer <= $result['highest_resolved_limit']);
    
    // Check if the next upcoming limit is resolved
    if ($result['next_limit'] && in_array($result['next_limit'], $resolved_limits)) {
        $result['next_limit_resolved'] = true;
    } else {
        $result['next_limit_resolved'] = false;
    }
    
    // Track status of each limit (resolved or not)
    if (!empty($result['all_limits'])) {
        $result['limit_statuses'] = [];
        foreach ($result['all_limits'] as $limit) {
            $result['limit_statuses'][$limit] = in_array($limit, $resolved_limits);
        }
    }
    
    return $result;
}
/**
 * Update the CSS class function to include the resolved state
 * 
 * @param string $warning_level The warning level (normal, yellow, orange, red, resolved)
 * @return string The CSS class name
 */
function getWarningClassWithResolution($warning_level) {
    // This function now only returns warning classes, not the resolved state
    switch ($warning_level) {
        case 'red':
            return 'bg-danger text-white';
        case 'orange':
            return 'bg-warning text-dark';
        case 'yellow':
            return 'bg-warning bg-opacity-50 text-dark';
        default:
            return '';
    }
}
?>
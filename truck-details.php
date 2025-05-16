<?php
session_start(); 
include('connector.php');
include('odometer_limits.php');
include('odometer_limits_enhanced.php');

if (!isset($_SESSION['ad_id'])) {
    header("Location: pages-login.php");
    exit();
}

$fname = $_SESSION['fname'];
$lname = $_SESSION['lname'];
$email_user = $_SESSION['email'];
$about = $_SESSION['about'];
$phone = $_SESSION['phone'];
$profile = $_SESSION['profile'];

$fullname = $fname." ".$lname;

$query = "SELECT COUNT(*) as total FROM truck";
$stmt = $conn->prepare($query);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$truck_count = $row['total'];

$type_query = "SELECT w_id, wheel_type FROM type";
$type_stmt = $conn->prepare($type_query);
$type_stmt->execute();
$types = $type_stmt->fetchAll(PDO::FETCH_ASSOC);

$counts = [];
$names = [];
foreach ($types as $type) {
    $counts[$type['w_id']] = 0;
    $names[$type['w_id']] = $type['wheel_type'];
}

$count_query = "
    SELECT wheel_name, COUNT(*) as total 
    FROM truck 
    GROUP BY wheel_name
";
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute();
$truck_counts = $count_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($truck_counts as $row) {
    $counts[$row['wheel_name']] = $row['total'];
}

$query = "SELECT * FROM admin";
$stmt = $conn->prepare($query);
$stmt->execute();
$email = $stmt->fetchAll(PDO::FETCH_ASSOC); 

$wheel_type = isset($_GET['wheel_type']) ? $_GET['wheel_type'] : '';
$selected_truck = isset($_GET['truck_plate']) ? $_GET['truck_plate'] : '';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Get trucks for dropdown
$truck_query = "
    SELECT t.trck_id, t.trck_plate, t.wheel_name, wt.wheel_type
    FROM truck t
    JOIN type wt ON t.wheel_name = wt.w_id
    WHERE wt.wheel_type = :wheel_type
    " . ($search_term ? "AND t.trck_plate LIKE :search_term" : "") . "
    ORDER BY t.trck_plate
";
$truck_stmt = $conn->prepare($truck_query);
$params = ['wheel_type' => $wheel_type];
if ($search_term) {
    $params['search_term'] = "%$search_term%";
}
$truck_stmt->execute($params);
$truck_options = $truck_stmt->fetchAll(PDO::FETCH_ASSOC);

// If search returns exactly one result, automatically select it
if ($search_term && count($truck_options) === 1 && !$selected_truck) {
    $selected_truck = $truck_options[0]['trck_plate'];
    // Redirect to the same page with the selected truck
    header("Location: truck-details.php?wheel_type=" . urlencode($wheel_type) . "&truck_plate=" . urlencode($selected_truck));
    exit();
}

$weekQuery = "SELECT DISTINCT week_no FROM odometer ORDER BY week_no ASC";
$weekStmt = $conn->prepare($weekQuery);
$weekStmt->execute();
$weeks = $weekStmt->fetchAll(PDO::FETCH_COLUMN);

// Get the truck details if a truck is selected
$selected_truck_details = null;
if ($selected_truck) {
    $truck_details_query = "
        SELECT t.trck_id, t.wheel_name, t.trck_plate, wt.wheel_type
        FROM truck t
        JOIN type wt ON t.wheel_name = wt.w_id
        WHERE t.trck_plate = :truck_plate
    ";
    $truck_details_stmt = $conn->prepare($truck_details_query);
    $truck_details_stmt->execute(['truck_plate' => $selected_truck]);
    $selected_truck_details = $truck_details_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get maximum week number to determine next week
    $max_week_query = "SELECT MAX(week_no) as max_week FROM odometer WHERE trck_id = :trck_id";
    $max_week_stmt = $conn->prepare($max_week_query);
    $max_week_stmt->execute(['trck_id' => $selected_truck_details['trck_id']]);
    $max_week = $max_week_stmt->fetch(PDO::FETCH_ASSOC)['max_week'];
    $next_week = $max_week ? $max_week + 1 : 1;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">

  <title>PCL - Trucks Weekly PMS</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <link href="assets/img/icon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

  <link href="assets/css/style.css" rel="stylesheet">
  <link href="assets/css/style1.css" rel="stylesheet">
  
  <style>
    .truck-info {
      background-color: #f8f9fa;
      border-radius: 5px;
      padding: 15px;
      margin-bottom: 20px;
    }
    .truck-info p {
      margin-bottom: 5px;
      font-size: 16px;
    }
    .truck-info p strong {
      font-weight: 600;
    }
    .week-container {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 20px;
    }
    .week-box {
      border: 1px solid #ddd;
      border-radius: 5px;
      width: 230px;
      overflow: hidden;
    }
    .week-header {
      background-color: #e9ecef;
      padding: 8px 12px;
      font-weight: 600;
      text-align: center;
    }
    .week-content {
      padding: 10px;
    }
    .week-field {
      margin-bottom: 8px;
    }
    .week-field label {
      display: block;
      font-size: 14px;
      margin-bottom: 3px;
      color: #6c757d;
    }
    .week-field span {
      font-size: 15px;
    }
    .edit-btn {
      margin-top: 10px;
      display: block;
      width: 100%;
    }
    .add-week-btn {
      margin-left: 10px;
      padding: 5px 15px;
    }
    .search-container {
      margin-bottom: 15px;
    }
    .search-results {
      margin-top: 10px;
    }
    .search-result-item {
      padding: 8px;
      border: 1px solid #ddd;
      margin-bottom: 5px;
      cursor: pointer;
      border-radius: 4px;
    }
    .search-result-item:hover {
      background-color: #f0f0f0;
    }
    .odometer-display {
        padding: 5px 10px;
        border-radius: 4px;
        font-weight: bold;
    }
    
    .progress-bar-warning {
        background-color: #ffc107;
    }
    
    .progress-bar-danger {
        background-color: #dc3545;
    }
    
    .limit-badge {
        margin-left: 5px;
        font-size: 12px;
        padding: 2px 5px;
        border-radius: 10px;
        background-color: #6c757d;
        color: white;
    }
    
    .next-limit {
        font-size: 13px;
        margin-top: 5px;
        color: #6c757d;
    }
    
    .limit-tag {
        display: inline-block;
        margin: 2px;
        padding: 1px 5px;
        border-radius: 3px;
        font-size: 11px;
        background-color: #e9ecef;
    }
    
    .limit-tag.reached {
        background-color: #28a745;
        color: white;
    }
    
    .limit-reached-indicator {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 5px;
    }
    
    .limit-reached-indicator.reached {
        background-color: #28a745;
    }
    
    .limit-reached-indicator.not-reached {
        background-color: #dc3545;
    }
    .maintenance-resolved {
        background-color: #28a745;
        color: white;
    }
    
    .btn-resolve {
        margin-top: 5px;
        font-size: 12px;
        padding: 2px 6px;
    }
    
    .resolution-info {
        font-size: 12px;
        margin-top: 5px;
        font-style: italic;
    }
    
    .limit-tag.resolved {
        background-color: #28a745;
        color: white;
    }
    
    .resolution-badge {
        font-size: 11px;
        margin-left: 5px;
        padding: 1px 5px;
        border-radius: 10px;
        background-color: #28a745;
        color: white;
    }
    
    /* Modal styles */
    .resolution-modal .modal-header {
        background-color: #28a745;
        color: white;
    }
    
    .resolution-history {
        max-height: 200px;
        overflow-y: auto;
        margin-top: 10px;
    }
    
    .resolution-history-item {
        padding: 8px;
        border-bottom: 1px solid #eee;
    }
    
    .resolution-history-item:last-child {
        border-bottom: none;
    }
        .maintenance-resolved {
        background-color: #28a745;
        color: white;
    }
  </style>
</head>
<body>
<header id="header" class="header fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
      <a href="index.php" class="logo d-flex align-items-center">
        <img src="assets/img/pcl_logo.png" alt="">
        <span class="d-none d-lg-block">Pick Count Log.</span>
      </a>
      <i class="bi bi-list toggle-sidebar-btn"></i>
    </div><!-- End Logo -->

    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">
        <li class="nav-item dropdown pe-3">
          <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
          <img src="<?php echo $_SESSION['profile'] ?? 'default.jpg'; ?>" alt="Profile" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
          <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo $fullname; ?></span>
          </a><!-- End Profile Iamge Icon -->

          <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
            <li class="dropdown-header">
              <h6><?php echo $fullname; ?></h6>
              <span><?php echo $email_user; ?></span>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="users-profile.php">
                <i class="bi bi-person"></i>
                <span>My Profile</span>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="users-profile.php">
                <i class="bi bi-gear"></i>
                <span>Account Settings</span>
              </a>
            </li>
            <li>
              <hr class="dropdown-divider">
            </li>

            <li>
              <hr class="dropdown-divider">
            </li>

            <li>
              <a class="dropdown-item d-flex align-items-center" href="logout.php">
                <i class="ri-logout-box-line"></i>
                <span>Sign Out</span>
              </a>
            </li>

          </ul><!-- End Profile Dropdown Items -->
        </li><!-- End Profile Nav -->

      </ul>
    </nav><!-- End Icons Navigation -->

  </header><!-- End Header -->
<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">

      <li class="nav-item">
        <a class="nav-link collapsed" href="index.php">
          <i class="bi bi-grid"></i>
          <span>Dashboard</span>
        </a>
      </li><!-- End Dashboard Nav -->

      <li class="nav-item">
          <a class="nav-link" data-bs-target="#components-nav" data-bs-toggle="collapse" href="#">
              <i class="ri ri-truck-line"></i><span>Trucks</span><i class="bi bi-chevron-down ms-auto"></i>
          </a>
          <ul id="components-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
              <?php
              // Loop through each type and create an individual list item for each wheel_type
              foreach ($types as $type) {
                  echo '<li>';
                  echo '<a href="truck-details.php?wheel_type=' . $type['wheel_type'] . '">';
                  echo '<i class="bi bi-circle"></i><span>' . $type['wheel_type'] . ' (' . $counts[$type['w_id']] . ' trucks)</span>';
                  echo '</a>';
                  echo '</li>';
              }
              ?>
          </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" href="change-oil-record1.php">
          <i class="ri-oil-line"></i><span>Changed Oil (FUSO)</span></a></li>

        <li class="nav-item">
        <a class="nav-link collapsed" href="change-oil-record2.php">
          <i class="ri-oil-line"></i><span>Changed Oil (HINO)</span></a></li>

      <li class="nav-heading">Pages</li>

      <li class="nav-item">
        <a class="nav-link collapsed" href="users-profile.php">
          <i class="bi bi-person"></i>
          <span>Profile</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" href="accounts.php">
          <i class="bi bi-people"></i>
          <span>Accounts</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" href="logout.php">
          <i class="ri-logout-box-line"></i>
          <span>Sign out</span>
        </a>
      </li>
    </ul>

  </aside>

  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Weekly Truck Mileage Report</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">Wheel Type: <?php echo htmlspecialchars($wheel_type); ?></li>
        </ol>
      </nav>
    </div>

    <section class="section dashboard">
    <div class="row">
        <div class="col-lg-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Select Truck</h5>
                    
                    <form method="get" action="truck-details.php" class="mb-4">
                        <input type="hidden" name="wheel_type" value="<?php echo htmlspecialchars($wheel_type); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <!-- Search bar for filtering truck plates -->
                                <div class="search-container mb-3">
                                    <div class="input-group">
                                        <input type="text" class="form-control" placeholder="Search plate number..." name="search" value="<?php echo htmlspecialchars($search_term); ?>" id="searchInput">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="bi bi-search"></i> Search
                                        </button>
                                        <?php if($search_term): ?>
                                        <a href="truck-details.php?wheel_type=<?php echo htmlspecialchars($wheel_type); ?>" class="btn btn-outline-secondary">
                                            <i class="bi bi-x-lg"></i> Clear
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php if($search_term && count($truck_options) > 0): ?>
                                    <div class="search-results mt-2">
                                        <div class="list-group">
                                            <?php foreach ($truck_options as $truck): ?>
                                                <a href="truck-details.php?wheel_type=<?php echo htmlspecialchars($wheel_type); ?>&truck_plate=<?php echo htmlspecialchars($truck['trck_plate']); ?>" 
                                                   class="list-group-item list-group-item-action search-result-item">
                                                    <?php echo htmlspecialchars($truck['trck_plate']); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="truck_plate" class="form-label">Truck Plate No:</label>
                                    <select class="form-select" id="truck_plate" name="truck_plate" onchange="this.form.submit()">
                                        <option value="">-- Select Truck --</option>
                                        <?php foreach ($truck_options as $truck): ?>
                                            <option value="<?php echo htmlspecialchars($truck['trck_plate']); ?>" 
                                                <?php echo ($selected_truck == $truck['trck_plate']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($truck['trck_plate']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if(count($truck_options) === 0 && $search_term): ?>
                                        <div class="alert alert-warning mt-2">
                                            No trucks found matching "<?php echo htmlspecialchars($search_term); ?>".
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <?php if ($selected_truck_details): ?>
                    <div class="truck-info">
                        <p><strong>Wheel Name (Type):</strong> <?php echo htmlspecialchars($selected_truck_details['wheel_name']) . ' (' . htmlspecialchars($selected_truck_details['wheel_type']) . ')'; ?></p>
                        <p><strong>Truck Plate:</strong> <?php echo htmlspecialchars($selected_truck_details['trck_plate']); ?></p>
                        <button type="button" class="btn btn-success btn-sm add-week-btn" onclick="openAddModal('<?php echo $selected_truck_details['trck_id']; ?>', '<?php echo $next_week; ?>', '<?php echo $selected_truck_details['trck_plate']; ?>', '<?php echo $selected_truck_details['wheel_type']; ?>')">
                            <i class="bi bi-plus-circle"></i> Add Week
                        </button>
                        <button type="button" class="btn btn-info btn-sm ms-2" onclick="viewMaintenanceHistory('<?php echo $selected_truck_details['trck_id']; ?>', '<?php echo $selected_truck_details['trck_plate']; ?>')">
                            <i class="bi bi-clock-history"></i> View Maintenance History
                        </button>
                    </div>

                    <?php if ($selected_truck_details): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5>Odometer Warning Levels</h5>
                            </div>
                            <div class="card-body mt-3">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center mb-2">
                                            <div style="width: 20px; height: 20px;" class="bg-danger me-2"></div>
                                            <span><strong>Red:</strong> Less than 1,000 km to next limit</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">  
                                        <div class="d-flex align-items-center mb-2">
                                            <div style="width: 20px; height: 20px;" class="bg-warning me-2"></div>
                                            <span><strong>Orange:</strong> 1,001 - 3,000 km to next limit</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center mb-2">
                                            <div style="width: 20px; height: 20px;" class="bg-warning bg-opacity-50 me-2"></div>
                                            <span><strong>Yellow:</strong> 3,001 - 5,000 km to next limit</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        The system tracks maintenance limits for each truck. Small dots below the odometer reading show all maintenance checkpoints - 
                                        green dots indicate passed limits, red dots indicate upcoming limits.
                                    </small>
                                </div>
                                
                                <?php 
                                // Get the latest week data for the truck
                                $latest_week_query = "SELECT odometer FROM odometer WHERE trck_id = ? ORDER BY week_no DESC LIMIT 1";
                                $latest_week_stmt = $conn->prepare($latest_week_query);
                                $latest_week_stmt->execute([$selected_truck_details['trck_id']]);
                                $latest_week_data = $latest_week_stmt->fetch(PDO::FETCH_ASSOC);
                                
                                if ($latest_week_data) {
                                    $odometer_warning = checkOdometerWarningWithMaintenance(
                                        $selected_truck_details['trck_plate'], 
                                        $latest_week_data['odometer'],
                                        $selected_truck_details['trck_id']
                                    );
                                    
                                    if (!empty($odometer_warning['all_limits'])): ?>
                                        <div class="mt-3">
                                            <h6>Maintenance Checkpoints:</h6>
                                            <div class="d-flex flex-wrap">
                                                <?php 
                                                foreach($odometer_warning['all_limits'] as $limit): 
                                                    $reached = $latest_week_data['odometer'] >= $limit;
                                                    $resolved = isset($odometer_warning['limit_statuses'][$limit]) && $odometer_warning['limit_statuses'][$limit];
                                                    $tag_class = $resolved ? 'resolved' : ($reached ? 'reached' : '');
                                                ?>
                                                    <span class="limit-tag <?php echo $tag_class; ?>" 
                                                        data-bs-toggle="tooltip" 
                                                        title="<?php echo $resolved ? 'Maintenance resolved' : ($reached ? 'Limit reached' : 'Upcoming limit'); ?>">
                                                        <span class="limit-reached-indicator <?php echo $reached ? 'reached' : 'not-reached'; ?>"></span>
                                                        <?php echo number_format($limit, 0, '.', ','); ?>
                                                        <?php if($reached && !$resolved): ?>
                                                            <i class="bi bi-wrench-adjustable" style="font-size: 10px;"></i>
                                                        <?php elseif($resolved): ?>
                                                            <i class="bi bi-check-circle" style="font-size: 10px;"></i>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif;
                                }
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="week-container">
                        <?php 
                        // Get all weeks for this truck
                        $odoQuery = "SELECT odometer, record_date, week_no, status FROM odometer WHERE trck_id = ? ORDER BY week_no ASC";
                        $odoStmt = $conn->prepare($odoQuery);
                        $odoStmt->execute([$selected_truck_details['trck_id']]);
                        $weeks_data = $odoStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        foreach ($weeks_data as $week_data): 
                            // Check for odometer warnings with maintenance status
                            // Inside your week display loop
$odometer_warning = checkOdometerWarningWithMaintenance(
    $selected_truck_details['trck_plate'], 
    $week_data['odometer'],
    $selected_truck_details['trck_id']
);

// Only apply green background if the odometer is within resolved range (≤ highest resolved limit)
$warning_class = $odometer_warning['within_resolved_range'] ? 'maintenance-resolved' : getWarningClassWithResolution($odometer_warning['warning_level']);
                        ?>
                        
                            <div class="week-box">
                                <div class="week-header">Week <?php echo htmlspecialchars($week_data['week_no']); ?> | <span style="font-size: 14px;"><?php echo date('F d, Y', strtotime($week_data['record_date'])); ?> </span> </div>
                                <div class="week-content">
                                    <div class="week-field">
                                        <label>Odometer:</label>
                                        <span class="odometer-display <?php echo $warning_class; ?>">
                                            <?php echo number_format($week_data['odometer'], 0, '.', ','); ?>
                                        </span>
                                        
                                        <?php if(isset($odometer_warning['next_limit_resolved']) && $odometer_warning['next_limit_resolved']): ?>
                                            <span class="resolution-badge">
                                                <i class="bi bi-check-circle"></i> Resolved
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if($odometer_warning['warning_level'] !== 'normal' && $odometer_warning['warning_level'] !== 'resolved' && $odometer_warning['next_limit']): ?>
                                            <div class="next-limit">
                                                <strong>Next limit:</strong> <?php echo number_format($odometer_warning['next_limit'], 0, '.', ','); ?> 
                                                <span class="badge bg-secondary"><?php echo number_format($odometer_warning['distance_to_limit'], 0, '.', ','); ?> km left</span>
                                                
                                                <?php if($odometer_warning['warning_level'] !== 'normal'): ?>
                                                    <button class="btn btn-success btn-sm btn-resolve" 
                                                            onclick="openResolveModal(
                                                                '<?php echo $selected_truck_details['trck_id']; ?>', 
                                                                <?php echo $odometer_warning['next_limit']; ?>, 
                                                                <?php echo $week_data['odometer']; ?>,
                                                                '<?php echo $selected_truck_details['trck_plate']; ?>'
                                                            )">
                                                        <i class="bi bi-check-circle"></i> Mark Resolved
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if($odometer_warning['percentage']): ?>
                                                <div class="progress mt-1" style="height: 5px;">
                                                    <div class="progress-bar <?php echo $odometer_warning['warning_level'] === 'red' ? 'progress-bar-danger' : 'progress-bar-warning'; ?>" 
                                                        role="progressbar" 
                                                        style="width: <?php echo $odometer_warning['percentage']; ?>%;" 
                                                        aria-valuenow="<?php echo $odometer_warning['percentage']; ?>" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="100"></div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <hr>
                                    <div class="week-field">
                                        <label style="font-weight: bold;">Truck Report:</label>
                                        <span><?php echo isset($week_data['status']) && $week_data['status'] !== '' ? htmlspecialchars($week_data['status']) : 'N/A'; ?></span>
                                    </div>
                                    <button type="button" class="btn btn-primary btn-sm edit-btn" 
                                            onclick="openEditModal('<?php echo $selected_truck_details['trck_id']; ?>', 
                                                            '<?php echo $week_data['week_no']; ?>', 
                                                            '<?php echo $selected_truck_details['trck_plate']; ?>', 
                                                            '<?php echo $selected_truck_details['wheel_type']; ?>', 
                                                            '<?php echo $week_data['odometer']; ?>', 
                                                            '<?php echo $week_data['record_date']; ?>', 
                                                            '<?php echo $week_data['status'] ?? ''; ?>')">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($weeks_data)): ?>
                            <div class="alert alert-info">No weekly records found for this truck. Use the "Add Week" button to add your first record.</div>
                        <?php endif; ?>
                    </div>

                    <?php elseif ($wheel_type): ?>
                        <div class="alert alert-info">Please select a truck from the dropdown or search results to view its weekly mileage data.</div>
                    <?php else: ?>
                        <div class="alert alert-warning">Please select a wheel type from the sidebar menu first.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

  </main>
  
  <!-- Edit Week Modal -->
  <div class="modal fade" id="editWeekModal" tabindex="-1">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title">Edit Week Record</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                  <input type="hidden" id="edit_trck_id">
                  <input type="hidden" id="edit_week_no">
                  <div class="mb-3">
                      <label class="form-label">Truck Plate</label>
                      <input type="text" class="form-control" id="edit_trck_plate" readonly>
                  </div>
                  <div class="mb-3">
                      <label class="form-label">Wheel Type</label>
                      <input type="text" class="form-control" id="edit_wheel_type" readonly>
                  </div>
                  <div class="mb-3">
                      <label class="form-label">Odometer Reading</label>
                      <input type="number" class="form-control" id="edit_odometer" required>
                  </div>
                  <div class="mb-3">
                      <label class="form-label">Record Date</label>
                      <input type="date" class="form-control" id="edit_record_date" required>
                  </div>
                  <div class="mb-3">
                      <label class="form-label">Status</label>
                      <input type="text" class="form-control" id="edit_status" required>
                  </div>
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  <button type="button" class="btn btn-primary" id="saveEditBtn">Save Changes</button>
              </div>
          </div>
      </div>
  </div>
  
  <!-- Add Week Modal -->
  <div class="modal fade" id="addWeekModal" tabindex="-1">
      <div class="modal-dialog">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title">Add New Week Record</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                  <input type="hidden" id="add_trck_id">
                  <div class="mb-3">
                      <label class="form-label">Week Number</label>
                      <input type="number" class="form-control" id="add_week_no" readonly>
                  </div>
                  <div class="mb-3">
                      <label class="form-label">Truck Plate</label>
                      <input type="text" class="form-control" id="add_trck_plate" readonly>
                  </div>
                  <div class="mb-3">
                      <label class="form-label">Wheel Type</label>
                      <input type="text" class="form-control" id="add_wheel_type" readonly>
                  </div>
                  <div class="mb-3">
                      <label class="form-label">Odometer Reading</label>
                      <input type="number" class="form-control" id="add_odometer" required>
                  </div>
                  <div class="mb-3">
                      <label class="form-label">Record Date</label>
                      <input type="date" class="form-control" id="add_record_date" required>
                  </div>
                  <div class="mb-3">
                      <label class="form-label">Status</label>
                      <input type="text" class="form-control" id="add_status" required>
                  </div>
              </div>
              <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  <button type="button" class="btn btn-primary" id="saveAddBtn">Save Record</button>
              </div>
          </div>
      </div>
  </div>
<script>
    function openResolveModal(trck_id, limit, current_odometer, trck_plate) {
    // Create modal HTML
    let modalHTML = `
        <div class="modal fade resolution-modal" id="resolveModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Mark Maintenance as Resolved</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to mark the maintenance for truck <strong>${trck_plate}</strong> at <strong>${numberWithCommas(limit)}</strong> km as resolved?</p>
                        <div class="mb-3">
                            <label class="form-label">Current Odometer:</label>
                            <input type="number" class="form-control" id="resolve_current_odometer" value="${current_odometer}" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Maintenance Type:</label>
                            <select class="form-control" id="maintenance_type">
                                <option value="Oil Change">Oil Change</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-success" id="confirmResolveBtn">Mark Resolved</button>
                    </div>
                </div>
            </div>
        </div>`;
    
    // Add modal to DOM
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('resolveModal'));
    modal.show();
    
    // Handle confirm button click
    document.getElementById('confirmResolveBtn').addEventListener('click', function() {
        const maintenance_type = document.getElementById('maintenance_type').value;
        
        fetch('resolve_maintenance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                trck_id, 
                limit, 
                current_odometer,
                maintenance_type
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Maintenance successfully marked as resolved.');
                modal.hide();
                location.reload(); // Refresh to show updated status
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    });
    
    // Remove modal from DOM after it's closed
    document.getElementById('resolveModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}
    function viewMaintenanceHistory(trck_id, trck_plate) {
        fetch(`get_maintenance_history.php?trck_id=${trck_id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Create modal HTML
                    let modalHTML = `
                        <div class="modal fade" id="maintenanceHistoryModal" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header bg-primary text-white">
                                        <h5 class="modal-title">Maintenance History - ${trck_plate}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Odometer Limit</th>
                                                        <th>Current Odometer</th>
                                                        <th>Maintenance Type</th>
                                                        <th>Resolved By</th>
                                                        <th>Resolved Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>`;
                    
                    // Add each resolved record to the table
                    data.history.forEach(record => {
                        modalHTML += `
                            <tr class="${record.current_odometer >= record.odometer_limit ? 'table-success' : ''}">
                                <td>${numberWithCommas(record.odometer_limit)}</td>
                                <td>${numberWithCommas(record.current_odometer)}</td>
                                <td>${record.maintenance_type || 'Oil Change'}</td>
                                <td>${record.fname} ${record.lname}</td>
                                <td>${new Date(record.resolved_date).toLocaleString()}</td>
                            </tr>`;
                    });
                    
                    modalHTML += `
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                    
                    // Add modal to DOM
                    document.body.insertAdjacentHTML('beforeend', modalHTML);
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('maintenanceHistoryModal'));
                    modal.show();
                    
                    // Remove modal from DOM after it's closed
                    document.getElementById('maintenanceHistoryModal').addEventListener('hidden.bs.modal', function() {
                        this.remove();
                    });
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    // Helper function to format numbers with commas
    function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    function openEditModal(trck_id, week_no, trck_plate, wheel_type, odometer, record_date, status) {
        document.getElementById('edit_trck_id').value = trck_id;
        document.getElementById('edit_week_no').value = week_no;
        document.getElementById('edit_trck_plate').value = trck_plate;
        document.getElementById('edit_wheel_type').value = wheel_type;
        document.getElementById('edit_odometer').value = odometer;
        document.getElementById('edit_record_date').value = record_date.split(' ')[0]; // Format date for input
        document.getElementById('edit_status').value = status;
        
        var modal = new bootstrap.Modal(document.getElementById('editWeekModal'));
        modal.show();
    }
    
    function openAddModal(trck_id, next_week, trck_plate, wheel_type) {
        document.getElementById('add_trck_id').value = trck_id;
        document.getElementById('add_week_no').value = next_week;
        document.getElementById('add_trck_plate').value = trck_plate;
        document.getElementById('add_wheel_type').value = wheel_type;
        document.getElementById('add_odometer').value = '';
        document.getElementById('add_record_date').value = new Date().toISOString().split('T')[0]; // Today's date
        document.getElementById('add_status').value = '';
        
        var modal = new bootstrap.Modal(document.getElementById('addWeekModal'));
        modal.show();
    }

    document.getElementById('saveEditBtn').addEventListener('click', function() {
        let trck_id = document.getElementById('edit_trck_id').value;
        let week_no = document.getElementById('edit_week_no').value;
        let odometer = document.getElementById('edit_odometer').value;
        let record_date = document.getElementById('edit_record_date').value;
        let status = document.getElementById('edit_status').value;

        if (odometer === '' || record_date === '') {
            alert('Please fill all required fields.');
            return;
        }

        fetch('update_odometer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                trck_id, 
                week_no, 
                odometer,
                record_date,
                status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Week record updated successfully.');
                var modal = bootstrap.Modal.getInstance(document.getElementById('editWeekModal'));
                modal.hide();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    });
    
    document.getElementById('saveAddBtn').addEventListener('click', function() {
        let trck_id = document.getElementById('add_trck_id').value;
        let week_no = document.getElementById('add_week_no').value;
        let odometer = document.getElementById('add_odometer').value;
        let record_date = document.getElementById('add_record_date').value;
        let status = document.getElementById('add_status').value;

        if (odometer === '' || record_date === '') {
            alert('Please fill all required fields.');
            return;
        }

        fetch('add_odometer.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                trck_id, 
                week_no, 
                odometer,
                record_date,
                status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('New week record added successfully.');
                var modal = bootstrap.Modal.getInstance(document.getElementById('addWeekModal'));
                modal.hide();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    });

    // Auto-submit form when search term is entered (after a small delay)
    document.getElementById('searchInput').addEventListener('input', function(e) {
        if (e.target.value.length > 2) {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => {
                this.form.submit();
            }, 500);
        }
    });
</script>

  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">&copy; Copyright <strong><span>Producers Connection Logistics Inc</span></strong>. All Rights Reserved</div>
  </footer>
  <!-- End Footer -->

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>

  <!-- Vendor JS Files -->
  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>

  <!-- Template Main JS File -->
  <script src="assets/js/main.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
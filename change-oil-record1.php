<?php
session_start(); 
include('connector.php');

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

$query = "
    SELECT 
        t.trck_id, 
        t.wheel_name, 
        t.trck_plate, 
        wt.wheel_type,
        co.column1,
        co.change_oil_odo,
        co.date
    FROM truck t
    JOIN type wt ON t.wheel_name = wt.w_id
    LEFT JOIN change_oil co ON t.trck_id = co.trck_id
    WHERE t.wheel_name IN (2, 3)  -- Add this WHERE clause to filter
    ORDER BY t.trck_id, co.column1
";

$stmt = $pdo->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$trucks = [];
foreach ($results as $row) {
    $trck_id = $row['trck_id'];
    if (!isset($trucks[$trck_id])) {
        $trucks[$trck_id] = [
            'trck_id' => $row['trck_id'],
            'wheel_name' => $row['wheel_name'],
            'wheel_type' => $row['wheel_type'],
            'trck_plate' => $row['trck_plate'],
            'readings' => []
        ];
    }
    if ($row['column1'] !== null) {
        $trucks[$trck_id]['readings'][$row['column1']] = [
            'odo' => $row['change_oil_odo'],
            'date' => $row['date']
        ];
    }
}

$column1Query = "SELECT DISTINCT `column1` FROM change_oil ORDER BY `column1` ASC";
$column1Stmt = $pdo->prepare($column1Query);
$column1Stmt->execute();
$columns1 = $column1Stmt->fetchAll(PDO::FETCH_COLUMN);

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

$count_query = "SELECT wheel_name, COUNT(*) as total FROM truck GROUP BY wheel_name";
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute();
$truck_counts = $count_stmt->fetchAll(PDO::FETCH_ASSOC);


foreach ($truck_counts as $row) {
    $counts[$row['wheel_name']] = $row['total'];
}
$display_query = "
    SELECT 
        t.trck_id, 
        t.wheel_name, 
        t.trck_plate, 
        wt.wheel_type,
        co.column1,
        co.change_oil_odo,
        co.date
    FROM truck t
    JOIN type wt ON t.wheel_name = wt.w_id
    LEFT JOIN change_oil co ON t.trck_id = co.trck_id
    WHERE t.wheel_name IN (2, 3)  -- Filter only for display
    ORDER BY t.trck_id, co.column1
";

$stmt = $pdo->prepare($display_query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <title>PCL - Truck Changed Oil</title>
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
    <link href="assets/css/style2.css" rel="stylesheet">
</head>
<body>

<header id="header" class="header fixed-top d-flex align-items-center">

    <div class="d-flex align-items-center justify-content-between">
    <a href="index.php" class="logo d-flex align-items-center">
        <img src="assets/img/pcl_logo.png" alt="">
        <span class="d-none d-lg-block">Pick Count Log.</span>
    </a>
    <i class="bi bi-list toggle-sidebar-btn"></i>
    </div>

    <nav class="header-nav ms-auto">
        <ul class="d-flex align-items-center">
            <a href=""></a>
            <li class="nav-item dropdown pe-3">
            <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                <img src="<?php echo $_SESSION['profile'] ?? 'default.jpg'; ?>" alt="Profile" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
                <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo $fullname; ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                <li class="dropdown-header">
                <h6><?php echo $fullname; ?></h6>
                <span><?php echo $email_user; ?></span>
                </li>
                <li><hr class="dropdown-divider"></li>

                <li><a class="dropdown-item d-flex align-items-center" href="users-profile.php">
                <i class="bi bi-person"></i><span>My Profile</span></a></li>
                
                <li><hr class="dropdown-divider"></li>

                <li><a class="dropdown-item d-flex align-items-center" href="users-profile.php">
                <i class="bi bi-gear"></i><span>Account Settings</span></a></li>

                <li><hr class="dropdown-divider"></li>

                <li><hr class="dropdown-divider"></li>

                <li><a class="dropdown-item d-flex align-items-center" href="logout.php">
                <i class="ri-logout-box-line"></i><span>Sign Out</span></a></li>
            </ul>
            </li>
        </ul>
    </nav>
</header>

<!-- ======= Sidebar ======= -->
<aside id="sidebar" class="sidebar">

    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" href="index.php">
            <i class="bi bi-grid"></i>
            <span>Dashboard</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link collapsed" data-bs-target="#components-nav" data-bs-toggle="collapse" href="#">
                <i class="ri ri-truck-line"></i><span>Trucks</span><i class="bi bi-chevron-down ms-auto"></i>
            </a>
            <ul id="components-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                <?php
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
        <a class="nav-link" href="change-oil-record1.php">
          <i class="ri-oil-line"></i><span>Changed Oil (FUSO)</span></a></li>

        <li class="nav-item">
        <a class="nav-link collapsed" href="change-oil-record2.php">
          <i class="ri-oil-line"></i><span>Changed Oil (HINO)</span></a></li>

        <li class="nav-heading">Pages</li>

        <li class="nav-item"><a class="nav-link collapsed" href="users-profile.php">
            <i class="bi bi-person"></i><span>Profile</span></a></li>

        <li class="nav-item"><a class="nav-link collapsed" href="accounts.php">
            <i class="bi bi-people"></i><span>Accounts</span></a></li>

        <li class="nav-item"><a class="nav-link collapsed" href="logout.php">
            <i class="ri-logout-box-line"></i><span>Sign out</span></a></li>
    </ul>
</aside>

    <!-- ======= Main ======= -->
    <main id="main" class="main">
        <div class="pagetitle">
            <h1> Truck Changed Oil Record</h1>
            <nav>
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active">10W FUSO | 10W FUSO AC</li>
                </ol>
            </nav>
        </div>


        <section class="section dashboard">
            <div class="row">
                <div class="col-lg-12">
                    <div class="row">
                        <div class="container mt-4">
                            <div class="controls-container">
                                <button class="btn btn-success mb-3" onclick="addColumn()">Add Column</button>
                                <button class="btn btn-warning mb-3" onclick="$editColumn1()">Edit Column</button>

                                
                                <div class="search-filter-wrapper">
                                    <div class="search-container">
                                        <input type="text" 
                                            id="tableSearch" 
                                            class="form-control" 
                                            placeholder="Search for plate number...">
                                    </div>
                                    <div class="column1-filter">
                                        <select class="form-select" id="ColumnFilter">
                                            <option value="all">All Columns</option>
                                            <option value="1-10">Column1 1-10</option>
                                            <option value="11-20">Column1 11-20</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="table-container">
                                <div class="table-responsive">
                                    <div style="overflow-x: auto; width: 100%;">
                                        <table class="table table-bordered" style="table-layout: fixed; width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th class="fixed-col first-col">#</th>
                                                    <th class="fixed-col second-col">Wheel Name (Type)</th>
                                                    <th class="fixed-col third-col">Truck Plate</th>
                                                    <?php foreach ($columns1 as $column1): ?>
                                                        <?php if (!is_null($column1) && $column1 !== ''): ?> 
                                                            <th style="width: 200px;">
                                                                <?php echo htmlspecialchars(number_format($column1)); ?>
                                                                <div class="small text-muted">Odo / Date</div>
                                                            </th>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody id="truckTableBody">
                                                <?php 
                                                $counter = 1;
                                                foreach ($trucks as $truck): ?>
                                                    <tr class="hover-highlight" 
                                                        data-search-id="<?php echo $counter; ?>" 
                                                        data-search-wheel="<?php echo strtolower(htmlspecialchars($truck['wheel_name'])); ?>" 
                                                        data-search-plate="<?php echo strtolower(htmlspecialchars($truck['trck_plate'])); ?>">
                                                        
                                                        <td class="fixed-col first-col">
                                                            <?php echo $counter++;?>
                                                        </td>
                                                        <td class="fixed-col second-col">
                                                            <?php echo htmlspecialchars($truck['wheel_name']) . ' (' . htmlspecialchars($truck['wheel_type']) . ')'; ?>
                                                        </td>
                                                        <td class="fixed-col third-col">
                                                            <?php echo htmlspecialchars($truck['trck_plate']); ?>
                                                        </td>

                                                        <?php foreach ($columns1 as $column1): ?>
    <?php if (!is_null($column1) && $column1 !== ''): ?> 
        <td class="clickable-cell" 
            onclick="openModal('<?php echo htmlspecialchars($truck['trck_id']); ?>', 
                            '<?php echo htmlspecialchars($column1); ?>', 
                            '<?php echo htmlspecialchars($truck['trck_plate']); ?>', 
                            '<?php echo htmlspecialchars($truck['wheel_type']); ?>')"
            data-trck-id="<?php echo htmlspecialchars($truck['trck_id']); ?>"
            data-column1="<?php echo htmlspecialchars($column1); ?>">

            <?php 
            $reading = $truck['readings'][$column1] ?? null;

            if ($reading) {
                echo $reading['odo'] 
                    ? htmlspecialchars(number_format($reading['odo'], 0, '.', ',')) 
                    : '-'; 
                echo '<br>';

                echo !empty($reading['date']) && $reading['date'] !== '0000-00-00'
                    ? '<small class="text-muted">' . date('F j, Y', strtotime($reading['date'])) . '</small>'
                    : '<small class="text-muted">-</small>';
            } else {
                echo '-<br><small class="text-muted">-</small>';
            }
            ?>
        </td>
    <?php endif; ?>
<?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modal -->
    <div class="modal fade" id="addOdometerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update change_oil_odo Reading</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="current_trck_id">
                    <div class="mb-3">
                        <label class="form-label">Column1 Number</label>
                        <input type="text" class="form-control" id="column1" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Truck Plate</label>
                        <input type="text" class="form-control" id="trck_plate" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Wheel Type</label>
                        <input type="text" class="form-control" id="wheel_type" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Changed Oil (Odometer)</label>
                        <input type="number" class="form-control" id="change_oil_odo" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">DATE Reading</label>
                        <input type="date" class="form-control" id="date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveOdometerBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>











<!-- ======= Script ======= -->
 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('tableSearch');
        const tbody = document.getElementById('truckTableBody');
        const rows = tbody.getElementsByTagName('tr');
        searchInput.addEventListener('input', function() {const searchTerm = this.value.toLowerCase().trim();

            for (let row of rows) {
                const truckId = row.getAttribute('data-search-id');
                const wheelName = row.getAttribute('data-search-wheel');
                const plateName = row.getAttribute('data-search-plate');
                if (truckId.includes(searchTerm) || wheelName.includes(searchTerm) || plateName.includes(searchTerm)) {row.style.display = '';
                } else {row.style.display = 'none';
                }
            }
        });

        function debounce(func, wait) {
            let timeout;
            return function() {const context = this;const args = arguments;clearTimeout(timeout);timeout = setTimeout(() => {func.apply(context, args);}, wait);};
        }

        searchInput.addEventListener('input', debounce(function() {const searchTerm = this.value.toLowerCase().trim();filterTable(searchTerm);}, 300));
        function filterTable(searchTerm) {
            for (let row of rows) {
                const truckId = row.getAttribute('data-search-id');
                const wheelName = row.getAttribute('data-search-wheel');
                const plateName = row.getAttribute('data-search-plate');

                if (truckId.includes(searchTerm) || wheelName.includes(searchTerm) || plateName.includes(searchTerm)) {row.style.display = '';
                } else {row.style.display = 'none';
                }
            }
        }
    });

    document.getElementById('ColumnFilter').addEventListener('change', function() {
        const selectedRange = this.value;
        const table = document.querySelector('.table');
        const headerCells = table.querySelectorAll('thead th');
        const dataCells = table.querySelectorAll('tbody tr');
        const columnStartIndex = 3;
        
        headerCells.forEach((cell, index) => {
            if (index >= columnStartIndex) {
                const columnText = cell.textContent;
                const columnNumber = parseInt(columnText.replace('Column1 ', ''));
                
                if (selectedRange === 'all') {cell.style.display = '';
                } else if (selectedRange === '1-10') {cell.style.display = (columnNumber >= 1 && columnNumber <= 10) ? '' : 'none';
                } else if (selectedRange === '11-20') {cell.style.display = (columnNumber >= 11 && columnNumber <= 20) ? '' : 'none';
                }
            }
        });
        
        dataCells.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                if (index >= columnStartIndex) {const columnNumber = parseInt(headerCells[index].textContent.replace('Column1 ', ''));
                    if (selectedRange === 'all') {cell.style.display = '';
                    } else if (selectedRange === '1-10') {cell.style.display = (columnNumber >= 1 && columnNumber <= 10) ? '' : 'none';
                    } else if (selectedRange === '11-20') {cell.style.display = (columnNumber >= 11 && columnNumber <= 20) ? '' : 'none';
                    }
                }
            });
        });
    });

    function openModal(trck_id, column1, trck_plate, wheel_type) {
        document.getElementById('current_trck_id').value = trck_id;
        document.getElementById('column1').value = column1;
        document.getElementById('trck_plate').value = trck_plate;
        document.getElementById('wheel_type').value = wheel_type;
        
        document.getElementById('saveOdometerBtn').dataset.trckId = trck_id;
        document.getElementById('saveOdometerBtn').dataset.columnNo = column1;

        var modal = new bootstrap.Modal(document.getElementById('addOdometerModal'));
        modal.show();
    }

    function addColumn() {
        var newColumn1 = prompt("Enter a new column1 number:");
        if (!newColumn1) {alert("Please enter a column1 number.");return;}

        newColumn1 = parseInt(newColumn1);
        if (isNaN(newColumn1) || newColumn1 <= 0) {alert("Invalid column1 number. Please enter a valid number.");return;}

        fetch('add_column1.php', {method: 'POST',headers: {'Content-Type': 'application/json',},body: JSON.stringify({column1: newColumn1})})
        .then(response => response.json())
        .then(data => {
            if (data.success) {alert("column1 " + newColumn1 + " added successfully!");location.reload();} 
            else {alert('Error adding column1: ' + (data.message || 'Unknown error'));}})
        .catch(error => {console.error('Error:', error);alert('Failed to connect to server.');});
    }

    function $editColumn1() {
        var oldColumn1 = prompt("Enter the existing column1 number to edit:");
        var newColumn1 = prompt("Enter the new column1 number:");

        if (!oldColumn1 || !newColumn1) {
            alert("Please enter both old and new column1 numbers.");
            return;
        }

        oldColumn1 = parseInt(oldColumn1);
        newColumn1 = parseInt(newColumn1);

        if (isNaN(oldColumn1) || isNaN(newColumn1) || newColumn1 <= 0) {
            alert("Invalid column1 number. Please enter valid numbers.");
            return;
        }

        fetch('edit_column1.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({old_column1: oldColumn1, new_column1: newColumn1})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("column1 updated successfully!");
                location.reload();
            } else {
                alert("Error updating column1: " + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to connect to server.');
        });
    }


    document.getElementById('saveOdometerBtn').addEventListener('click', function() {
        let trck_id = this.dataset.trckId;
        let column1 = this.dataset.columnNo;
        let change_oil_odo = document.getElementById('change_oil_odo').value;
        let date = document.getElementById('date').value;

        if (change_oil_odo === '') {alert('Please enter an odometer reading.');return;}
        if (date === '') {alert('Please enter a date.');return;}

        fetch('update_change_oil_c1.php', {method: 'POST',headers: { 'Content-Type': 'application/json' },body: JSON.stringify({ trck_id, column1, change_oil_odo, date })})
        .then(response => response.json())
        .then(data => {
            if (data.success) {let cell = document.querySelector(`td[data-trck-id="${trck_id}"][data-column1="${column1}"]`);
            if (cell) {cell.innerHTML = `${change_oil_odo}<br><small class="text-muted">${date}</small>`;}
        alert('Record updated successfully.');
        var modal = bootstrap.Modal.getInstance(document.getElementById('addOdometerModal'));
        modal.hide();
        } else {alert('Error: ' + data.message);}})
        .catch(error => console.error('Error:', error));
    });
</script>











  <!-- ======= Footer ======= -->
  <footer id="footer" class="footer">
    <div class="copyright">&copy; Copyright <strong><span>Producers Connection Logistics Inc</span></strong>. All Rights Reserved</div>
  </footer>

  <a href="#" class="back-to-top d-flex align-items-center justify-content-center"><i class="bi bi-arrow-up-short"></i></a>
  <script src="assets/vendor/apexcharts/apexcharts.min.js"></script>
  <script src="assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/chart.js/chart.umd.js"></script>
  <script src="assets/vendor/echarts/echarts.min.js"></script>
  <script src="assets/vendor/quill/quill.js"></script>
  <script src="assets/vendor/simple-datatables/simple-datatables.js"></script>
  <script src="assets/vendor/tinymce/tinymce.min.js"></script>
  <script src="assets/vendor/php-email-form/validate.js"></script>
  <script src="assets/js/main.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
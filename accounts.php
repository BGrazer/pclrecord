<?php
session_start(); 
include('connector.php');

// Redirect if not logged in
if (!isset($_SESSION['ad_id'])) {
    header("Location: pages-login.php");
    exit();
}

// Set user session variables
$fname = $_SESSION['fname'];
$lname = $_SESSION['lname'];
$email_user = $_SESSION['email'];
$about = $_SESSION['about'];
$phone = $_SESSION['phone'];
$profile = $_SESSION['profile'];
$fullname = $fname . " " . $lname;

// Fetch truck count
$query = "SELECT COUNT(*) as total FROM truck";
$stmt = $conn->prepare($query);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
$truck_count = $row['total'];

// Fetch truck types
$type_query = "SELECT w_id, wheel_type FROM type";
$type_stmt = $conn->prepare($type_query);
$type_stmt->execute();
$types = $type_stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare counts and names arrays
$counts = [];
$names = [];
foreach ($types as $type) {
    $counts[$type['w_id']] = 0;
    $names[$type['w_id']] = $type['wheel_type'];
}

// Get counts per wheel type
$count_query = "SELECT wheel_name, COUNT(*) as total FROM truck GROUP BY wheel_name";
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute();
$truck_counts = $count_stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($truck_counts as $row) {
    $counts[$row['wheel_name']] = $row['total'];
}

// Fetch all admin accounts
$query = "SELECT * FROM admin";
$stmt = $conn->prepare($query);
$stmt->execute();
$email = $stmt->fetchAll(PDO::FETCH_ASSOC); 
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>PCL - Admin Accounts</title>
  <meta content="" name="description">
  <meta content="" name="keywords">

  <!-- Favicons -->
  <link href="assets/img/icon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Google Fonts -->
  <link href="https://fonts.gstatic.com" rel="preconnect">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,300i,400,400i,600,600i,700,700i|Nunito:300,300i,400,400i,600,600i,700,700i|Poppins:300,300i,400,400i,500,500i,600,600i,700,700i" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/boxicons/css/boxicons.min.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.snow.css" rel="stylesheet">
  <link href="assets/vendor/quill/quill.bubble.css" rel="stylesheet">
  <link href="assets/vendor/remixicon/remixicon.css" rel="stylesheet">
  <link href="assets/vendor/simple-datatables/style.css" rel="stylesheet">

  <!-- Template Main CSS File -->
  <link href="assets/css/style.css" rel="stylesheet">
  
  <style>
    .card-body .flex-wrap {line-height: 1.5;}
    .delete-btn {
      margin-left: 10px;
    }
  </style>
</head>

<body>
  <!-- Header -->
  <header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
      <a href="index.php" class="logo d-flex align-items-center">
        <img src="assets/img/pcl_logo.png" alt="">
        <span class="d-none d-lg-block">PCL Inc. Oil & Odo Tracking</span>
      </a>
      <i class="bi bi-list toggle-sidebar-btn"></i>
    </div>

    <nav class="header-nav ms-auto">
      <ul class="d-flex align-items-center">
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
            <li>
              <a class="dropdown-item d-flex align-items-center" href="users-profile.php">
                <i class="bi bi-person"></i>
                <span>My Profile</span>
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="users-profile.php">
                <i class="bi bi-gear"></i>
                <span>Account Settings</span>
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item d-flex align-items-center" href="logout.php">
                <i class="ri-logout-box-line"></i>
                <span>Sign Out</span>
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>

  <!-- Sidebar -->
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
          <?php foreach ($types as $type): ?>
            <li>
              <a href="truck-details.php?wheel_type=<?= $type['wheel_type'] ?>&w_id=<?= $type['w_id'] ?>">
                <i class="bi bi-circle"></i><span><?= $type['wheel_type'] ?> (<?= $counts[$type['w_id']] ?> trucks)</span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" href="change-oil-record1.php">
          <i class="ri-oil-line"></i><span>Changed Oil (10W FUSO)</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link collapsed" href="change-oil-record2.php">
          <i class="ri-oil-line"></i><span>Changed Oil (HINO)</span>
        </a>
      </li>

      <li class="nav-heading">Pages</li>

      <li class="nav-item">
        <a class="nav-link collapsed" href="users-profile.php">
          <i class="bi bi-person"></i>
          <span>Profile</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="accounts.php">
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

  <!-- Main Content -->
  <main id="main" class="main">
    <div class="pagetitle">
      <h1>Account Management</h1>
      <nav>
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="index.php">Home</a></li>
          <li class="breadcrumb-item active">Accounts</li>
        </ol>
      </nav>
    </div>

    <section class="section">
      <div class="row">
        <div class="col-12">
          <div class="card top-selling overflow-auto">
            <div class="card-body pb-0">
              <h5 class="card-title">Accounts</h5>
              
              <!-- Add Account Button -->
              <div class="d-flex justify-content-end mb-3">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
                  <i class="bi bi-plus-circle"></i> Add Account
                </button>
              </div>
              
              <?php if(isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                  <?php echo htmlspecialchars($_GET['success']); ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>
              
              <?php if(isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                  <?php echo htmlspecialchars($_GET['error']); ?>
                  <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
              <?php endif; ?>
              
              <table class="table table-borderless datatable">
                <thead>
                  <tr>
                    <th scope="col">#</th>
                    <th scope="col">Profile</th>
                    <th scope="col">First Name</th>
                    <th scope="col">Last Name</th>
                    <th scope="col">Email</th>
                    <th scope="col">Contact No.</th>
                    <th scope="col">Role</th>
                    <th scope="col">Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($email as $index => $data): ?>
                    <tr>
                      <td><?= $index + 1 ?></td>
                      <td><img src="<?= $data['profile'] ?? 'uploads/default.jpg' ?>" alt="Profile" style="width: 30px; height: 30px; object-fit: cover; border-radius: 50%;"></td>
                      <td><?= htmlspecialchars($data['fname']) ?></td>
                      <td><?= htmlspecialchars($data['lname']) ?></td>
                      <td><?= htmlspecialchars($data['email']) ?></td>
                      <td><?= htmlspecialchars($data['phone']) ?></td>
                      <td><?= htmlspecialchars($data['role'] ?? 'admin') ?></td>
                      <td>
                        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateAccountModal" 
                          onclick="setUpdateFormValues(
                            '<?= $data['ad_id'] ?>',
                            '<?= $data['fname'] ?>',
                            '<?= $data['lname'] ?>',
                            '<?= $data['email'] ?>',
                            '<?= $data['phone'] ?>',
                            '<?= $data['role'] ?? 'admin' ?>'
                          )">
                          <i class="bi bi-pencil-square"></i> Edit
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- Update Account Modal -->
  <div class="modal fade" id="updateAccountModal" tabindex="-1" aria-labelledby="updateAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="updateAccountModalLabel">Update Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="updateAccountForm" action="update_account.php" method="POST">
            <input type="hidden" id="update_ad_id" name="ad_id">
            <div class="mb-3">
              <label for="update_fname" class="form-label">First Name</label>
              <input type="text" class="form-control" id="update_fname" name="fname" required>
            </div>
            <div class="mb-3">
              <label for="update_lname" class="form-label">Last Name</label>
              <input type="text" class="form-control" id="update_lname" name="lname" required>
            </div>
            <div class="mb-3">
              <label for="update_email" class="form-label">Email</label>
              <input type="email" class="form-control" id="update_email" name="email" required>
            </div>
            <div class="mb-3">
              <label for="update_phone" class="form-label">Contact No.</label>
              <input type="text" class="form-control" id="update_phone" name="phone" required>
            </div>
            <div class="mb-3">
              <label for="update_role" class="form-label">Role</label>
              <select class="form-select" id="update_role" name="role" required>
                <option value="admin">Admin</option>
                <option value="Monitor">Monitor</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="update_password" class="form-label">New Password (leave blank to keep current)</label>
              <input type="password" class="form-control" id="update_password" name="password">
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary">Save changes</button>
              <button type="button" class="btn btn-danger delete-btn" onclick="confirmDelete()">
                <i class="bi bi-trash"></i> Delete Account
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Delete</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this account? This action cannot be undone.</p>
          <p><strong>Account: </strong><span id="delete_account_name"></span></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <form id="deleteAccountForm" action="delete_account.php" method="POST">
            <input type="hidden" id="delete_ad_id" name="ad_id">
            <button type="submit" class="btn btn-danger">Delete Account</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Add Account Modal -->
  <div class="modal fade" id="addAccountModal" tabindex="-1" aria-labelledby="addAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addAccountModalLabel">Add New Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="addAccountForm" action="add_account.php" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
              <label for="profileImage" class="form-label">Profile Image</label>
              <div>
                <input type="file" name="profileImage" id="profileImageInput" accept="image/*" onchange="updateFileName()">
                <span id="fileName" style="margin-left: 10px; color: #555;"></span>
              </div>
            </div>
            <div class="mb-3">
              <label for="add_fname" class="form-label">First Name</label>
              <input type="text" class="form-control" id="add_fname" name="fname" required>
            </div>
            <div class="mb-3">
              <label for="add_lname" class="form-label">Last Name</label>
              <input type="text" class="form-control" id="add_lname" name="lname" required>
            </div>
            <div class="mb-3">
              <label for="add_about" class="form-label">About</label>
              <textarea class="form-control" id="add_about" name="about" required></textarea>
            </div>
            <div class="mb-3">
              <label for="add_email" class="form-label">Email</label>
              <input type="email" class="form-control" id="add_email" name="email" required>
            </div>
            <div class="mb-3">
              <label for="add_phone" class="form-label">Contact No.</label>
              <input type="text" class="form-control" id="add_phone" name="phone" required>
            </div>
            <div class="mb-3">
              <label for="add_password" class="form-label">Password</label>
              <input type="password" class="form-control" id="add_password" name="password" required>
            </div>
            <div class="mb-3">
              <label for="add_confirm_password" class="form-label">Confirm Password</label>
              <input type="password" class="form-control" id="add_confirm_password" name="confirm_password" required>
            </div>
            <div class="mb-3">
              <label for="add_role" class="form-label">Role</label>
              <select class="form-select" id="add_role" name="role" required>
                <option value="Admin">Admin</option>
                <option value="Monitor">Monitor</option>
              </select>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary">Add Account</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer id="footer" class="footer">
    <div class="copyright">
      &copy; Copyright <strong><span>Producers Connection Logistics Inc</span></strong>. All Rights Reserved
    </div>
  </footer>

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

  <script>
    // Function to set form values when update button is clicked
    function setUpdateFormValues(ad_id, fname, lname, email, phone, role) {
      document.getElementById('update_ad_id').value = ad_id;
      document.getElementById('update_fname').value = fname;
      document.getElementById('update_lname').value = lname;
      document.getElementById('update_email').value = email;
      document.getElementById('update_phone').value = phone;
      document.getElementById('update_role').value = role;
    }

    // Function to show delete confirmation modal
    function confirmDelete() {
      const adId = document.getElementById('update_ad_id').value;
      const fname = document.getElementById('update_fname').value;
      const lname = document.getElementById('update_lname').value;
      
      // Set the account name in the confirmation modal
      document.getElementById('delete_account_name').textContent = fname + ' ' + lname;
      
      // Set the ad_id in the delete form
      document.getElementById('delete_ad_id').value = adId;
      
      // Hide the update modal
      const updateModal = bootstrap.Modal.getInstance(document.getElementById('updateAccountModal'));
      updateModal.hide();
      
      // Show the delete confirmation modal
      const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
      deleteModal.show();
    }

    // Function to display selected filename
    function updateFileName() {
      const input = document.getElementById('profileImageInput');
      const fileName = input.files.length > 0 ? input.files[0].name : '';
      document.getElementById('fileName').textContent = fileName;
    }
  </script>
</body>
</html>
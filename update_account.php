<?php
session_start();
include('connector.php');

// Redirect if not logged in
if (!isset($_SESSION['ad_id'])) {
    header("Location: pages-login.php");
    exit();
}

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $ad_id = $_POST['ad_id'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];
    $password = $_POST['password']; // This might be empty if not changing

    try {
        // Start transaction
        $conn->beginTransaction();

        // Build the SQL query based on whether password is being updated
        if (!empty($password)) {
            // Update with new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE admin SET 
                      fname = :fname, 
                      lname = :lname, 
                      email = :email, 
                      phone = :phone, 
                      role = :role, 
                      password = :password 
                      WHERE ad_id = :ad_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':password', $hashed_password);
        } else {
            // Update without changing password
            $query = "UPDATE admin SET 
                      fname = :fname, 
                      lname = :lname, 
                      email = :email, 
                      phone = :phone, 
                      role = :role 
                      WHERE ad_id = :ad_id";
            
            $stmt = $conn->prepare($query);
        }

        // Bind parameters
        $stmt->bindParam(':fname', $fname);
        $stmt->bindParam(':lname', $lname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':ad_id', $ad_id);

        // Execute the update query
        $stmt->execute();

        // Commit the transaction
        $conn->commit();

        // Check if we're updating the current user
        if ($_SESSION['ad_id'] == $ad_id) {
            // Update session variables
            $_SESSION['fname'] = $fname;
            $_SESSION['lname'] = $lname;
            $_SESSION['email'] = $email;
            $_SESSION['phone'] = $phone;
            $_SESSION['role'] = $role;
        }

        // Redirect with success message
        header("Location: accounts.php?success=Account updated successfully");
        exit();

    } catch (PDOException $e) {
        // Rollback the transaction on error
        $conn->rollBack();
        
        // Redirect with error message
        header("Location: accounts.php?error=Update failed: " . $e->getMessage());
        exit();
    }
} else {
    // If not POST request, redirect to accounts page
    header("Location: accounts.php");
    exit();
}
?>
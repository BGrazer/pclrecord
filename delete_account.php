<?php
// Delete account processing file
session_start();
include('connector.php');

// Check if user is logged in
if (!isset($_SESSION['ad_id'])) {
    header("Location: pages-login.php");
    exit();
}

// Check if form was submitted with POST method
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the admin ID from the form
    $ad_id = $_POST['ad_id'];

    // Prevent users from deleting their own account
    if ($ad_id == $_SESSION['ad_id']) {
        $_SESSION['error'] = "You cannot delete your own account.";
        header("Location: accounts.php");
        exit();
    }

    try {
        // Prepare and execute DELETE statement
        $query = "DELETE FROM admin WHERE ad_id = :ad_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':ad_id', $ad_id);
        $stmt->execute();

        // Check if deletion was successful
        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Account deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete account.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }

    // Redirect back to accounts page
    header("Location: accounts.php");
    exit();
} else {
    // If not POST request, redirect to accounts page
    header("Location: accounts.php");
    exit();
}
?>
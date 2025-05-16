<?php
session_start();
include('connector.php');

if (!isset($_SESSION['ad_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad_id = $_SESSION['ad_id'];
    $currentPassword = $_POST['password'];
    $newPassword = $_POST['newpassword'];
    $renewPassword = $_POST['renewpassword'];

    try {
        // Kunin ang kasalukuyang password mula sa database
        $stmt = $conn->prepare("SELECT password FROM admin WHERE ad_id = :ad_id");
        $stmt->bindParam(':ad_id', $ad_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            header("Location: profile.php?error=User not found.");
            exit();
        }

        // I-verify kung tama ang kasalukuyang password
        if (!password_verify($currentPassword, $user['password'])) {
            header("Location: profile.php?error=Incorrect current password.");
            exit();
        }

        // Siguraduhin na magkatugma ang new password at re-enter password
        if ($newPassword !== $renewPassword) {
            header("Location: profile.php?error=New passwords do not match.");
            exit();
        }

        // I-hash ang bagong password para sa security
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // I-update ang password sa database
        $updateStmt = $conn->prepare("UPDATE admin SET password = :password WHERE ad_id = :ad_id");
        $updateStmt->bindParam(':password', $hashedPassword);
        $updateStmt->bindParam(':ad_id', $ad_id);
        $updateStmt->execute();

        header("Location: users-profile.php?success=Password changed successfully.");
        exit();
    } catch (PDOException $e) {
        echo "Error updating password: " . $e->getMessage();
    }
}
?>

<?php
session_start();
include('connector.php');

if (!isset($_SESSION['ad_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad_id = $_SESSION['ad_id'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $about = $_POST['about'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    // Directory ng uploads
    $upload_dir = "uploads/";

    // Auto-create ng folder kung wala pa
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Profile image upload
    if (!empty($_FILES['profileImage']['name'])) {
        $profileImage = $upload_dir . basename($_FILES["profileImage"]["name"]);

        if (move_uploaded_file($_FILES["profileImage"]["tmp_name"], $profileImage)) {
            $_SESSION['profile'] = $profileImage;
        } else {
            $profileImage = $_SESSION['profile']; // Gumamit ng luma kung failed
        }
    } else {
        $profileImage = $_SESSION['profile'];
    }

    try {
        // Update database gamit ang tamang PDO syntax
        $stmt = $conn->prepare("UPDATE admin 
                                SET fname = :fname, lname = :lname, about = :about, 
                                    phone = :phone, email = :email, profile = :profile 
                                WHERE ad_id = :ad_id");
        $stmt->execute([
            ':fname' => $fname,
            ':lname' => $lname,
            ':about' => $about,
            ':phone' => $phone,
            ':email' => $email,
            ':profile' => $profileImage,
            ':ad_id' => $ad_id
        ]);

        // Update session variables
        $_SESSION['fname'] = $fname;
        $_SESSION['lname'] = $lname;
        $_SESSION['about'] = $about;
        $_SESSION['phone'] = $phone;
        $_SESSION['email'] = $email;
        $_SESSION['profile'] = $profileImage;

        header("Location: users-profile.php?success=Profile updated successfully");
        exit();
    } catch (PDOException $e) {
        echo "Error updating profile: " . $e->getMessage();
    }
}
?>

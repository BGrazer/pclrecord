<?php
session_start();
include('connector.php'); 

if (!isset($_SESSION['ad_id'])) {
    header("Location: pages-login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad_id = $_SESSION['ad_id']; 
    $fname = $_POST['fname']; 
    $lname = $_POST['lname']; 
    $about = $_POST['about']; 
    $phone = $_POST['phone']; 
    $email = $_POST['email']; 
    $password = $_POST['password'];

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $upload_dir = "uploads/";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    if (!empty($_FILES['profileImage']['name'])) {
        $profileImage = $upload_dir . basename($_FILES["profileImage"]["name"]);

        if (move_uploaded_file($_FILES["profileImage"]["tmp_name"], $profileImage)) {
        } else {
            $profileImage = 'uploads/default.jpg';
        }
    } else {
        $profileImage = 'uploads/default.jpg';
    }


    try {
        $stmt = $conn->prepare("INSERT INTO admin (fname, lname, about, phone, email, profile, password) 
                                VALUES (:fname, :lname, :about, :phone, :email, :profile, :password)");
        $stmt->execute([
            ':fname' => $fname,
            ':lname' => $lname,
            ':about' => $about,
            ':phone' => $phone,
            ':email' => $email,
            ':profile' => $profileImage,
            ':password' => $hashedPassword
        ]);

        header("Location: accounts.php");
        exit();
    } catch (PDOException $e) {
        echo "Error creating account: " . $e->getMessage();
    }
}

?>

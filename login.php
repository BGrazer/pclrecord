<?php
session_start();
include('connector.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM admin WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin) {
        // Check kung naka-hash o hindi ang password
        if (password_verify($password, $admin['password']) || $password === $admin['password']) {
            $_SESSION['ad_id'] = $admin['ad_id'];
            $_SESSION['email'] = $admin['email'];
            $_SESSION['fname'] = $admin['fname'];
            $_SESSION['lname'] = $admin['lname'];
            $_SESSION['about'] = $admin['about'];
            $_SESSION['phone'] = $admin['phone'];
            $_SESSION['profile'] = $admin['profile'];

            header("Location: index.php");
            exit();
        }
    }

    // Redirect kung mali ang email o password
    header("Location: pages-login.php?error=Invalid email or password");
    exit();
}
?>

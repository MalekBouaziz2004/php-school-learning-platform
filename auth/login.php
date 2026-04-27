<?php
session_start();

$link = mysqli_connect("localhost", "root", "azerty", "hsgg_lernzentrum");

if (!$link)
{
    die("Verbindung fehlgeschlagen: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = mysqli_prepare($link, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    // Klartext-Passwort-Vergleich
    if ($user && $password === $user['password']) {
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['username'];
        $_SESSION['email'] = $user['email'];  // Email auch speichern!
        $_SESSION['role'] = $user['role'];

        header("Location: index.php");
        exit();
    } else {
        header("Location: login_view.php?error=invalid");
        exit();
    }
} else
{
    header("Location: login_view.php");
    exit();
}
?>


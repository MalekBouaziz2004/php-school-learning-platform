
<?php
// db_connect.php - Zentrale Datenbankverbindung

$db_host = "localhost";
$db_user = "root";
$db_pass = ".";
$db_name = "hsgg_lernzentrum";

$link = mysqli_connect($db_host, $db_user, $db_pass, $db_name);

if (!$link) {
    die("Datenbankverbindung fehlgeschlagen: " . mysqli_connect_error());
}

// UTF-8 Zeichensatz setzen
mysqli_set_charset($link, "utf8mb4");
?>

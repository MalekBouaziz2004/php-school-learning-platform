<?php
session_start();
require_once __DIR__ . '/db_connect.php';

// Sicherheit: Nur Lehrer/Admins
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['lehrer', 'admin'])) {
    die("Keine Berechtigung.");
}

if (isset($_GET['id']))
{
    $id = (int)$_GET['id'];

    // 1. Pfad der Datei holen, um sie physisch zu löschen
    /** @var $link */
    $stmt = $link->prepare("SELECT file_path FROM downloads WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if ($res)
    {

        $full_path = __DIR__ . $res['file_path'];

        // 2. Datei vom Server löschen
        if (file_exists($full_path))
        {
            unlink($full_path);
        }

        // 3. Eintrag aus der Datenbank löschen
        $del_stmt = $link->prepare("DELETE FROM downloads WHERE id = ?");
        $del_stmt->bind_param("i", $id);
        $del_stmt->execute();
    }
}

// Zurück zur vorherigen Seite
header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
exit();

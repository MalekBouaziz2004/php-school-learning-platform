
<?php
session_start();

//Datenbankverbindung ausgelagert
require_once __DIR__ . '/db_connect.php';

//Überprüfen ob der Nutzer Zugriff hat ( eingeloggt ist und die richtige Rolle hat)
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['lehrer', 'admin']))
{
    http_response_code(403);
    exit('Zugriff verweigert: Sie haben keine Berechtigung für diese Aktion.');
}

//Sicherstellen das über Button-Klick das Formular aufgerufen wurde
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['upload_submit']))
{
    header("Location: index.php"); // Zurück zum Formular, falls URL direkt aufgerufen wurde
    exit();
}

//Datei validierung
if (isset($_FILES['datei']) && $_FILES['datei']['error'] === UPLOAD_ERR_OK)
{
    /** @var mysqli $link wird in db_connect.php definiert, notwendig da php-storm sonst fehler angezeigt hat, originaler dateititel wird verwendet, falls nicht abgeändert in db*/
    $title = !empty($_POST['title']) ? mysqli_real_escape_string($link, $_POST['title']) : mysqli_real_escape_string($link, $_FILES['datei']['name']);
    $kat_id = (int)$_POST['kategorie_id'];
    $uebungId = !empty($_POST['uebung_id']) ? (int)$_POST['uebung_id'] : null; // NULL falls PDF/Header-Upload
    $section = !empty($_POST['section']) ? mysqli_real_escape_string($link, $_POST['section']) : 'uebung';
    $datei = $_FILES['datei'];


    //Dateigröße prüfen (es werden 5 MB verwendet), kann einfach verändert werden, indem die 5 zu dem gewünschten MB wert geändert wird
    $max_size = 5 * 1024 * 1024; // 5 MB in Bytes
    if ($datei['size'] > $max_size)
    {
        exit("Fehler: Die Datei ist zu groß (maximal 5 MB erlaubt).");
    }

    //Endung prüfen (falls keine pdf,jpg,jpeg,png,webp → fehler)
    $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
    $file_extension = strtolower(pathinfo($datei['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_extensions))
    {
        exit("Fehler: Nur PDF und Bilder (JPG, PNG, WEBP) sind erlaubt.");
    }
    //Überprüfen auf echtheit der dateien arten mithilfe finfo, überprüft den MIME-Typ der datei
    //zugelassene arten
    $allowed_mimes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp'
    ];
    //objekt erstellen
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    //MIME-typ ermitteln
    $mime = finfo_file($finfo, $datei['tmp_name']);
    //überprüfen ob vorhanden
    if (!in_array($mime, $allowed_mimes))
    {
        exit("Fehler: Der Dateiinhalt ist kein gültiges PDF oder Bild.");
    }

    //gültige datei jetzt vorhanden → abspeichern



    //ordnerstruktur ermitteln damit leicht findbar
    $sql = "SELECT f.name AS fach_name, k.name AS kat_name 
            FROM kategorien k 
            JOIN faecher f ON k.fach_id = f.id 
            WHERE k.id = ?";

    $stmt = $link->prepare($sql);
    $stmt->bind_param("i", $kat_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    if (!$res)
    {
        exit("Fehler: Kategorie oder Fach existiert nicht.");
    }

    // 1. Funktion definieren
    if (!function_exists('cleanFolderName'))
    {
        function cleanFolderName($name) {
            $name = trim($name);
            $name = str_replace([' ', 'ä', 'ö', 'ü', 'ß', "\r", "\n", "\t"], ['_', 'ae', 'oe', 'ue', 'ss', '', '', ''], $name);
            return preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
        }
    }

    // 2. Namen BEREINIGEN
    $clean_fach = cleanFolderName($res['fach_name']);
    $clean_kat  = cleanFolderName($res['kat_name']);

    // 3. Pfad erstellen mit sauberen Namen
    $relative_path = "/uploads/" . $clean_fach . "/" . $clean_kat . "/";
    $upload_dir = __DIR__ . $relative_path;

    // 4. Ordner physisch anlegen
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            exit("Fehler: Ordner konnte nicht erstellt werden. Pfad: " . $upload_dir);
        }
    }

    // 5. DATEINAMEN bereinigen
    $original_name = $_FILES['datei']['name'];
    $file_ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
    $base_name = pathinfo($original_name, PATHINFO_FILENAME);

    // Dateinamen säubern
    $clean_base = cleanFolderName($base_name);
    $clean_name = $clean_base . "." . $file_ext;

    $target_path = $upload_dir . $clean_name;

    // 6. Prüfen, ob DATEI (nicht Pfad) existiert
    if (file_exists($target_path)) {
        $clean_name = time() . "_" . $clean_name;
        $target_path = $upload_dir . $clean_name;
    }

    // 7. Datei verschieben
    if (move_uploaded_file($datei['tmp_name'], $target_path))
    {
        $db_save_path = $relative_path . $clean_name;

        // NEU: Erst prüfen, ob dieser Pfad für diese Kategorie schon existiert
        $check_sql = "SELECT id FROM downloads WHERE kategorie_id = ? AND file_path = ? AND section = ? AND (uebung_id = ? OR (uebung_id IS NULL AND ? IS NULL))";
        $stmt_check = $link->prepare($check_sql);
        $stmt_check->bind_param("issii", $kat_id, $db_save_path, $section,$uebungId, $uebungId);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check->num_rows > 0) {
            // Datei ist schon in der DB registriert -> nur weiterleiten
            $return_url = $_SERVER['HTTP_REFERER'] ?? "fach.php?id=" . $kat_id;
            header("Location: " . $return_url . (strpos($return_url, '?') ? '&' : '?') . "info=already_exists");
            exit();
        }

        // Datenbank-Eintrag erstellen (NUR EINMAL execute!)
        $insert_sql = "INSERT INTO downloads (kategorie_id, uebung_id, title, file_path,section) VALUES (?, ?, ?, ?,?)";
        $stmt_ins = $link->prepare($insert_sql);
        $stmt_ins->bind_param("iisss", $kat_id, $uebungId, $title, $db_save_path,$section);

        if ($stmt_ins->execute())
        {
            // Erfolg: Zurück zur vorherigen Seite
            $return_url = $_SERVER['HTTP_REFERER'] ?? "fach.php?id=" . $kat_id;
            $separator = (parse_url($return_url, PHP_URL_QUERY)) ? '&' : '?';
            header("Location: " . $return_url . $separator . "upload_success=1");
            exit();
        } else {
            echo "Fehler beim Speichern in der Datenbank: " . $link->error;
        }
    } else {
        echo "Fehler beim Verschieben der Datei auf dem Server.";
    }
} else
{
    // Dieser Teil gehört zum allerersten IF (UPLOAD_ERR_OK)
    echo "Upload-Fehler oder keine Datei ausgewählt. Fehlercode: " . ($_FILES['datei']['error'] ?? 'unbekannt');
}


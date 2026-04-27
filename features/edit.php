
<?php
session_start();

/* DB-Verbindung */
$link = mysqli_connect("localhost", "root", "azerty", "hsgg_lernzentrum");
if (!$link) {
    die("DB Fehler: " . mysqli_connect_error());
}

/* Rollenprüfung */
if (!isset($_SESSION['logged_in']) && !in_array($_SESSION['role'] ?? '', ['lehrer', 'admin'])) {
    http_response_code(403);
    exit('Keine Berechtigung');
}

/* Erlaubte Tabellen & Felder */
$allowed = [
        'faecher' => ['name', 'description'],
        'downloads' => ['title', 'description'],
        'suchbegriffe' => ['stichwort', 'beschreibung'],
        'erklaerungen' => ['content'],
        'kategorien' => ['name', 'description']
];

$table = $_GET['table'] ?? '';
$field = $_GET['field'] ?? '';
$id    = (int)($_GET['id'] ?? 0);

if (!isset($allowed[$table]) || !in_array($field, $allowed[$table], true)) {
    exit('Ungültige Anfrage');
}

/* Text laden */
$stmt = $link->prepare("SELECT `$field` FROM `$table` WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$text = $stmt->get_result()->fetch_assoc()[$field] ?? '';

/* Speichern */
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    $newText = $_POST['text'];
    $stmt = $link->prepare("UPDATE `$table` SET `$field` = ? WHERE id = ?");
    $stmt->bind_param("si", $newText, $id);
    if ($stmt->execute()) {
        //falls rückkehr -> dann rückkehr zur alten URL
        $returnUrl = $_SESSION['last_page'] ?? 'faecher.php';

        // Erfolgsmeldung an die URL an dran gehangen
        $connector = (strpos($returnUrl, '?') === false) ? '?' : '&';
        //rück kehr zur alten url
        header("Location: " . $returnUrl . $connector . "edit_success=1");
        exit;
    }
}

$pageTitle = 'Text bearbeiten';
include __DIR__ . '/header.php';
?>

<div class="layout-with-sidebar">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">
        <div class="kv-page">
            <div class="kv-header">
                <div>
                    <h1 class="kv-title">Text bearbeiten</h1>
                </div>

                <a class="kv-btn kv-btn-ghost" href="javascript:history.back()">Zurück</a>
            </div>

            <section class="kv-card">
                <form method="post" class="kv-form">
                    <div class="kv-field">
                        <textarea
                                id="edit_text"
                                name="text"
                                rows="10"
                                class="kv-textarea"
                                required
                        ><?= htmlspecialchars($text) ?></textarea>
                    </div>

                    <div class="kv-actions">
                        <button class="kv-btn kv-btn-primary" type="submit">Speichern</button>
                        <a class="kv-btn kv-btn-ghost" href="javascript:history.back()">Abbrechen</a>
                    </div>
                </form>
            </section>
        </div>
    </main>

</div>

<?php include __DIR__ . '/footer.php'; ?>

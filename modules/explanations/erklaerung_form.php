
<?php
$pageTitle = 'Erklärung hinzufügen/bearbeiten';
include '../../header.php';

$link = mysqli_connect("localhost", "root", "azerty", "hsgg_lernzentrum");
if (!$link) {
    die("DB Fehler: " . mysqli_connect_error());
}

// Only teachers can access
if (!isset($_SESSION['logged_in']) && !in_array($_SESSION['role'] ?? '', ['lehrer', 'admin'])) {
    http_response_code(403);
    exit('Keine Berechtigung');
}

$kategorieId = (int)($_GET['kategorie_id'] ?? 0);
if ($kategorieId <= 0) {
    exit('Ungültige Kategorie');
}

// Load category name
$stmt = $link->prepare("SELECT name FROM kategorien WHERE id = ?");
$stmt->bind_param("i", $kategorieId);
$stmt->execute();
$kategorie = $stmt->get_result()->fetch_assoc();

if (!$kategorie) {
    exit('Kategorie nicht gefunden');
}

// Load existing explanation if present
$stmt = $link->prepare("SELECT id, content FROM erklaerungen WHERE kategorie_id = ? LIMIT 1");
$stmt->bind_param("i", $kategorieId);
$stmt->execute();
$erk = $stmt->get_result()->fetch_assoc();

$erkId = $erk['id'] ?? null;
$content = $erk['content'] ?? '';

// Save form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newText = trim($_POST['content'] ?? '');

    if ($erkId) {
        // UPDATE
        $stmt = $link->prepare("UPDATE erklaerungen SET content = ? WHERE id = ?");
        $stmt->bind_param("si", $newText, $erkId);
        $stmt->execute();
    } else {
        // INSERT
        $stmt = $link->prepare("INSERT INTO erklaerungen (kategorie_id, content) VALUES (?, ?)");
        $stmt->bind_param("is", $kategorieId, $newText);
        $stmt->execute();
    }

    // Back to the explanation page
    header("Location: erklaerungen.php?id=" . $kategorieId);
    exit();
}
?>

<div class="layout-with-sidebar">
    <?php include __DIR__ . '/../../sidebar.php'; ?>

    <main class="main-content">
        <h1><?= htmlspecialchars($kategorie['name']) ?> – Erklärung</h1>

        <form method="post">
            <label for="content"><strong>Erklärungstext:</strong></label><br><br>
            <textarea id="content" name="content" rows="12" style="width:100%; font-size:16px; padding:10px;"><?= htmlspecialchars($content) ?></textarea>

            <br><br>
            <button type="submit" style="padding:10px 16px;">Speichern</button>
            <a href="erklaerungen.php?id=<?= $kategorieId ?>" style="margin-left:10px;">Abbrechen</a>
        </form>
    </main>
</div>

<?php include '../../footer.php'; ?>

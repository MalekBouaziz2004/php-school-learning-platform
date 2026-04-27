
<?php
session_start();

/* DB-Verbindung */
$link = mysqli_connect("localhost", "root", "azerty", "hsgg_lernzentrum");
if (!$link) {
    die("DB Fehler: " . mysqli_connect_error());
}

/* Rollenprüfung */
if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '', ['lehrer', 'admin']))
{
    http_response_code(403);
    exit('Keine Berechtigung');
}

$fachId = (int)($_GET['fach_id'] ?? 0);
if ($fachId <= 0)
{
    exit('Ungültiges Fach');
}
// Kategorie löschen
if (isset($_GET['delete_id']))
{
    $deleteId = (int)$_GET['delete_id'];

    $stmt = $link->prepare("DELETE FROM kategorien WHERE id = ?");
    $stmt->bind_param("i", $deleteId);
    $stmt->execute();

    header("Location: kategorie_verwalten.php?fach_id=$fachId");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $parentId = $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;

    $stmt = $link->prepare("
        INSERT INTO kategorien (fach_id, name, parent_id)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("isi", $fachId, $name, $parentId);
    $stmt->execute();

    header("Location: fach.php?id=$fachId");
    exit;
}

$pageTitle = 'Kategorie verwalten';
include __DIR__ . '/../header.php';
?>

<div class="layout-with-sidebar">
    <?php include __DIR__ . '/../sidebar.php'; ?>

    <main class="main-content">
        <div class="kv-page">
            <div class="kv-header">
                <div>
                    <h1 class="kv-title">Kategorie anlegen</h1>
                    <p class="kv-subtitle">Lege eine neue Kategorie und optional eine Hauptkategorie an.</p>
                </div>

                <a class="kv-btn kv-btn-ghost" href="javascript:history.back()">Zurück</a>
            </div>

            <section class="kv-card">
                <form method="post" class="kv-form">
                    <div class="kv-field">
                        <label class="kv-label" for="kv-name">Name</label>
                        <input class="kv-input" id="kv-name" type="text" name="name" required placeholder="z.B. Bruchrechnung">
                    </div>

                    <div class="kv-field">
                        <label class="kv-label" for="kv-parent">Übergeordnete Kategorie</label>
                        <select class="kv-select" id="kv-parent" name="parent_id">
                            <option value="">— Hauptkategorie —</option>

                            <?php
                            $stmt = $link->prepare(
                                    "SELECT id, name FROM kategorien
                             WHERE fach_id = ? AND parent_id IS NULL"
                            );
                            $stmt->bind_param("i", $fachId);
                            $stmt->execute();
                            $kategorien = $stmt->get_result();

                            while ($kategorie = $kategorien->fetch_assoc()):
                                ?>
                                <option value="<?= (int)$kategorie['id'] ?>">
                                    <?= htmlspecialchars($kategorie['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
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

<?php include __DIR__ . '/../footer.php'; ?>

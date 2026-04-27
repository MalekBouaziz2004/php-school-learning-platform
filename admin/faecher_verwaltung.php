
<?php
session_start();

$link = mysqli_connect("localhost", "root", "azerty", "hsgg_lernzentrum");
if (!$link) die("DB Fehler: " . mysqli_connect_error());

if (!isset($_SESSION['logged_in']) || ($_SESSION['role'] ?? '') !== 'admin')
{
    http_response_code(403);
    exit('Keine Berechtigung');
}
//fach hinzufügen
if (isset($_POST['add_fach']) && $_SESSION['role'] === 'admin') {
    $neuer_name = trim($_POST['fach_name']);
    if (!empty($neuer_name)) {
        $stmt = $link->prepare("INSERT INTO faecher (name) VALUES (?)");
        $stmt->bind_param("s", $neuer_name);
        $stmt->execute();
        header("Location: faecher.php");
        exit;
    }
}

// fach löschen, kateogiren werden über db on delete cascade aufgeräumt
if (isset($_GET['delete_fach']) && $_SESSION['role'] === 'admin') {
    $delete_id = (int)$_GET['delete_fach'];
    $stmt = $link->prepare("DELETE FROM faecher WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: faecher.php");
    exit;
}
$query = "SELECT * FROM faecher ORDER BY name ASC";
$result = mysqli_query($link, $query);


$pageTitle = 'Fächer verwalten';
include __DIR__ . '/header.php';
?>

<div class="layout-with-sidebar">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">
        <div style="margin-bottom: 20px;">
            <a href="/faecher.php" style="text-decoration: none; color: #236C93;">
                <i class="fas fa-arrow-left"></i> Zurück zur Ansicht
            </a>
        </div>

        <h1>Fächer-Administration</h1>

        <div style="background: #fff; border: 1px solid #ddd; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
            <h3 style="margin-top:0;">Neues Fach anlegen</h3>
            <form method="POST" style="display: flex; gap: 10px;">
                <input type="text" name="fach_name" required placeholder="Name des Fachs (z.B. Biologie)" style="padding: 10px; flex-grow: 1; border-radius: 4px; border: 1px solid #ccc;">
                <button type="submit" name="add_fach" style="background: #27ae60; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: bold;">
                    Speichern
                </button>
            </form>
        </div>

        <table style="width: 100%; border-collapse: collapse; background: white; border: 1px solid #eee;">
            <thead>
            <tr style="background: #f8f9fa; border-bottom: 2px solid #236C93;">
                <th style="padding: 15px; text-align: left;">Fachname</th>
                <th style="padding: 15px; text-align: right;">Verwaltung</th>
            </tr>
            </thead>
            <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 15px; font-weight: bold; font-size: 1.1em;">
                        <?= htmlspecialchars($row['name']) ?>
                    </td>
                    <td style="padding: 15px; text-align: right;">
                        <a href="edit.php?table=faecher&id=<?= $row['id'] ?>">

                            <a href="edit.php?table=faecher&field=name&id=<?= $row['id'] ?>" style="color: #f39c12; margin-right: 20px; text-decoration: none;">
                                <i class="fas fa-edit"></i> Umbenennen
                            </a>
                        <a href="?delete_fach=<?= $row['id'] ?>"
                           onclick="return confirm('Möchtest du das Fach <?= htmlspecialchars($row['name']) ?> wirklich löschen?')"
                           style="color: #e74c3c; text-decoration: none;" title="Fach löschen">
                            <i class="fas fa-trash"></i> Löschen
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </main>
</div>

<?php include 'footer.php'; ?>

<script src="script.js"></script>

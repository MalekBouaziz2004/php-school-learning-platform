<?php
// stichwort_suche.php
session_start();
//daten der datenbank ggf. anpassen
$db_host = "localhost";
$db_user = "root";
$db_pass = "azerty";
$db_name = "hsgg_lernzentrum";
//verbindung mit datenbank herstellen
$link = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
//falls nicht gelinkt
if (!$link)
{
    die("Datenbankverbindung fehlgeschlagen");
}

mysqli_set_charset($link, "utf8mb4");
//trimmen vom query falls notwendig
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
// falls leer zurück zu index.php
if (empty($query)) {
    header('Location: index.php');
    exit();
}

// Suche in Datenbank
$search_pattern = "%" . mysqli_real_escape_string($link, $query) . "%";

// Dynamische Abfrage über Fächer, Kategorien und Erklärungen
$sql = "
    (SELECT id, name AS titel, 'Fach' AS typ, CONCAT('/Faecher/fach.php?id=', id) AS ziel 
     FROM faecher 
     WHERE name LIKE ?)
    UNION
    (SELECT id, name AS titel, 'Kategorie' AS typ, CONCAT('/Faecher/Erklaerungen/erklaerungen.php?id=', id) AS ziel 
     FROM kategorien 
     WHERE name LIKE ? AND parent_id IS NOT NULL)
    UNION
    (SELECT e.kategorie_id, k.name AS titel, 'Inhalt' AS typ, CONCAT('/Faecher/Erklaerungen/erklaerungen.php?id=', e.kategorie_id) AS ziel 
     FROM erklaerungen e
     JOIN kategorien k ON e.kategorie_id = k.id
     WHERE e.content LIKE ?)
    
    LIMIT 10";

$stmt = $link->prepare($sql);
$stmt->bind_param("sss", $search_pattern, $search_pattern, $search_pattern);
$stmt->execute();
$results = $stmt->get_result();



// Gefunden? → Weiterleiten!
if ($results->num_rows === 1) {
    $row = $results->fetch_assoc();
    header('Location: ' . $row['ziel']);
    exit();
}

//Mehrere Treffer Ergebnisliste anzeigen
$pageTitle = 'Suchergebnisse';
include __DIR__ . '/header.php';
?>
<div class="layout-with-sidebar">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content" style="padding: 30px;">
        <h2>Suchergebnisse für "<?= htmlspecialchars($query) ?>"</h2>

        <?php if ($results->num_rows > 0): ?>
            <div class="search-results-list">
                <?php while ($row = $results->fetch_assoc()): ?>
                    <a href="<?= $row['ziel'] ?>" style="display: block; padding: 15px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 10px; text-decoration: none; color: inherit; transition: background 0.2s;">
                        <span style="font-size: 0.8em; color: #236C93; font-weight: bold; text-transform: uppercase;">[<?= $row['typ'] ?>]</span><br>
                        <strong style="font-size: 1.2em;"><?= htmlspecialchars($row['titel'] ?? 'Unbenanntes Thema') ?></strong>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div style="background: #fff3cd; color: #856404; padding: 20px; border-radius: 8px;">
                <i class="fas fa-exclamation-triangle"></i> Keine passenden Themen oder Erklärungen gefunden.
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/footer.php'; ?>


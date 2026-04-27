
<?php
include __DIR__ . '/../header.php';

$_SESSION['last_page'] = $_SERVER['REQUEST_URI'];

$pageTitle = 'Fach';
$link = mysqli_connect("localhost", "root", "azerty", "hsgg_lernzentrum");
$userName = $_SESSION['name'] ?? 'Benutzer';
$userEmail = $_SESSION['email'] ?? '';
$aktuelle_fach_id = (int)($_GET['id'] ?? 0);

if ($aktuelle_fach_id <= 0) {
    exit('Ungültige Fach-ID');
}

// Abfrage mit eindeutigem Variablennamen $aktuelle_fach_daten
$stmt = $link->prepare("SELECT name FROM faecher WHERE id = ?");
$stmt->bind_param("i", $aktuelle_fach_id);
$stmt->execute();
$aktuelle_fach_daten = $stmt->get_result()->fetch_assoc();

if (!$aktuelle_fach_daten) {
    exit('Fach nicht gefunden!');
}

// Kategorien laden (wir nutzen weiterhin $aktuelle_fach_id)
$stmt = $link->prepare("SELECT id, name FROM kategorien WHERE fach_id = ? AND parent_id IS NULL");
$stmt->bind_param("i", $aktuelle_fach_id);
$stmt->execute();
$kategorien_liste = $stmt->get_result();

//unterkategorien vorbereiten
$stmtUnter = $link->prepare
("
    SELECT id, name
    FROM kategorien
    WHERE parent_id = ?"
);


//Übungsblätter aus downloads holen
$stmtDownloads = $link->prepare("
    SELECT title, file_path
    FROM downloads
    WHERE kategorie_id = ?
    ORDER BY upload_date DESC
");


?>
    <!-- NEU: Wrapper für Sidebar + Inhalt -->
    <div class="layout-with-sidebar">

        <!-- Sidebar einbinden -->
        <?php include __DIR__ . '/../sidebar.php'; ?>

        <!-- Hauptinhalt -->
        <main class="main-content" style="padding: 20px;">
            <div style="border-bottom: 2px solid #236C93; margin-bottom: 30px; padding-bottom: 10px; display: flex; justify-content: space-between; align-items: center;">
                <h1 style="margin: 0; font-size: 28px; color: #333;">
                    <i class="fas fa-graduation-cap"></i> <?= htmlspecialchars($aktuelle_fach_daten['name']) ?>
                </h1>

                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'lehrer' || isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <a href="/Faecher/kategorie_verwalten.php?fach_id=<?= $aktuelle_fach_id ?>"
                       style="background: #236C93; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 14px;">
                        <i class="fas fa-plus"></i> Kategorien verwalten
                    </a>
                <?php endif; ?>
            </div>

            <div class="topics-container">
                <?php while ($haupt = $kategorien_liste->fetch_assoc()): ?>
                    <div class="main-topic-card" style="background: white; border: 1px solid #ddd; border-radius: 10px; margin-bottom: 25px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); overflow: hidden;">

                        <div style="background: #f8f9fa; padding: 15px 20px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                            <h2 style="margin: 0; font-size: 20px; color: #236C93;"><?= htmlspecialchars($haupt['name']) ?></h2>

                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'lehrer' || isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <div style="font-size: 12px; background: #fff; padding: 5px 10px; border-radius: 4px; border: 1px solid #ddd;">
                                    <a href="../edit.php?table=kategorien&field=name&id=<?= $haupt['id'] ?>" style="color: #f39c12; text-decoration: none; margin-right: 10px;"><i class="fas fa-edit"></i> Umbenennen</a>
                                    <a href="../Faecher/kategorie_verwalten.php?fach_id=<?= $aktuelle_fach_id ?>&delete_id=<?= $haupt['id'] ?>"
                                       onclick="return confirm('Wirklich löschen?')" style="color: #e74c3c; text-decoration: none;"><i class="fas fa-trash"></i> Löschen</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="padding: 20px;">
                            <?php
                            $stmtUnter->bind_param("i", $haupt['id']);
                            $stmtUnter->execute();
                            $unterKategorien = $stmtUnter->get_result();
                            ?>

                            <?php while ($sub = $unterKategorien->fetch_assoc()): ?>
                                <div style="margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid #f0f0f0;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                        <strong style="font-size: 17px; color: #444;"><?= htmlspecialchars($sub['name']) ?></strong>

                                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'lehrer' || isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                            <span style="font-size: 11px;">
                                        <a href="../edit.php?table=kategorien&field=name&id=<?= $sub['id'] ?>" style="color: #f39c12; text-decoration: none; margin-right: 8px;">Edit</a>
                                        <a href="../Faecher/kategorie_verwalten.php?fach_id=<?= $aktuelle_fach_id ?>&delete_id=<?= $sub['id'] ?>"
                                           onclick="return confirm('Wirklich löschen?')" style="color: #e74c3c; text-decoration: none;">Löschen</a>
                                    </span>
                                        <?php endif; ?>
                                    </div>

                                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                        <a href="/Faecher/Erklaerungen/erklaerungen.php?id=<?= $sub['id'] ?>"
                                           style="background: #eef4f7; color: #236C93; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; border: 1px solid #d0e2ec;">
                                            <i class="fas fa-book-open"></i> Erklärung anschauen
                                        </a>
                                        <a href="/Faecher/uebungen.php?kategorie_id=<?= $sub['id'] ?>"
                                           style="background: #eef7f2; color: #27ae60; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 14px; font-weight: 500; border: 1px solid #d0ecd8;">
                                            <i class="fas fa-pencil-alt"></i> Übung starten
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </main>
    </div>

<?php include __DIR__ . '/../footer.php'; ?>

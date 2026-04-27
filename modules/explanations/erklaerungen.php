<?php
session_start();

// Überprüfen ob Benutzer eingeloggt ist
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../login_view.php");
    exit();
}

$pageTitle = 'Erklärung';
include '../../header.php';

require_once __DIR__ . '/../../db_connect.php';
/** @var $link */
$allowedRoles = ['lehrer', 'admin'];
$canEdit =(isset($_SESSION['logged_in']) && in_array($_SESSION['role'] ?? '', $allowedRoles));

$kategorieId = (int)($_GET['kategorie_id'] ?? ($_GET['id'] ?? 0));
if ($kategorieId <= 0) {
    exit('Ungültige Kategorie');
}

// Fach-ID der Kategorie ermitteln (für "Zurück")
$stmtFach = $link->prepare("
    SELECT fach_id
    FROM kategorien
    WHERE id = ?
");
$stmtFach->bind_param("i", $kategorieId);
$stmtFach->execute();
$fachRow = $stmtFach->get_result()->fetch_assoc();

$fachId = (int)($fachRow['fach_id'] ?? 0);

$stmt = $link->prepare("
    SELECT k.name, e.id AS erklaerung_id, e.content
    FROM kategorien k
    LEFT JOIN erklaerungen e ON e.kategorie_id = k.id
    WHERE k.id = ?
");
$stmt->bind_param("i", $kategorieId);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data)
{
    exit('Keine Erklärung gefunden');
}

// Verwandte Erklärungen laden (Gleicher Parent)
$stmtParent = $link->prepare("SELECT parent_id FROM kategorien WHERE id = ?");
$stmtParent->bind_param("i", $kategorieId);
$stmtParent->execute();
$currentCat = $stmtParent->get_result()->fetch_assoc();
$parentId = $currentCat['parent_id'] ?? 0;

$verwandteErklaerungen = [];
if ($parentId > 0) {
    $stmtRel = $link->prepare("
        SELECT id, name 
        FROM kategorien 
        WHERE parent_id = ? AND id != ? 
        ORDER BY name ASC
    ");
    $stmtRel->bind_param("ii", $parentId, $kategorieId);
    $stmtRel->execute();
    $verwandteErklaerungen = $stmtRel->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>


<div class="layout-with-sidebar">

    <!-- Sidebar einbinden -->
    <?php include __DIR__ . '/../../sidebar.php'; ?>

    <main class="main-content">
        <div class="ex-page">
            <div class="ex-header">
                <div>
                    <h1 class="ex-title"><?= htmlspecialchars($data['name']) ?></h1>
                </div>

                <?php if ($canEdit): ?>
                    <div class="admin-toolbar" style="display: flex; gap: 10px; align-items: center;">

                        <?php if (!empty($data['erklaerung_id'])): ?>
                            <?php
                            $editUrl = '../../edit.php?' . http_build_query([
                                            'table'  => 'erklaerungen',
                                            'field'  => 'content',
                                            'id'     => (int)$data['erklaerung_id'],
                                            'return' => $_SERVER['REQUEST_URI'],
                                    ]);
                            ?>

                            <a class="ex-btn ex-btn-ghost"
                               href="<?= htmlspecialchars($editUrl, ENT_QUOTES) ?>">
                                Text bearbeiten
                            </a>
                        <?php endif; ?>

                        <form action="../../upload.php" method="POST" enctype="multipart/form-data" class="upload-inline-form">
                            <input type="hidden" name="kategorie_id" value="<?= $kategorieId ?>">
                            <input type="hidden" name="section" value="erklaerung">

                            <input type="hidden" name="title" value="">

                            <label class="ex-btn ex-btn-ghost" style="cursor: pointer; display: inline-block;">
                                <span>Datei wählen</span>
                                <input type="file" name="datei" accept=".pdf, .jpg, .jpeg, .png, .webp" required
                                       style="display: none;" onchange="this.previousElementSibling.innerText = '✔️ ' + this.files[0].name">
                            </label>

                            <button type="submit" name="upload_submit" class="ex-btn ex-btn-primary">
                                Hochladen
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>

            <section class="ex-card">
                <div class="ex-text">
                    <?php if (!empty($data['content'])): ?>
                        <div class="ex-content-body">
                            <?= nl2br(htmlspecialchars($data['content'])) ?>
                        </div>

                        <?php
                        $stmt_imgs = $link->prepare("SELECT file_path, title FROM downloads WHERE kategorie_id = ? AND section = 'erklaerung'");
                        $stmt_imgs->bind_param("i", $kategorieId);
                        $stmt_imgs->execute();
                        $res_imgs = $stmt_imgs->get_result();

                        while ($img = $res_imgs->fetch_assoc()):
                            $ext = strtolower(pathinfo($img['file_path'], PATHINFO_EXTENSION));
                            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])): ?>
                                <div class="ex-inline-image">
                                    <img src="<?= htmlspecialchars($img['file_path']) ?>" alt="Grafik">
                                    <?php if(!empty($img['title'])): ?>
                                        <p class="img-caption"><?= htmlspecialchars($img['title']) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endif;
                        endwhile; ?>

                    <?php else: ?>
                        <p><strong>Keine Erklärung vorhanden.</strong></p>
                        <?php if ($canEdit): ?>
                            <a class="ex-btn ex-btn-primary" href="erklaerung_form.php?kategorie_id=<?= $kategorieId ?>">
                                + Erklärung hinzufügen
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="ex-footer" style="display: flex; flex-direction: column; gap: 20px; margin-top: 20px;">
                    <div class="ex-footer-main">
                        <a class="ex-btn ex-btn-primary" href="/Faecher/uebungen.php?kategorie_id=<?= $kategorieId ?>">
                            ➜ Zu den Übungen
                        </a>
                    </div>

                    <?php if (!empty($verwandteErklaerungen)): ?>
                        <div class="ex-footer-related">
                            <p style="font-size: 0.9rem; color: #000; margin-bottom: 8px; font-weight: bold;">Weitere Erklärungen in diesem Fach:</p>
                            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <?php foreach ($verwandteErklaerungen as $vRel): ?>
                                    <a class="ex-btn ex-btn-primary"
                                       href="/Faecher/Erklaerungen/erklaerungen.php?id=<?= (int)$vRel['id'] ?>"
                                       style="font-size: 0.9rem; filter: brightness(0.95);"> ➜ <?= htmlspecialchars($vRel['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="ex-footer-nav" style="border-top: 1px solid #eee; padding-top: 15px;">
                        <a class="ex-btn ex-btn-ghost" href="javascript:history.back()">Zurück</a>
                    </div>
                </div>
            </section>



            <section class="ex-materials">
                <?php
                $sql_files = "SELECT id ,title, file_path FROM downloads WHERE kategorie_id = ? and uebung_id IS NULL AND section ='erklaerung'";
                $stmt_files = $link->prepare($sql_files);
                $stmt_files->bind_param("i", $kategorieId);
                $stmt_files->execute();
                $result_files = $stmt_files->get_result();

                if ($result_files->num_rows > 0): ?>
                    <h2 class="ex-materials-title">Materialien & Medien</h2>
                    <div class="materials-grid">
                        <?php while ($file = $result_files->fetch_assoc()):
                            $ext = strtolower(pathinfo($file['file_path'], PATHINFO_EXTENSION));
                            $displayTitle = !empty($file['title']) ? $file['title'] : basename($file['file_path']);
                            $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'webp']);
                            ?>
                            <div class="material-card <?= $isImage ? 'is-image-card' : '' ?>">
                                <a href="<?= htmlspecialchars($file['file_path']) ?>" target="_blank" class="material-link">
                                    <div class="material-preview">
                                        <?php if ($isImage): ?>
                                            <img src="<?= htmlspecialchars($file['file_path']) ?>" alt="<?= htmlspecialchars($displayTitle) ?>">
                                        <?php else: ?>
                                            <div class="pdf-placeholder">
                                                <span class="file-icon">📄</span>
                                                <span class="file-badge">PDF</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="material-info">
                                        <span class="material-title"><?= htmlspecialchars($displayTitle) ?></span>
                                    </div>
                                </a>

                                <?php if ($canEdit): ?>
                                    <div class="admin-controls" style="padding: 10px; border-top: 1px solid #eee; text-align: center;">
                                        <a href="../../delete_file.php?id=<?= $file['id'] ?>"
                                           onclick="return confirm('Datei wirklich löschen?')"
                                           style="color: #8b0000; font-size: 12px; font-weight: bold; text-decoration: none; display: block;">
                                            🗑️ Löschen
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>
</div>





<?php include '../../footer.php'; ?>


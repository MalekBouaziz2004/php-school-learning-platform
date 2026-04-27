<?php
session_start();

$link = mysqli_connect("localhost", "root", "azerty", "hsgg_lernzentrum");
if (!$link) die("DB Fehler: " . mysqli_connect_error());

$kategorieId = (int)($_GET['kategorie_id'] ?? 0);
if ($kategorieId <= 0) exit('Ungültige Kategorie');

$isTeacher = isset($_SESSION['logged_in']) && in_array($_SESSION['role'] ?? '',[ 'lehrer','admin']);

// Persist inputs + feedback per Kategorie in Session
$_SESSION['uebungen_state'] ??= [];
$_SESSION['uebungen_state'][$kategorieId] ??= ['prefill' => [], 'feedback' => []];

// Load saved state into your runtime arrays
$prefill  = $_SESSION['uebungen_state'][$kategorieId]['prefill'];
$feedback = $_SESSION['uebungen_state'][$kategorieId]['feedback'];

// Kategoriename laden
$stmtKat = $link->prepare("
    SELECT name
    FROM kategorien
    WHERE id = ?
");
$stmtKat->bind_param("i", $kategorieId);
$stmtKat->execute();
$kategorie = $stmtKat->get_result()->fetch_assoc();

if (!$kategorie) {
    exit('Kategorie nicht gefunden');
}
/** Hilfsfunktionen */
function post($k, $default = null) { return $_POST[$k] ?? $default; }

/* Lehrer: Löschen */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'delete' && $isTeacher) {
    $deleteId = (int)post('aufgabe_id', 0);

    $stmt = $link->prepare("DELETE FROM aufgaben WHERE id = ? AND kategorie_id = ?");
    $stmt->bind_param("ii", $deleteId, $kategorieId);
    $stmt->execute();

    header("Location: /Faecher/uebungen.php?kategorie_id=$kategorieId");
    exit;
}

/* Prüfen / Lösung anzeigen */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array(post('action'), ['check', 'solve'], true)) {
    $aufgabeId = (int)post('aufgabe_id', 0);

    $stmt = $link->prepare("SELECT id, typ FROM aufgaben WHERE id = ? AND kategorie_id = ?");
    $stmt->bind_param("ii", $aufgabeId, $kategorieId);
    $stmt->execute();
    $aufgabe = $stmt->get_result()->fetch_assoc();

    if ($aufgabe) {
        $typ = $aufgabe['typ'];

        if ($typ === 'variable' || $typ === 'blank') {
            $stmt = $link->prepare("SELECT id, name, korrekter_wert FROM aufgaben_felder WHERE aufgabe_id = ? ORDER BY id ASC");
            $stmt->bind_param("i", $aufgabeId);
            $stmt->execute();
            $felder = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $allCorrect = true;
            $prefill[$aufgabeId]['felder'] = [];

            foreach ($felder as $f) {
                $fid = (int)$f['id'];
                $correct = trim((string)$f['korrekter_wert']);
                $given = trim((string)post("feld_$fid", ''));

                if (post('action') === 'solve') {
                    $prefill[$aufgabeId]['felder'][$fid] = $correct; // richtige Werte eintragen
                } else {
                    $prefill[$aufgabeId]['felder'][$fid] = $given;   // Eingaben behalten
                    if ($given !== $correct) $allCorrect = false;
                }
            }

            if (post('action') === 'solve') {
                $feedback[$aufgabeId] = "✅ Lösung eingesetzt.";
            } else {
                $feedback[$aufgabeId] = $allCorrect ? "✅ Richtig!" : "❌ Falsch";
            }
            // SAVE state to session so other tasks don't reset
            $_SESSION['uebungen_state'][$kategorieId]['prefill']  = $prefill;
            $_SESSION['uebungen_state'][$kategorieId]['feedback'] = $feedback;

        } elseif ($typ === 'multiple_choice') {
            $stmt = $link->prepare("SELECT id, ist_korrekt FROM aufgaben_optionen WHERE aufgabe_id = ? ORDER BY id ASC");
            $stmt->bind_param("i", $aufgabeId);
            $stmt->execute();
            $opts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $selected = (int)post('option', 0);
            $correctId = 0;
            foreach ($opts as $o) {
                if ((int)$o['ist_korrekt'] === 1) $correctId = (int)$o['id'];
            }

            if (post('action') === 'solve') {
                $prefill[$aufgabeId]['option'] = $correctId;
                $feedback[$aufgabeId] = "✅ Richtig!";
            } else {
                $prefill[$aufgabeId]['option'] = $selected;
                $feedback[$aufgabeId] = ($selected === $correctId) ? "✅ Richtig!" : "❌ Falsch";
            }
            // SAVE state to session so other tasks don't reset
            $_SESSION['uebungen_state'][$kategorieId]['prefill']  = $prefill;
            $_SESSION['uebungen_state'][$kategorieId]['feedback'] = $feedback;
        }
    }
}

/* Aufgaben laden */
$stmt = $link->prepare("
    SELECT id, frage, typ
    FROM aufgaben
    WHERE kategorie_id = ?
    ORDER BY id ASC
");
$stmt->bind_param("i", $kategorieId);
$stmt->execute();
$aufgaben = $stmt->get_result();

$pageTitle = 'Übungen';
include __DIR__ . '/../header.php';
?>

<div class="layout-with-sidebar">
    <?php include __DIR__ . '/../sidebar.php'; ?>

    <main class="main-content">
        <div class="ex-page">

            <div class="ex-header">
                <div>
                    <h1 class="ex-title">Übungen zu <?= htmlspecialchars($kategorie['name']) ?></h1>
                </div>
            </div>

            <div class="ex-list">
                <?php while ($a = $aufgaben->fetch_assoc()): ?>
                    <?php $aid = (int)$a['id']; ?>

                    <article class="ex-item">
                        <div class="ex-item-head">
                            <p class="ex-question"><strong><?= htmlspecialchars($a['frage']) ?></strong></p>

                            <?php if ($isTeacher): ?>
                                <div class="ex-item-actions">
                                    <a class="ex-link" href="/Faecher/aufgabe_form.php?id=<?= $aid ?>">Bearbeiten</a>

                                    <form method="post"
                                          action="/Faecher/uebungen.php?kategorie_id=<?= $kategorieId ?>"
                                          onsubmit="return confirm('Übung wirklich löschen?');"
                                          class="ex-delete-inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="aufgabe_id" value="<?= $aid ?>">
                                        <button type="submit" class="ex-link ex-link-danger">Löschen</button>
                                    </form>
                                </div>
                            <?php endif; ?>

                        </div>

                        <?php if ($a['typ'] === 'variable' || $a['typ'] === 'blank'): ?>

                            <?php
                            $stmtF = $link->prepare("SELECT id, name, korrekter_wert FROM aufgaben_felder WHERE aufgabe_id = ? ORDER BY id ASC");
                            $stmtF->bind_param("i", $aid);
                            $stmtF->execute();
                            $felder = $stmtF->get_result()->fetch_all(MYSQLI_ASSOC);
                            ?>

                            <form class="ex-form" method="post" action="/Faecher/uebungen.php?kategorie_id=<?= $kategorieId ?>">
                                <input type="hidden" name="aufgabe_id" value="<?= $aid ?>">

                                <div class="ex-fields-inline">
                                    <?php foreach ($felder as $f): ?>
                                        <?php
                                        $fid = (int)$f['id'];
                                        $value = $prefill[$aid]['felder'][$fid] ?? '';
                                        ?>
                                        <div class="ex-eq">
                                            <span class="ex-eq-name"><?= htmlspecialchars($f['name']) ?></span>
                                            <span class="ex-eq-sign">=</span>
                                            <input class="ex-input"
                                                   type="text"
                                                   name="feld_<?= $fid ?>"
                                                   value="<?= htmlspecialchars($value) ?>">
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div class="ex-actions">
                                    <button class="ex-btn ex-btn-primary" type="submit" name="action" value="check">Prüfen</button>
                                    <button class="ex-btn ex-btn-ghost" type="submit" name="action" value="solve">Lösung anzeigen</button>
                                </div>

                                <?php if (isset($feedback[$aid])): ?>
                                    <?php $ok = (strpos($feedback[$aid], '✅') === 0); ?>
                                    <div class="ex-feedback <?= $ok ? 'ex-feedback-ok' : 'ex-feedback-bad' ?>">
                                        <?= htmlspecialchars($feedback[$aid]) ?>
                                    </div>
                                <?php endif; ?>

                            </form>

                        <?php elseif ($a['typ'] === 'multiple_choice'): ?>

                            <?php
                            $stmtO = $link->prepare("SELECT id, text FROM aufgaben_optionen WHERE aufgabe_id = ? ORDER BY id ASC");
                            $stmtO->bind_param("i", $aid);
                            $stmtO->execute();
                            $optionen = $stmtO->get_result()->fetch_all(MYSQLI_ASSOC);
                            $selectedOpt = (int)($prefill[$aid]['option'] ?? 0);
                            ?>

                            <form class="ex-form" method="post" action="/Faecher/uebungen.php?kategorie_id=<?= $kategorieId ?>">
                                <input type="hidden" name="aufgabe_id" value="<?= $aid ?>">

                                <div class="ex-options">
                                    <?php foreach ($optionen as $opt): ?>
                                        <?php $oid = (int)$opt['id']; ?>
                                        <label class="ex-option">
                                            <input type="radio" name="option" value="<?= $oid ?>" <?= ($oid === $selectedOpt) ? 'checked' : '' ?>>
                                            <span><?= htmlspecialchars($opt['text']) ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>

                                <div class="ex-actions">
                                    <button class="ex-btn ex-btn-primary" type="submit" name="action" value="check">Prüfen</button>
                                    <button class="ex-btn ex-btn-ghost" type="submit" name="action" value="solve">Lösung anzeigen</button>
                                </div>

                                <?php if (isset($feedback[$aid])): ?>
                                    <?php $ok = (strpos($feedback[$aid], '✅') === 0); ?>
                                    <div class="ex-feedback <?= $ok ? 'ex-feedback-ok' : 'ex-feedback-bad' ?>">
                                        <?= htmlspecialchars($feedback[$aid]) ?>
                                    </div>
                                <?php endif; ?>

                            </form>

                        <?php endif; ?>

                    </article>

                <?php endwhile; ?>
            </div>

            <section class="ex-card">
                <?php if ($isTeacher): ?>
                    <div style="margin-bottom: 14px;">
                        <a class="ex-btn ex-btn-primary"
                           href="/Faecher/aufgabe_form.php?kategorie_id=<?= $kategorieId ?>">
                            + Neue Übung anlegen
                        </a>
                    </div>
                <?php endif; ?>

                <div class="ex-footer">
                    <a class="ex-btn ex-btn-primary"
                       href="/Faecher/Erklaerungen/erklaerungen.php?id=<?= $kategorieId ?>">
                        ➜ Zu den Erklärungen
                    </a>

                    <a class="ex-btn ex-btn-ghost" href="javascript:history.back()">
                        Zurück
                    </a>
                </div>
            </section>

        </div>
    </main>
</div>

<?php include __DIR__ . '/../footer.php'; ?>


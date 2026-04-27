
<?php
session_start();
include __DIR__ . '/../header.php';
require_once __DIR__ . '/../db_connect.php';
/** @var $link */
if (!$link) die("DB Fehler: " . mysqli_connect_error());

if (!isset($_SESSION['logged_in']) || !in_array($_SESSION['role'] ?? '',[ 'lehrer','admin']))
{
    http_response_code(403);
    exit('Keine Berechtigung');
}

$kategorieId = (int)($_GET['kategorie_id'] ?? 0);
$aufgabeId = (int)($_GET['id'] ?? 0);
if ($kategorieId <= 0) exit('Ungültige Kategorie');

$allowedTypes = ['variable', 'blank', 'multiple_choice'];
$errors = [];
$isEdit = ($aufgabeId > 0);

// DATEN LADEN (Falls Bearbeiten-Modus)
if ($isEdit && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $stmt = $link->prepare("SELECT frage, typ FROM aufgaben WHERE id = ?");
    $stmt->bind_param("i", $aufgabeId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if (!$res) exit('Aufgabe nicht gefunden');

    $_POST['frage'] = $res['frage'];
    $typ = $res['typ'];

    if ($typ === 'variable' || $typ === 'blank') {
        $stmtF = $link->prepare("SELECT name, korrekter_wert FROM aufgaben_felder WHERE aufgabe_id = ? ORDER BY id ASC");
        $stmtF->bind_param("i", $aufgabeId);
        $stmtF->execute();
        $felder = $stmtF->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach($felder as $f) {
            $_POST['var_names'][] = $f['name'];
            $_POST['var_values'][] = $f['korrekter_wert'];
        }
    } elseif ($typ === 'multiple_choice') {
        $stmtO = $link->prepare("SELECT text, ist_korrekt FROM aufgaben_optionen WHERE aufgabe_id = ? ORDER BY id ASC");
        $stmtO->bind_param("i", $aufgabeId);
        $stmtO->execute();
        $opts = $stmtO->get_result()->fetch_all(MYSQLI_ASSOC);
        $keys = ['a', 'b', 'c', 'd'];
        foreach($opts as $i => $o) {
            if (isset($keys[$i])) {
                $_POST['opt_'.$keys[$i]] = $o['text'];
                if ($o['ist_korrekt']) $_POST['correct'] = $keys[$i];
            }
        }
    }
} else {
    $typ = $_POST['typ'] ?? ($_GET['typ'] ?? 'variable');
}
if (!in_array($typ, $allowedTypes, true)) $typ = 'variable';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $frage = trim($_POST['frage'] ?? '');
    if ($frage === '') $errors[] = 'Bitte eine Aufgabenstellung eingeben.';

    if (empty($errors)) {
        mysqli_begin_transaction($link);
        try {
            if ($isEdit) {
                // UPDATE
                $stmt = $link->prepare("UPDATE aufgaben SET frage = ?, typ = ? WHERE id = ?");
                $stmt->bind_param("ssi", $frage, $typ, $aufgabeId);
                $stmt->execute();

                // Alte Felder/Optionen löschen (einfachster Weg für Update)
                $link->query("DELETE FROM aufgaben_felder WHERE aufgabe_id = $aufgabeId");
                $link->query("DELETE FROM aufgaben_optionen WHERE aufgabe_id = $aufgabeId");
            } else {
                // INSERT
                $userId = (int)$_SESSION['user_id'];
                $stmt = $link->prepare("INSERT INTO aufgaben (kategorie_id, frage, typ, created_by) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $kategorieId, $frage, $typ, $userId);
                $stmt->execute();
                $aufgabeId = $stmt->insert_id;
            }

            if ($typ === 'variable' || $typ === 'blank') {
                $names = $_POST['var_names'] ?? [];
                $values = $_POST['var_values'] ?? [];
                $stmt2 = $link->prepare("INSERT INTO aufgaben_felder (aufgabe_id, name, korrekter_wert) VALUES (?, ?, ?)");
                for ($i = 0; $i < count($names); $i++) {
                    $vName = trim($names[$i]); $vValue = trim($values[$i]);
                    if ($vName !== '') {
                        $stmt2->bind_param("iss", $aufgabeId, $vName, $vValue);
                        $stmt2->execute();
                    }
                }
            } elseif ($typ === 'multiple_choice') {
                $correct = $_POST['correct'] ?? '';
                $options = ['a' => 'opt_a', 'b' => 'opt_b', 'c' => 'opt_c', 'd' => 'opt_d'];
                $stmt3 = $link->prepare("INSERT INTO aufgaben_optionen (aufgabe_id, text, ist_korrekt) VALUES (?, ?, ?)");
                foreach ($options as $key => $postKey) {
                    $text = trim($_POST[$postKey] ?? '');
                    if ($text !== '') {
                        $isCor = ($key === $correct ? 1 : 0);
                        $stmt3->bind_param("isi", $aufgabeId, $text, $isCor);
                        $stmt3->execute();
                    }
                }
            }

            mysqli_commit($link);
            header("Location: /Faecher/uebungen.php?kategorie_id=$kategorieId&saved=1");
            exit;
        } catch (Throwable $e) {
            mysqli_rollback($link);
            $errors[] = "Speichern fehlgeschlagen: " . $e->getMessage();
        }
    }
}
?>

    <div class="layout-with-sidebar">
        <?php include __DIR__ . '/../sidebar.php'; ?>
        <main class="main-content">
            <div class="kv-page">
                <div class="kv-header">
                    <h1 class="kv-title"><?= $isEdit ? 'Übung bearbeiten' : 'Übung erstellen' ?></h1>
                    <a class="kv-btn kv-btn-ghost" href="/Faecher/uebungen.php?kategorie_id=<?= $kategorieId ?>">Zurück</a>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="kv-alert"><ul><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>

                <section class="kv-card">
                    <form method="post" class="kv-form">
                        <div class="kv-field">
                            <label class="kv-label">Aufgabentyp</label>
                            <select class="kv-select" name="typ" onchange="this.form.submit()" <?= $isEdit ? 'disabled' : '' ?>>
                                <option value="variable" <?= $typ==='variable'?'selected':'' ?>>Variable (X)</option>
                                <option value="blank" <?= $typ==='blank'?'selected':'' ?>>Lücke (___)</option>
                                <option value="multiple_choice" <?= $typ==='multiple_choice'?'selected':'' ?>>Multiple Choice</option>
                            </select>
                            <?php if($isEdit): ?><input type="hidden" name="typ" value="<?= $typ ?>"><?php endif; ?>
                        </div>

                        <div class="kv-field">
                            <label class="kv-label">Aufgabenstellung</label>
                            <textarea class="kv-textarea" name="frage" rows="4" required><?= htmlspecialchars($_POST['frage'] ?? '') ?></textarea>
                        </div>

                        <?php if ($typ === 'variable' || $typ === 'blank'): ?>
                            <div class="kv-divider"></div>
                            <div id="var-container">
                                <?php
                                $vNames = $_POST['var_names'] ?? [''];
                                $vValues = $_POST['var_values'] ?? [''];
                                foreach($vNames as $i => $name): ?>
                                    <div class="kv-grid-2" style="margin-bottom: 10px;">
                                        <div class="kv-field">
                                            <input class="kv-input" type="text" name="var_names[]" value="<?= htmlspecialchars($name) ?>" required placeholder="Name">
                                        </div>
                                        <div class="kv-field">
                                            <input class="kv-input" type="text" name="var_values[]" value="<?= htmlspecialchars($vValues[$i] ?? '') ?>" required placeholder="Lösung">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" class="kv-btn kv-btn-ghost" onclick="addVarField()">+ Weiteres Feld</button>

                            <script>
                                function addVarField() {
                                    const container = document.getElementById('var-container');
                                    const div = document.createElement('div');
                                    div.className = 'kv-grid-2';
                                    div.style.marginBottom = '10px';
                                    div.innerHTML = `<div class="kv-field"><input class="kv-input" type="text" name="var_names[]" required placeholder="Name"></div>
                                                 <div class="kv-field"><input class="kv-input" type="text" name="var_values[]" required placeholder="Lösung"></div>`;
                                    container.appendChild(div);
                                }
                            </script>
                        <?php elseif ($typ === 'multiple_choice'): ?>
                            <div class="kv-divider"></div>
                            <div class="kv-grid-2">
                                <?php foreach(['a','b','c','d'] as $k): ?>
                                    <div class="kv-field">
                                        <label>Option <?= strtoupper($k) ?></label>
                                        <input class="kv-input" type="text" name="opt_<?= $k ?>" value="<?= htmlspecialchars($_POST['opt_'.$k] ?? '') ?>" <?= in_array($k, ['a','b']) ? 'required' : '' ?>>
                                        <label><input type="radio" name="correct" value="<?= $k ?>" <?= ($_POST['correct'] ?? '') === $k ? 'checked' : '' ?> required> Richtig</label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="kv-actions">
                            <button class="kv-btn kv-btn-primary" type="submit" name="save">Speichern</button>
                        </div>
                    </form>
                </section>
            </div>
        </main>
    </div>
<?php include __DIR__ . '/../footer.php'; ?>

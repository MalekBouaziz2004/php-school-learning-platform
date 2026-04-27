
<?php
session_start();
$pageTitle = 'Kontakt';
include 'header.php';

require_once __DIR__ . '/db_connect.php';
/** @var $link */
if (!$link) die("DB Fehler: " . mysqli_connect_error());

$success_message = '';
$error_message = '';

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $betreff = trim($_POST['betreff']);
    $nachricht = trim($_POST['nachricht']);

    // Validierung
    if (empty($name) || empty($email) || empty($betreff) || empty($nachricht)) {
        $error_message = 'Bitte füllen Sie alle Felder aus!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Ungültige E-Mail-Adresse!';
    } else {
        // Nachricht in Datenbank speichern
        $stmt = mysqli_prepare($link, "INSERT INTO kontakt_nachrichten (name, email, betreff, nachricht, datum) VALUES (?, ?, ?, ?, NOW())");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $betreff, $nachricht);

            if (mysqli_stmt_execute($stmt)) {
                $success_message = 'Ihre Nachricht wurde erfolgreich gesendet! Wir werden uns in Kürze bei Ihnen melden.';
                // Formular zurücksetzen
                $_POST = array();
            } else {
                $error_message = 'Fehler beim Senden der Nachricht: ' . mysqli_error($link);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error_message = 'Fehler beim Vorbereiten der Anfrage: ' . mysqli_error($link);
        }
    }
}
?>

<style>
    .contact-page {
        max-width: 900px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .page-header {
        margin-bottom: 30px;
        text-align: center;
    }

    .page-header h1 {
        font-size: 32px;
        color: #111;
        margin: 0 0 10px 0;
    }

    .page-header p {
        color: #666;
        font-size: 16px;
        margin: 0;
    }

    .contact-content {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-top: 30px;
    }

    .contact-info {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .contact-info h2 {
        font-size: 24px;
        color: #236C93;
        margin: 0 0 20px 0;
    }

    .info-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 20px;
    }

    .info-icon {
        width: 40px;
        height: 40px;
        background: #236C93;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 18px;
        flex-shrink: 0;
    }

    .info-text {
        flex: 1;
    }

    .info-label {
        font-weight: bold;
        color: #111;
        margin-bottom: 5px;
        font-size: 16px;
    }

    .info-value {
        color: #666;
        line-height: 1.6;
        font-size: 14px;
    }

    .contact-form-section {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .contact-form-section h2 {
        font-size: 22px;
        color: #236C93;
        margin: 0 0 20px 0;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-weight: bold;
        color: #111;
        margin-bottom: 8px;
        font-size: 16px;
    }

    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        box-sizing: border-box;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        font-family: inherit;
    }

    .form-group textarea {
        min-height: 150px;
        resize: vertical;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #236C93;
        box-shadow: 0 0 0 3px rgba(35, 108, 147, 0.1);
    }

    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: bold;
        cursor: pointer;
        border: none;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-primary {
        background: #236C93;
        color: white;
        width: 100%;
    }

    .btn-primary:hover {
        background: #1d5877;
    }

    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
    }

    .alert-success {
        background: #e8f5e9;
        border: 1px solid #c8e6c9;
        color: #1b5e20;
    }

    .alert-error {
        background: #ffebee;
        border: 1px solid #ffcdd2;
        color: #c62828;
    }

    @media (max-width: 768px) {
        .contact-content {
            grid-template-columns: 1fr;
        }

        .page-header h1 {
            font-size: 24px;
        }
    }
</style>

<div class="layout-with-sidebar">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">
        <div class="contact-page">
            <div class="page-header">
                <h1>Kontaktieren Sie uns</h1>
                <p>Haben Sie Fragen oder Anregungen? Wir helfen Ihnen gerne weiter!</p>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="contact-content">
                <!-- Kontaktinformationen -->
                <div class="contact-info">
                    <h2>Kontaktinformationen</h2>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-text">
                            <div class="info-label">Adresse</div>
                            <div class="info-value">
                                HSGG Lernzentrum<br>
                                Eupnerstr 70<br>

                            </div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="info-text">
                            <div class="info-label">Telefon</div>
                            <div class="info-value">+49 123 456789</div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info-text">
                            <div class="info-label">E-Mail</div>
                            <div class="info-value">kontakt@hsgg-lernzentrum.de</div>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="info-text">
                            <div class="info-label">Öffnungszeiten</div>
                            <div class="info-value">
                                Montag - Freitag: 08:00 - 18:00<br>
                                Samstag: 09:00 - 14:00<br>
                                Sonntag: Geschlossen
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kontaktformular -->
                <div class="contact-form-section">
                    <h2>Nachricht senden</h2>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="name">Name *</label>
                            <input type="text" id="name" name="name" required
                                   placeholder="Ihr vollständiger Name"
                                   value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">E-Mail-Adresse *</label>
                            <input type="email" id="email" name="email" required
                                   placeholder="ihre.email@beispiel.de"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="betreff">Betreff *</label>
                            <input type="text" id="betreff" name="betreff" required
                                   placeholder="Worum geht es?"
                                   value="<?php echo isset($_POST['betreff']) ? htmlspecialchars($_POST['betreff']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="nachricht">Nachricht *</label>
                            <textarea id="nachricht" name="nachricht" required
                                      placeholder="Schreiben Sie uns Ihre Nachricht..."><?php echo isset($_POST['nachricht']) ? htmlspecialchars($_POST['nachricht']) : ''; ?></textarea>
                        </div>

                        <button type="submit" name="send_message" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Nachricht senden
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>

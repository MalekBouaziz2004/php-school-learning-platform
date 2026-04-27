<?php
session_start();
$pageTitle = 'Neues Konto erstellen';
include 'header.php';

require_once __DIR__ . '/db_connect.php';
/** @var $link */
if (!$link) die("DB Fehler: " . mysqli_connect_error());
// Prüfen ob Benutzer Admin ist
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}



$success_message = '';
$error_message = '';

// Formular verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_user'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    // Validierung
    if (empty($username) || empty($email) || empty($password)) {
        $error_message = 'Bitte alle Felder ausfüllen!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Ungültige E-Mail-Adresse!';
    } else {
        // Prüfen ob Email bereits existiert
        $check_stmt = mysqli_prepare($link, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            $error_message = 'Diese E-Mail-Adresse ist bereits registriert!';
        } else {
            // Benutzer erstellen
            $insert_stmt = mysqli_prepare($link, "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($insert_stmt, "ssss", $username, $email, $password, $role);

            if (mysqli_stmt_execute($insert_stmt)) {
                $success_message = "Konto für $username ($role) erfolgreich erstellt!";
            } else {
                $error_message = 'Fehler beim Erstellen des Kontos: ' . mysqli_error($link);
            }
            mysqli_stmt_close($insert_stmt);
        }
        mysqli_stmt_close($check_stmt);
    }
}
?>

<style>
    .create-account-page {
        max-width: 800px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .page-header {
        margin-bottom: 30px;
    }

    .page-header h1 {
        font-size: 28px;
        color: #111;
        margin: 0 0 10px 0;
    }

    .page-header p {
        color: #666;
        margin: 0;
    }

    /* Role Selection Cards */
    .role-selection {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 40px;
    }

    .role-card {
        background: white;
        border: 3px solid #e5e7eb;
        border-radius: 12px;
        padding: 30px 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .role-card:hover {
        border-color: #236C93;
        box-shadow: 0 4px 12px rgba(35, 108, 147, 0.15);
        transform: translateY(-2px);
    }

    .role-card.selected {
        border-color: #236C93;
        background-color: #f0f8ff;
    }

    .role-card input[type="radio"] {
        display: none;
    }

    .role-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 15px;
        background: linear-gradient(135deg, #236C93 0%, #1d5877 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 36px;
    }

    .role-card.selected .role-icon {
        background: linear-gradient(135deg, #1d5877 0%, #236C93 100%);
    }

    .role-title {
        font-size: 20px;
        font-weight: bold;
        color: #111;
        margin: 10px 0 5px;
    }

    .role-description {
        font-size: 14px;
        color: #666;
        margin: 0;
    }

    /* Form Section */
    .form-section {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .form-section h2 {
        font-size: 20px;
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
        font-size: 14px;
    }

    .form-group input {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        box-sizing: border-box;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .form-group input:focus {
        outline: none;
        border-color: #236C93;
        box-shadow: 0 0 0 3px rgba(35, 108, 147, 0.1);
    }

    /* Buttons */
    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 30px;
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
    }

    .btn-primary:hover {
        background: #1d5877;
    }

    .btn-primary:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .btn-secondary {
        background: #f5f5f5;
        color: #111;
        border: 1px solid #d1d5db;
    }

    .btn-secondary:hover {
        background: #e5e5e5;
    }

    /* Messages */
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

    /* Confirmation Modal */
    .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .modal.active {
        display: flex;
    }

    .modal-content {
        background: white;
        border-radius: 12px;
        padding: 30px;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
    }

    .modal-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
    }

    .modal-icon {
        width: 50px;
        height: 50px;
        background: #236C93;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 24px;
        color: #111;
    }

    .modal-body {
        margin-bottom: 25px;
        color: #666;
        line-height: 1.6;
    }

    .modal-details {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
    }

    .modal-details-item {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .modal-details-item:last-child {
        border-bottom: none;
    }

    .modal-details-label {
        font-weight: bold;
        color: #666;
    }

    .modal-details-value {
        color: #111;
    }

    .modal-actions {
        display: flex;
        gap: 12px;
        justify-content: flex-end;
    }

    @media (max-width: 768px) {
        .role-selection {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-direction: column;
        }

        .btn {
            width: 100%;
        }
    }
</style>

<div class="layout-with-sidebar">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">
        <div class="create-account-page">
            <div class="page-header">
                <h1>Neues Konto erstellen</h1>
                <p>Erstellen Sie ein neues Benutzerkonto für Schüler, Lehrer oder Administratoren</p>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    ✓ <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    ✗ <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form id="createAccountForm" method="POST">
                <!-- Role Selection -->
                <div class="role-selection">
                    <label class="role-card" for="role-schueler">
                        <input type="radio" name="role" id="role-schueler" value="schueler" required>
                        <div class="role-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="role-title">Schüler</div>
                        <div class="role-description">Zugriff auf Lernmaterialien und Übungen</div>
                    </label>

                    <label class="role-card" for="role-lehrer">
                        <input type="radio" name="role" id="role-lehrer" value="lehrer">
                        <div class="role-icon">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="role-title">Lehrer</div>
                        <div class="role-description">Inhalte erstellen und verwalten</div>
                    </label>

                    <label class="role-card" for="role-admin">
                        <input type="radio" name="role" id="role-admin" value="admin">
                        <div class="role-icon">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="role-title">Admin</div>
                        <div class="role-description">Volle Systemverwaltung</div>
                    </label>
                </div>

                <!-- Form Fields -->
                <div class="form-section">
                    <h2>Kontoinformationen</h2>

                    <div class="form-group">
                        <label for="username">Name *</label>
                        <input type="text" id="username" name="username" required
                               placeholder="z.B. Max Mustermann">
                    </div>

                    <div class="form-group">
                        <label for="email">E-Mail-Adresse *</label>
                        <input type="email" id="email" name="email" required
                               placeholder="z.B. max.mustermann@example.com">
                    </div>

                    <div class="form-group">
                        <label for="password">Passwort *</label>
                        <input type="password" id="password" name="password" required
                               placeholder="Mindestens 6 Zeichen">
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-primary" id="showConfirmBtn" disabled>
                            Konto erstellen
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            Abbrechen
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </main>
</div>

<!-- Confirmation Modal -->
<div class="modal" id="confirmModal">
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-icon">
                <i class="fas fa-question-circle"></i>
            </div>
            <h3>Konto erstellen bestätigen</h3>
        </div>
        <div class="modal-body">
            <p>Sind Sie sicher, dass Sie dieses Konto erstellen möchten?</p>
            <div class="modal-details">
                <div class="modal-details-item">
                    <span class="modal-details-label">Name:</span>
                    <span class="modal-details-value" id="confirm-name"></span>
                </div>
                <div class="modal-details-item">
                    <span class="modal-details-label">E-Mail:</span>
                    <span class="modal-details-value" id="confirm-email"></span>
                </div>
                <div class="modal-details-item">
                    <span class="modal-details-label">Rolle:</span>
                    <span class="modal-details-value" id="confirm-role"></span>
                </div>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-primary" id="confirmCreateBtn">
                Ja, erstellen
            </button>
            <button type="button" class="btn btn-secondary" id="cancelModalBtn">
                Abbrechen
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createAccountForm');
    const showConfirmBtn = document.getElementById('showConfirmBtn');
    const confirmModal = document.getElementById('confirmModal');
    const confirmCreateBtn = document.getElementById('confirmCreateBtn');
    const cancelModalBtn = document.getElementById('cancelModalBtn');

    const roleCards = document.querySelectorAll('.role-card');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    // Role card selection
    roleCards.forEach(card => {
        card.addEventListener('click', function() {
            roleCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            validateForm();
        });
    });

    // Form validation
    function validateForm() {
        const role = document.querySelector('input[name="role"]:checked');
        const username = usernameInput.value.trim();
        const email = emailInput.value.trim();
        const password = passwordInput.value.trim();

        if (role && username && email && password) {
            showConfirmBtn.disabled = false;
        } else {
            showConfirmBtn.disabled = true;
        }
    }

    usernameInput.addEventListener('input', validateForm);
    emailInput.addEventListener('input', validateForm);
    passwordInput.addEventListener('input', validateForm);

    // Show confirmation modal
    showConfirmBtn.addEventListener('click', function() {
        const role = document.querySelector('input[name="role"]:checked');
        const roleLabel = role.closest('.role-card').querySelector('.role-title').textContent;

        document.getElementById('confirm-name').textContent = usernameInput.value;
        document.getElementById('confirm-email').textContent = emailInput.value;
        document.getElementById('confirm-role').textContent = roleLabel;

        confirmModal.classList.add('active');
    });

    // Confirm creation
    confirmCreateBtn.addEventListener('click', function() {
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'create_user';
        hiddenInput.value = '1';
        form.appendChild(hiddenInput);

        form.submit();
    });

    // Cancel modal
    cancelModalBtn.addEventListener('click', function() {
        confirmModal.classList.remove('active');
    });

    // Close modal on outside click
    confirmModal.addEventListener('click', function(e) {
        if (e.target === confirmModal) {
            confirmModal.classList.remove('active');
        }
    });
});
</script>

<?php include 'footer.php'; ?>

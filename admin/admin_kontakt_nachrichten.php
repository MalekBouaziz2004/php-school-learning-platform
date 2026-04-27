
<?php
session_start();

// Überprüfen ob Benutzer eingeloggt ist
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login_view.php");
    exit();
}

// Überprüfen ob Benutzer Admin ist
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Zugriff verweigert! Sie haben keine Berechtigung, diese Seite zu sehen.");
}

$pageTitle = 'Kontaktnachrichten - Admin';
include 'header.php';

// Datenbankverbindung
require_once __DIR__ . '/db_connect.php';
/** @var $link */
if (!$link) die("DB Fehler: " . mysqli_connect_error());

// Nachrichten als gelesen markieren (optional)
if (isset($_POST['mark_read']) && isset($_POST['nachricht_id'])) {
    $nachricht_id = intval($_POST['nachricht_id']);
    $stmt = mysqli_prepare($link, "UPDATE kontakt_nachrichten SET gelesen = 1 WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $nachricht_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Nachricht löschen (optional)
if (isset($_POST['delete']) && isset($_POST['nachricht_id'])) {
    $nachricht_id = intval($_POST['nachricht_id']);
    $stmt = mysqli_prepare($link, "DELETE FROM kontakt_nachrichten WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $nachricht_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Filter für gelesene/ungelesene Nachrichten
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where_clause = '';
if ($filter === 'unread') {
    $where_clause = 'WHERE gelesen = 0';
} elseif ($filter === 'read') {
    $where_clause = 'WHERE gelesen = 1';
}

// Alle Kontaktnachrichten abrufen
$query = "SELECT * FROM kontakt_nachrichten $where_clause ORDER BY datum DESC";
$result = mysqli_query($link, $query);
?>

<style>
    .admin-page {
        max-width: 1200px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .page-header {
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .page-header h1 {
        font-size: 32px;
        color: #236C93;
        margin: 0;
    }

    .filter-buttons {
        display: flex;
        gap: 10px;
    }

    .filter-btn {
        padding: 8px 16px;
        border: 1px solid #236C93;
        background: white;
        color: #236C93;
        border-radius: 6px;
        cursor: pointer;
        text-decoration: none;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .filter-btn.active {
        background: #236C93;
        color: white;
    }

    .filter-btn:hover {
        background: #1d5877;
        color: white;
    }

    .stats-bar {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        display: flex;
        gap: 30px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .stat-item {
        flex: 1;
        text-align: center;
        padding: 10px;
    }

    .stat-number {
        font-size: 32px;
        font-weight: bold;
        color: #236C93;
        margin-bottom: 5px;
    }

    .stat-label {
        color: #666;
        font-size: 14px;
    }

    .messages-container {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .message-item {
        border-bottom: 1px solid #e5e7eb;
        padding: 20px;
        transition: background 0.2s ease;
    }

    .message-item:last-child {
        border-bottom: none;
    }

    .message-item.unread {
        background: #f0f9ff;
    }

    .message-item:hover {
        background: #f9fafb;
    }

    .message-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }

    .message-info {
        flex: 1;
    }

    .message-sender {
        font-weight: bold;
        color: #333;
        font-size: 16px;
        margin-bottom: 5px;
    }

    .message-email {
        color: #236C93;
        font-size: 14px;
        margin-bottom: 5px;
    }

    .message-date {
        color: #666;
        font-size: 13px;
    }

    .message-status {
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
    }

    .status-unread {
        background: #fef3c7;
        color: #92400e;
    }

    .status-read {
        background: #d1fae5;
        color: #065f46;
    }

    .message-subject {
        font-size: 16px;
        font-weight: bold;
        color: #236C93;
        margin-bottom: 10px;
    }

    .message-body {
        color: #444;
        line-height: 1.6;
        margin-bottom: 15px;
        padding: 15px;
        background: #f9fafb;
        border-radius: 8px;
        white-space: pre-wrap;
    }

    .message-actions {
        display: flex;
        gap: 10px;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: bold;
        cursor: pointer;
        border: none;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-block;
    }

    .btn-success {
        background: #10b981;
        color: white;
    }

    .btn-success:hover {
        background: #059669;
    }

    .btn-danger {
        background: #ef4444;
        color: white;
    }

    .btn-danger:hover {
        background: #dc2626;
    }

    .btn-secondary {
        background: #6b7280;
        color: white;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .no-messages {
        padding: 60px 20px;
        text-align: center;
        color: #666;
    }

    .no-messages i {
        font-size: 64px;
        color: #d1d5db;
        margin-bottom: 20px;
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .stats-bar {
            flex-direction: column;
            gap: 15px;
        }

        .message-actions {
            flex-direction: column;
        }
    }
</style>

<div class="layout-with-sidebar">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="main-content">
        <div class="admin-page">
            <div class="page-header">
                <h1><i class="fas fa-envelope"></i> Kontaktnachrichten</h1>
                <div class="filter-buttons">
                    <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                        Alle
                    </a>
                    <a href="?filter=unread" class="filter-btn <?php echo $filter === 'unread' ? 'active' : ''; ?>">
                        Ungelesen
                    </a>
                    <a href="?filter=read" class="filter-btn <?php echo $filter === 'read' ? 'active' : ''; ?>">
                        Gelesen
                    </a>
                </div>
            </div>

            <?php
            // Statistiken berechnen
            $total_query = mysqli_query($link, "SELECT COUNT(*) as total FROM kontakt_nachrichten");
            $total_count = mysqli_fetch_assoc($total_query)['total'];

            $unread_query = mysqli_query($link, "SELECT COUNT(*) as total FROM kontakt_nachrichten WHERE gelesen = 0");
            $unread_count = mysqli_fetch_assoc($unread_query)['total'];

            $read_count = $total_count - $unread_count;
            ?>

            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-number"><?php echo $total_count; ?></div>
                    <div class="stat-label">Gesamt</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $unread_count; ?></div>
                    <div class="stat-label">Ungelesen</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $read_count; ?></div>
                    <div class="stat-label">Gelesen</div>
                </div>
            </div>

            <div class="messages-container">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($nachricht = mysqli_fetch_assoc($result)): ?>
                        <div class="message-item <?php echo $nachricht['gelesen'] == 0 ? 'unread' : ''; ?>">
                            <div class="message-header">
                                <div class="message-info">
                                    <div class="message-sender">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($nachricht['name']); ?>
                                    </div>
                                    <div class="message-email">
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($nachricht['email']); ?>
                                    </div>
                                    <div class="message-date">
                                        <i class="fas fa-calendar"></i> <?php echo date('d.m.Y H:i', strtotime($nachricht['datum'])); ?> Uhr
                                    </div>
                                </div>
                                <span class="message-status <?php echo $nachricht['gelesen'] == 0 ? 'status-unread' : 'status-read'; ?>">
                                    <?php echo $nachricht['gelesen'] == 0 ? 'Ungelesen' : 'Gelesen'; ?>
                                </span>
                            </div>

                            <div class="message-subject">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($nachricht['betreff']); ?>
                            </div>

                            <div class="message-body">
                                <?php echo htmlspecialchars($nachricht['nachricht']); ?>
                            </div>

                            <div class="message-actions">
                                <?php if ($nachricht['gelesen'] == 0): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="nachricht_id" value="<?php echo $nachricht['id']; ?>">
                                        <button type="submit" name="mark_read" class="btn btn-success">
                                            <i class="fas fa-check"></i> Als gelesen markieren
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <a href="mailto:<?php echo htmlspecialchars($nachricht['email']); ?>" class="btn btn-secondary">
                                    <i class="fas fa-reply"></i> Antworten
                                </a>

                                <form method="POST" style="display: inline;" onsubmit="return confirm('Möchten Sie diese Nachricht wirklich löschen?');">
                                    <input type="hidden" name="nachricht_id" value="<?php echo $nachricht['id']; ?>">
                                    <button type="submit" name="delete" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Löschen
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-messages">
                        <i class="fas fa-inbox"></i>
                        <p>Keine Nachrichten gefunden.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php
mysqli_close($link);
include 'footer.php';
?>


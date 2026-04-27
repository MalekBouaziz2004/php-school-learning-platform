<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Session-Infos für Header vorbereiten (Login-Status + Nutzername/Email)
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = isset($_SESSION['name']) ? $_SESSION['name'] : 'Benutzer';
$userEmail = isset($_SESSION['email']) ? $_SESSION['email'] : '';
$pageTitle = $pageTitle ?? 'Startseite';
// ⭐ GEÄNDERT! Korrekter Pfad für localhost/main/Homepage/
$rootPath = '/';
//$rootPath = '/main/Homepage/';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <!-- Globale Basis-Styles + Sidebar-Layout -->
    <link rel="stylesheet" href="<?php echo $rootPath; ?>basestyle.css">
    <link rel="stylesheet" href="<?php echo $rootPath; ?>sidebar_style.css">
    <!-- Icon-Font für alle Font-Awesome-Icons im Layout -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
<header class="top-bar">
    <div class="top-bar-content">
        <!-- Globale Stichwort-Suche: leitet auf Suchseite mit GET-Parameter 'q' -->
        <div class="top-bar-left" style="display: flex; align-items: center; gap: 20px;">
            <form action="<?php echo $rootPath; ?>stichwort_suche.php" method="GET" class="search-bar">
                <input type="text" name="q" placeholder="Suchen..." required>
                <button type="submit"><i class="fas fa-search"></i></button>
            </form>
            <a href="<?php echo $rootPath; ?>index.php" class="contact-button" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Startseite</a>
        </div>
        <div class="logo">
            <!-- Klick auf Logo führt immer zur Startseite -->
            <a href="<?php echo $rootPath; ?>index.php">
                <img src="<?php echo $rootPath; ?>logo.PNG" alt="Lernzentrum Logo">
            </a>
        </div>
        <div class="top-bar-buttons">
            <!-- Platzhalter-Buttons für Kontakt/Guide im oberen Bereich -->
            <a href="<?php echo $rootPath; ?>kontakt.php" class="contact-button" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">Kontakt</a>
            <a href="<?php
            // Direkte Pfadausgabe je nach Rolle
            if (($_SESSION['role'] ?? '') === 'admin')
            {
                echo $rootPath . "Lernwebseite_des_HSGG-Admin.pdf";
            } elseif (($_SESSION['role'] ?? '') === 'lehrer')
            {
                echo $rootPath . "Lernwebseite_des_HSGG-Lehrer.pdf";
            } else
            {
                echo $rootPath . "Lernwebseite_des_HSGG-Schueler.pdf";
            }?>" download class="guide-button">Guide</a>
            <?php if ($isLoggedIn && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="<?php echo $rootPath; ?>neue_kontakt.php" class="contact-button" style="text-decoration: none;">
                    <i class="fas fa-user-plus"></i> Neues Konto
                </a>
                <a href="<?php echo $rootPath; ?>admin_kontakt_nachrichten.php" class="contact-button" style="text-decoration: none;">

                    <i class="fas fa-envelope"></i> Nachrichten
                </a>
            <?php endif; ?>
            <?php if ($isLoggedIn): ?>
                <!-- Profilbereich für eingeloggte Nutzer (Dropdown mit Abmelden-Link) -->
                <div class="profile-section" id="profileSection">
                    <button class="profile-button" id="profileButton">
                        <div class="profile-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <span class="profile-name"><?php echo htmlspecialchars($userName); ?></span>
                        <i class="fas fa-chevron-down profile-arrow"></i>
                    </button>
                    <div class="profile-dropdown" id="profileDropdown">
                        <div class="profile-info">
                            <div class="profile-info-icon">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="profile-info-details">
                                <div class="profile-info-name"><?php echo htmlspecialchars($userName); ?></div>
                                <div class="profile-info-email"><?php echo htmlspecialchars($userEmail); ?></div>
                            </div>
                        </div>
                        <!-- Abmelden-Link im Dropdown -->
                        <div class="profile-menu">
                            <a href="<?php echo $rootPath; ?>logout.php" class="profile-menu-item logout">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Abmelden</span>
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Wenn nicht eingeloggt: Button führt zur Login-Seite -->
                <a href="<?php echo $rootPath; ?>Login.php" class="login-button">Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>




<?php

$pageTitle = 'Login – HSGG Lernzentrum';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="basestyle.css">
</head>
<body>

<main class="login-main">
    <div class="login-card">

        <img src="logo_gross.PNG" alt="HSGG Logo" class="login-logo">

        <p class="login-subtitle">Eine interaktive Lernwebseite</p>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid'): ?>
            <div class="error-message">E-Mail oder Passwort falsch!</div>
        <?php endif; ?>

        <?php if (isset($_GET['msg'])): ?>
            <div class="info-message">
                <?php
                if ($_GET['msg'] === 'logout') {
                    echo 'Sie wurden erfolgreich abgemeldet.';
                }
                ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="post" class="login-form">

            <div class="login-form-group">
                <label for="email">E-Mail:</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Ihre E-Mail"
                    required
                >
            </div>

            <div class="login-form-group">
                <label for="password">Passwort:</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Ihr Passwort"
                    required
                >
            </div>

            <button type="submit" class="login-submit">
                Anmelden
            </button>

        </form>
    </div>
</main>

<?php include 'footer.php'; ?>

</body>
</html>

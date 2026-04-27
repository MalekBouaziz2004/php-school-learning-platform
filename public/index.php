
<?php
session_start();
$pageTitle = 'Startseite';
include 'header.php';



$link = mysqli_connect("localhost", "root", "azerty", "hsgg_lernzentrum");
if (!$link) die("DB Fehler: " . mysqli_connect_error());


//abfrage faecher dynamisch
$query = "SELECT id, name, folder_path FROM faecher ORDER BY id ASC";
$result = mysqli_query($link, $query);
?>

<!-- Main Content -->
<!-- NEU: Wrapper für Sidebar + Inhalt -->
<div class="layout-with-sidebar">

    <!-- Sidebar einbinden -->
    <?php include __DIR__ . '/sidebar.php'; ?>
<main class="main-content">

    <?php if (isset($_GET['search_error'])): ?>
        <div style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin: 20px auto; max-width: 600px; text-align: center; font-weight: bold;">
            ❌ "<?php echo htmlspecialchars($_GET['search_error']); ?>" nicht gefunden!
        </div>
    <?php endif; ?>
    <h2>Klicke auf das gewünschte Fach:</h2>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <a href="faecher_verwaltung.php" class="btn-primary" style="background: #236C93; color: white; padding: 10px 15px; border-radius: 5px; text-decoration: none;">
            <i class="fas fa-cog"></i>Fächer verwalten
        </a>
    <br>
    <br>
    <?php endif; ?>
    <section class="subject-grid">
        <?php

        if ($result && mysqli_num_rows($result) > 0)
        {
            // Schleife durch alle gefundenen Fächer
            while ($row = mysqli_fetch_assoc($result))
            {
                $id = $row['id'];
                $name = htmlspecialchars($row['name']);

                //Fach mit richtiger id als link
                $url = "Faecher/fach.php?id=" . $id;

                ?>
                <a href="<?php echo $url; ?>" class="subject-card">
                    <span><?php echo $name; ?></span>
                </a>
                <?php
            }
        } else {
            echo "<p>Es gibt keine Fächer.</p>";
        }
        ?>
    </section>
</main>
</div>
<?php include 'footer.php'; ?>

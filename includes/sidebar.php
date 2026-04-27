<?php

// Falls noch keine DB-Verbindung existiert, hier herstellen
if (!isset($link)) {
    $link = mysqli_connect("localhost", "root", "azerty", "hsgg_lernzentrum");
}

// detect current page name
$page = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// ---------- Fach ----------
$current_fach_id = (int)($_GET['fach_id'] ?? 0);

// old links: fach.php?id=...
if ($current_fach_id === 0 && $page === 'fach.php' && isset($_GET['id'])) {
    $current_fach_id = (int)$_GET['id'];
}

// ---------- Kategorie ----------
$current_cat_id = (int)($_GET['cat'] ?? $_GET['kategorie_id'] ?? 0);

// Backward compatibility: old erklärungen links use ?id=<kategorie_id>
if ($current_cat_id === 0 && $page === 'erklaerungen.php' && isset($_GET['id'])) {
    $current_cat_id = (int)$_GET['id'];
}

// derive fach from category (for erklärungen / übungen)
if ($current_fach_id === 0 && $current_cat_id > 0) {
    $stmt = mysqli_prepare($link, "SELECT fach_id FROM kategorien WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $current_cat_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $current_fach_id = (int)$row['fach_id'];
    }
    mysqli_stmt_close($stmt);
}

// Alle Fächer für die Sidebar laden
$faecher_query  = "SELECT * FROM faecher ORDER BY id ASC";
$faecher_result = mysqli_query($link, $faecher_query);

?>

<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <?php while ($fach = mysqli_fetch_assoc($faecher_result)):
            $fach_id       = $fach['id'];
            $is_active_fach = ($current_fach_id == $fach_id); // Flag für aktuelles Fach
            ?>
            <div class="sidebar-category">
                <!-- Kopfzeile eines Faches: klickbar zum Ein-/Ausklappen der Themen -->
                <div class="sidebar-category-header <?php echo $is_active_fach ? 'active' : ''; ?>"
                     data-category="fach-<?php echo $fach_id; ?>">
                    <span><?php echo htmlspecialchars($fach['name']); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>

                <!-- Container für alle Kategorien des Faches -->
                <ul class="sidebar-subtopics <?php echo $is_active_fach ? 'expanded' : ''; ?>" id="fach-<?php echo $fach_id; ?>-subtopics">

                    <?php
                    // Oberkategorien (Bereichsüberschriften) des Faches
                    $parent_cat_query  = "SELECT * FROM kategorien WHERE fach_id = $fach_id AND parent_id IS NULL ORDER BY id ASC";
                    $parent_cat_result = mysqli_query($link, $parent_cat_query);

                    while ($parent_cat = mysqli_fetch_assoc($parent_cat_result)):
                        $p_id = $parent_cat['id'];
                        ?>
                        <li class="sidebar-section-title">
                            <?php echo htmlspecialchars($parent_cat['name']); ?>
                        </li>

                        <?php
                        // Unterkategorien zur jeweiligen Oberkategorie
                        $child_cat_query  = "SELECT * FROM kategorien WHERE parent_id = $p_id ORDER BY id ASC";
                        $child_cat_result = mysqli_query($link, $child_cat_query);

                        while ($child_cat = mysqli_fetch_assoc($child_cat_result)):
                            $c_id        = $child_cat['id'];
                            $is_active_c = ($current_cat_id == $c_id); // aktive Unterkategorie
                            ?>
                            <li class="sidebar-topic-block <?php echo $is_active_c ? 'active' : ''; ?>">
                            <a class="topic-link" href="/Faecher/Erklaerungen/erklaerungen.php?kategorie_id=<?php echo $c_id; ?>">
                                <?php echo htmlspecialchars($child_cat['name']); ?>
                                </a>
                            </li>
                        <?php endwhile; ?>
                    <?php endwhile; ?>
                </ul>
            </div>
        <?php endwhile; ?>
    </nav>
</aside>

<!-- Button zum Ein-/Ausklappen der Sidebar (Desktop + Mobile) -->
<button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</button>

<!-- Halbtransparenter Hintergrund für mobile Sidebar -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sidebar   = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        const overlay   = document.getElementById('sidebarOverlay');

        console.log('Sidebar script loaded'); // Debug-Hinweis in der Konsole

        // 1. Toggle-Button: Sidebar auf/zu (Desktop = collapse, Mobile = off-canvas)
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                console.log('Toggle button clicked');

                if (window.innerWidth <= 768) {
                    // Mobile: Sidebar als Overlay öffnen/schließen
                    sidebar.classList.toggle('open');
                    if (overlay) {
                        overlay.style.display = sidebar.classList.contains('open') ? 'block' : 'none';
                    }
                    document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
                } else {
                    // Desktop: nur Breite ein-/ausklappen
                    sidebar.classList.toggle('collapsed');
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                }
            });
        }

        // 2. Initialzustand: auf Desktop standardmäßig nicht eingeklappt
        const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebar && window.innerWidth > 768) {
            sidebar.classList.remove('collapsed');
            localStorage.setItem('sidebarCollapsed', 'false');
        }

        // 3. Kategorien-Header: Ein-/Ausklappen der Unterthemen
        const categoryHeaders = document.querySelectorAll('.sidebar-category-header');

        categoryHeaders.forEach(header => {
            const categoryId = header.getAttribute('data-category');
            const subtopics  = document.getElementById(categoryId + '-subtopics');
            const arrowIcon  = header.querySelector('i');

            // Aktuelle Seite: Fach + Kategorie vorab geöffnet darstellen
            const isCurrentSubject = header.classList.contains('active') && subtopics.classList.contains('expanded');

            // Beim Laden: nur das aktuelle Thema offen lassen
            if (!isCurrentSubject) {
                subtopics.classList.remove('expanded');
                subtopics.style.maxHeight = '0';
                header.classList.remove('active');
                arrowIcon.style.transform = 'rotate(0deg)';
            } else {
                subtopics.classList.add('expanded');
                subtopics.style.maxHeight = subtopics.scrollHeight + 'px';
                header.classList.add('active');
                arrowIcon.style.transform = 'rotate(180deg)';
            }

            // Klick: Unterthemen toggeln
            header.addEventListener('click', function(e) {
                e.stopPropagation();

                // Wenn auf Desktop komplett eingeklappt, erst Sidebar wieder öffnen
                if (sidebar.classList.contains('collapsed') && window.innerWidth > 768) {
                    sidebar.classList.remove('collapsed');
                    return;
                }

                if (subtopics.classList.contains('expanded')) {
                    // Zuklappen mit kleiner Animation
                    subtopics.style.maxHeight = subtopics.scrollHeight + 'px';
                    setTimeout(() => {
                        subtopics.style.maxHeight = '0';
                        setTimeout(() => {
                            subtopics.classList.remove('expanded');
                        }, 300);
                    }, 10);
                    arrowIcon.style.transform = 'rotate(0deg)';
                    this.classList.remove('active');
                } else {
                    // Aufklappen
                    subtopics.classList.add('expanded');
                    subtopics.style.maxHeight = subtopics.scrollHeight + 'px';
                    arrowIcon.style.transform = 'rotate(180deg)';
                    this.classList.add('active');
                }
            });
        });

        // 4. Overlay-Klick schließt die Sidebar (mobil)
        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('open');
                this.style.display = 'none';
                document.body.style.overflow = '';
            });
        }

        // 5. Klick außerhalb schließt Sidebar auf mobilen Geräten
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768 &&
                sidebar.classList.contains('open') &&
                !sidebar.contains(e.target) &&
                !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('open');
                if (overlay) overlay.style.display = 'none';
                document.body.style.overflow = '';
            }
        });

        // 6. Beim Klick auf einen Sidebar-Link (mobil) Sidebar schließen
        const sidebarLinks = document.querySelectorAll('.sidebar-subtopic a');
        sidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    sidebar.classList.remove('open');
                    if (overlay) overlay.style.display = 'none';
                    document.body.style.overflow = '';
                }
            });
        });
    });
</script>

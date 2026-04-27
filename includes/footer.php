
<?php
// footer.php
?>

</main> <!-- Close main-content if open -->

<footer class="footer">
    <div class="footer-row">
        <a href="#" class="footer-link">HSGG Website</a>
        <span class="footer-divider"></span>
        <a href="#" class="footer-link">Guide</a>
        <span class="footer-divider"></span>
        <a href="<?php echo isset($rootPath) ? $rootPath : '/'; ?>kontakt.php" class="footer-link">Kontakt</a>
    </div>

    <div class="footer-bottom">
        © 2025 Horst-Schlämmer-Gedächtnis-Gymnasium
    </div>
</footer>

<script src="<?php echo isset($rootPath) ? $rootPath : '/'; ?>script.js" defer></script>

</body>
</html>

    <footer class="main-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Bosque Modelo Palencia</h3>
                    <p>Dashboard Ambiental para la visualización de datos forestales y ambientales de Castilla y León.</p>
                </div>

                <div class="footer-section">
                    <h3>Contacto</h3>
                    <p>
                        <strong>Email:</strong><br>
                        <a href="mailto:<?= esc($contact_email ?? 'secretariatecnica@palenciabosquemodelo.es') ?>">
                            <?= esc($contact_email ?? 'secretariatecnica@palenciabosquemodelo.es') ?>
                        </a>
                    </p>
                </div>

                <div class="footer-section">
                    <h3>Información</h3>
                    <ul>
                        <li><a href="#about">Acerca del proyecto</a></li>
                        <li><a href="#methodology">Metodología</a></li>
                        <li><a href="#data-sources">Fuentes de datos</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-financing">
                <img src="<?= base_url('logos/FINANCIA_ON line_sin fondo.png') ?>" alt="Financiado por" class="financing-logo">
            </div>

            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Bosque Modelo Palencia. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="<?= base_url('js/main.js') ?>"></script>
</body>
</html>

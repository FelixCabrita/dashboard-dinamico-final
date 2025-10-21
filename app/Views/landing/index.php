<?= view('layouts/header_public') ?>

<main class="landing-main">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1>Dashboard Ambiental</h1>
                <h2>Bosque Modelo Palencia</h2>
                <p class="hero-description">
                    Sistema de visualización de datos forestales, ambientales y territoriales de Castilla y León.
                    Información actualizada y transparente para la gestión sostenible de nuestros bosques.
                </p>
                <div class="hero-actions">
                    <a href="<?= base_url('/dashboard') ?>" class="btn btn-primary">Ver Dashboard</a>
                    <a href="#about" class="btn btn-secondary">Conocer más</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <h2 class="section-title">Características</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Datos Actualizados</h3>
                    <p>Información forestal basada en el Inventario Forestal Nacional y Mapa Forestal Español.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🌲</div>
                    <h3>Cobertura Forestal</h3>
                    <p>Visualización de existencias, formaciones arboladas y diversidad de especies.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🗺️</div>
                    <h3>Análisis Territorial</h3>
                    <p>Datos por municipio, provincia y tipo de formación forestal.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">💧</div>
                    <h3>Datos Ambientales</h3>
                    <p>Indicadores hídricos, riesgo de incendios y servicios ecosistémicos.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about-section" id="about">
        <div class="container">
            <h2 class="section-title">Acerca del Proyecto</h2>
            <div class="about-content">
                <div class="about-text">
                    <p>
                        El <strong>Bosque Modelo Palencia</strong> es una iniciativa de gestión forestal sostenible
                        que integra datos forestales, ambientales y territoriales de Castilla y León.
                    </p>
                    <p>
                        Este dashboard permite visualizar de forma interactiva información clave sobre:
                    </p>
                    <ul>
                        <li>Existencias forestales por municipio y especie</li>
                        <li>Evolución temporal de la cobertura forestal (IFN2, IFN3, IFN4)</li>
                        <li>Formaciones arboladas, arbustivas y herbáceas (MFE25, MFE50)</li>
                        <li>Modelos de combustible forestal y riesgo de incendios</li>
                        <li>Valoración económica de servicios ecosistémicos</li>
                        <li>Calidades forestales y volumen de madera</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Data Sources Section -->
    <section class="data-sources-section" id="data-sources">
        <div class="container">
            <h2 class="section-title">Fuentes de Datos</h2>
            <div class="sources-grid">
                <div class="source-card">
                    <h4>Datos Locales</h4>
                    <p>12 archivos CSV con más de 144,000 registros de inventarios forestales nacionales (IFN2, IFN3, IFN4)
                        y mapas forestales de España (MFE25, MFE50).</p>
                </div>
                <div class="source-card">
                    <h4>Cobertura Geográfica</h4>
                    <p>12 provincias de Castilla y León, cientos de municipios y miles de especies forestales catalogadas.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-section" id="contact">
        <div class="container">
            <h2 class="section-title">Contacto</h2>
            <div class="contact-content">
                <p>Para más información sobre el proyecto Bosque Modelo Palencia:</p>
                <p class="contact-email">
                    <strong>Email:</strong>
                    <a href="mailto:<?= esc($contact_email) ?>"><?= esc($contact_email) ?></a>
                </p>
            </div>
        </div>
    </section>
</main>

<?= view('layouts/footer_public') ?>

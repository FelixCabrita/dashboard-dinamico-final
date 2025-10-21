<?= view('layouts/header_public') ?>

<main class="dashboard-main">
    <div class="container">
        <div class="dashboard-header">
            <h1>Dashboard Ambiental</h1>
            <p class="dashboard-subtitle">Visualización de datos forestales y ambientales</p>
        </div>

        <!-- Placeholder para dashboard dinámico (Hito 6) -->
        <div class="dashboard-placeholder">
            <div class="placeholder-content">
                <h2>Dashboard en Construcción</h2>
                <p>El sistema de dashboards dinámicos estará disponible en el Hito 6.</p>
                <p>Por ahora, esta es una vista de ejemplo.</p>

                <!-- Ejemplo de componentes estáticos -->
                <div class="components-grid">
                    <div class="component-placeholder kpi-placeholder">
                        <h3>KPI Ejemplo</h3>
                        <div class="kpi-value">12,450 ha</div>
                        <p>Superficie Forestal</p>
                    </div>

                    <div class="component-placeholder kpi-placeholder">
                        <h3>KPI Ejemplo</h3>
                        <div class="kpi-value">2024</div>
                        <p>Año Actual</p>
                    </div>

                    <div class="component-placeholder chart-placeholder">
                        <h3>Gráfico Ejemplo</h3>
                        <p>Aquí se mostrará un gráfico de evolución temporal</p>
                    </div>

                    <div class="component-placeholder table-placeholder">
                        <h3>Tabla Ejemplo</h3>
                        <p>Aquí se mostrará una tabla de datos</p>
                    </div>
                </div>

                <div class="info-box">
                    <strong>Nota:</strong> Los componentes dinámicos (KPIs, gráficos, tablas, mapas) se implementarán
                    en los próximos hitos según el plan de desarrollo.
                </div>
            </div>
        </div>
    </div>
</main>

<?= view('layouts/footer_public') ?>

<?= view('layouts/header_admin') ?>

<div class="dashboard-stats">
    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div class="stat-content">
            <h3>Total Usuarios</h3>
            <div class="stat-value"><?= esc($stats['total_users']) ?></div>
            <?php if (!empty($stats['last_user'])): ?>
                <small>Último: <?= esc($stats['last_user']['name']) ?></small>
            <?php endif; ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">📅</div>
        <div class="stat-content">
            <h3>Nuevos Hoy</h3>
            <div class="stat-value"><?= esc($stats['users_today']) ?></div>
            <small>Registrados hoy</small>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">📊</div>
        <div class="stat-content">
            <h3>Esta Semana</h3>
            <div class="stat-value"><?= esc($stats['users_this_week']) ?></div>
            <small>Últimos 7 días</small>
        </div>
    </div>

    <div class="stat-card <?= $stats['db_exists'] ? '' : 'disabled' ?>">
        <div class="stat-icon">💾</div>
        <div class="stat-content">
            <h3>Base de Datos</h3>
            <div class="stat-value"><?= $stats['db_exists'] ? number_format($stats['db_records']) : 'N/A' ?></div>
            <small><?= $stats['db_exists'] ? number_format($stats['db_size'] / 1024 / 1024, 1) . ' MB' : 'No generada' ?></small>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">🧩</div>
        <div class="stat-content">
            <h3>Componentes</h3>
            <div class="stat-value"><?= esc($stats['total_components']) ?></div>
            <small><?= $stats['total_components'] > 0 ? 'KPIs, tablas y gráficos' : 'No creados aún' ?></small>
        </div>
    </div>
</div>

<div class="dashboard-content">
    <div class="welcome-box">
        <h2>Bienvenido, <?= esc($user_name) ?></h2>
        <p>Estás en el panel administrativo del Dashboard Ambiental Bosque Modelo Palencia.</p>

        <?php if ($stats['db_exists']): ?>
        <div class="info-panel" style="background: #e8f5e9; border-left: 4px solid #4caf50; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h3 style="color: #2e7d32; margin-top: 0;">✅ Base de Datos Activa</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px;">
                <div>
                    <strong>Total de Registros:</strong><br>
                    <span style="font-size: 24px; color: #2e7d32;"><?= number_format($stats['db_records']) ?></span>
                </div>
                <div>
                    <strong>Tamaño:</strong><br>
                    <span style="font-size: 24px; color: #2e7d32;"><?= number_format($stats['db_size'] / 1024 / 1024, 2) ?> MB</span>
                </div>
                <div>
                    <strong>Última Actualización:</strong><br>
                    <span style="font-size: 16px; color: #2e7d32;"><?= $stats['db_last_update'] ?></span>
                </div>
            </div>
            <div style="margin-top: 15px;">
                <a href="<?= base_url('/admin-panel-bmp/data-manager') ?>" class="btn btn-primary" style="background: #4caf50; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    📊 Gestionar Datos
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="info-panel" style="background: #fff3cd; border-left: 4px solid #ff9800; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h3 style="color: #f57c00; margin-top: 0;">⚠️ Base de Datos No Generada</h3>
            <p style="margin: 10px 0;">La base de datos SQLite aún no ha sido generada. Ejecuta el proceso ETL para procesar los archivos CSV y crear la base de datos.</p>
            <div style="margin-top: 15px;">
                <a href="<?= base_url('/admin-panel-bmp/data-manager') ?>" class="btn btn-primary" style="background: #ff9800; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    ▶ Ejecutar ETL
                </a>
            </div>
        </div>
        <?php endif; ?>

        <div class="info-panel">
            <h3>Hitos Completados</h3>
            <ul class="checklist">
                <li class="completed">✓ <strong>Hito 1:</strong> Infraestructura base + Autenticación</li>
                <li class="completed">✓ <strong>Hito 2:</strong> CRUD completo de usuarios
                    <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                        <li class="completed">✓ Paginación y búsqueda</li>
                        <li class="completed">✓ Validaciones avanzadas</li>
                        <li class="completed">✓ Rate limiting en login</li>
                        <li class="completed">✓ Validación en tiempo real</li>
                        <li class="completed">✓ Estadísticas de usuarios</li>
                    </ul>
                </li>
                <li class="completed">✓ <strong>Hito 3:</strong> Sistema ETL + Base de datos SQLite
                    <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                        <li class="completed">✓ Comando CLI para ETL</li>
                        <li class="completed">✓ Procesamiento de 12 archivos CSV (144K+ registros)</li>
                        <li class="completed">✓ Base de datos SQLite optimizada (4.3 MB)</li>
                        <li class="completed">✓ Sistema de versionado</li>
                        <li class="completed">✓ Panel de gestión de datos</li>
                        <li class="completed">✓ Visualizador de logs</li>
                    </ul>
                </li>
                <li class="completed">✓ <strong>Hito 4:</strong> API REST + Constructor de componentes
                    <ul style="margin-left: 1.5rem; margin-top: 0.5rem;">
                        <li class="completed">✓ QueryBuilder dinámico (600+ líneas)</li>
                        <li class="completed">✓ Sistema de caché con TTL</li>
                        <li class="completed">✓ 9 endpoints RESTful funcionales</li>
                        <li class="completed">✓ ComponentModel con JSON storage</li>
                        <li class="completed">✓ Wizard de creación de componentes</li>
                        <li class="completed">✓ Soporte para KPIs, tablas y gráficos</li>
                    </ul>
                </li>
            </ul>

            <h3>Próximos Hitos</h3>
            <ul class="roadmap">
                <li><strong>Hito 5:</strong> Renderizado de componentes visuales</li>
                <li><strong>Hito 6:</strong> Gestión de dashboards dinámicos</li>
                <li><strong>Hito 7:</strong> Mapas + Integración CKAN</li>
            </ul>
        </div>

        <?php if ($stats['total_components'] > 0): ?>
        <div class="info-panel" style="background: #f3e5f5; border-left: 4px solid #6D0C6D; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h3 style="color: #6D0C6D; margin-top: 0;">🧩 Componentes Creados</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; margin-top: 10px;">
                <div style="text-align: center;">
                    <strong>Total</strong><br>
                    <span style="font-size: 24px; color: #6D0C6D;"><?= $stats['total_components'] ?></span>
                </div>
                <div style="text-align: center;">
                    <strong>KPIs</strong><br>
                    <span style="font-size: 24px; color: #1976d2;"><?= $stats['components_by_type']['kpi'] ?></span>
                </div>
                <div style="text-align: center;">
                    <strong>Tablas</strong><br>
                    <span style="font-size: 24px; color: #7b1fa2;"><?= $stats['components_by_type']['table'] ?></span>
                </div>
                <div style="text-align: center;">
                    <strong>Gráficos</strong><br>
                    <span style="font-size: 24px; color: #388e3c;"><?= $stats['components_by_type']['line_chart'] + $stats['components_by_type']['bar_chart'] + $stats['components_by_type']['pie_chart'] ?></span>
                </div>
            </div>
            <div style="margin-top: 15px;">
                <a href="<?= base_url('/admin-panel-bmp/components') ?>" class="btn btn-primary" style="background: #6D0C6D; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    🧩 Gestionar Componentes
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="info-panel" style="background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 20px 0; border-radius: 5px;">
            <h3 style="color: #1976d2; margin-top: 0;">🧩 Constructor de Componentes Disponible</h3>
            <p style="margin: 10px 0;">Ahora puedes crear componentes visuales (KPIs, tablas, gráficos) para tus dashboards usando el wizard interactivo.</p>
            <div style="margin-top: 15px;">
                <a href="<?= base_url('/admin-panel-bmp/components/create') ?>" class="btn btn-primary" style="background: #2196f3; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">
                    + Crear Primer Componente
                </a>
            </div>
        </div>
        <?php endif; ?>
        </div>

        <div class="quick-actions">
            <h3>Acciones Rápidas</h3>
            <a href="<?= base_url('/admin-panel-bmp/users') ?>" class="btn btn-primary">
                👥 Gestionar Usuarios
            </a>
            <a href="<?= base_url('/admin-panel-bmp/data-manager') ?>" class="btn btn-primary">
                💾 Gestionar Datos
            </a>
            <a href="<?= base_url('/admin-panel-bmp/components') ?>" class="btn btn-primary">
                🧩 Gestionar Componentes
            </a>
            <a href="<?= base_url('/') ?>" class="btn btn-secondary" target="_blank">
                🌐 Ver Landing Pública
            </a>
        </div>
    </div>
</div>

<?= view('layouts/footer_admin') ?>

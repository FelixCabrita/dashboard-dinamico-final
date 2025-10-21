<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Panel Administrativo BMP</title>
    <link rel="stylesheet" href="/wp/dashboard-dinamico-final/public/css/admin.css">
    <style>
        .data-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .stat-card {
            background: white;
            border: 2px solid #6D0C6D;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #6D0C6D;
            font-size: 14px;
            text-transform: uppercase;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #010101;
            margin: 10px 0;
        }

        .stat-card .label {
            font-size: 12px;
            color: #666;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #6D0C6D;
            color: white;
        }

        .btn-primary:hover {
            background: #550A55;
        }

        .btn-secondary {
            background: #666;
            color: white;
        }

        .btn-secondary:hover {
            background: #444;
        }

        .versions-table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            margin: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .versions-table th,
        .versions-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .versions-table th {
            background: #6D0C6D;
            color: white;
            font-weight: bold;
        }

        .versions-table tr:hover {
            background: #f8f8f8;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }

        .status-success {
            background: #d4edda;
            color: #155724;
        }

        .status-error {
            background: #f8d7da;
            color: #721c24;
        }

        .status-running {
            background: #fff3cd;
            color: #856404;
        }

        .status-unknown {
            background: #e2e3e5;
            color: #383d41;
        }

        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
            font-weight: bold;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }

        .section-title {
            color: #6D0C6D;
            font-size: 24px;
            margin: 30px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #6D0C6D;
        }

        .table-stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin: 20px 0;
        }

        .table-stat {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #6D0C6D;
        }

        .table-stat .name {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .table-stat .count {
            font-size: 20px;
            font-weight: bold;
            color: #010101;
        }
    </style>
</head>
<body>
    <?= view('layouts/header_admin') ?>

    <div class="container" style="padding: 20px;">
        <h1>Gestión de Datos ETL</h1>

        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success">
                ✓ <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>

        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-error">
                ✗ <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>

        <!-- Botones de Acción -->
        <div class="action-buttons">
            <form method="POST" action="<?= admin_url('data-manager/run-etl') ?>" style="display: inline;">
                <button type="submit" class="btn btn-primary" onclick="return confirm('¿Está seguro de ejecutar el proceso ETL? Esto puede tomar varios minutos.')">
                    ▶ Ejecutar ETL
                </button>
            </form>
            <a href="<?= admin_url('data-manager/logs') ?>" class="btn btn-secondary">
                📄 Ver Logs
            </a>
        </div>

        <!-- Estadísticas de la Base de Datos Actual -->
        <h2 class="section-title">Estado de la Base de Datos</h2>

        <?php if ($currentDb): ?>
            <div class="data-stats">
                <div class="stat-card">
                    <h3>Tamaño Total</h3>
                    <div class="value"><?= number_format($currentDb['size'] / 1024 / 1024, 2) ?></div>
                    <div class="label">MB</div>
                </div>

                <div class="stat-card">
                    <h3>Total Registros</h3>
                    <div class="value"><?= number_format($statistics['total_records']) ?></div>
                    <div class="label">Registros</div>
                </div>

                <div class="stat-card">
                    <h3>Última Actualización</h3>
                    <div class="value"><?= date('d/m/Y', $currentDb['modified']) ?></div>
                    <div class="label"><?= date('H:i:s', $currentDb['modified']) ?></div>
                </div>

                <div class="stat-card">
                    <h3>Versiones Disponibles</h3>
                    <div class="value"><?= count($versions) ?></div>
                    <div class="label">Versiones históricas</div>
                </div>
            </div>

            <!-- Estadísticas por Tabla -->
            <?php if (!empty($statistics['tables'])): ?>
                <h3 style="margin-top: 30px; color: #6D0C6D;">Registros por Tabla</h3>
                <div class="table-stats">
                    <?php foreach ($statistics['tables'] as $table): ?>
                        <div class="table-stat">
                            <div class="name"><?= str_replace('fact_', '', $table['name']) ?></div>
                            <div class="count"><?= number_format($table['records']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="no-data">
                <p><strong>No hay base de datos generada</strong></p>
                <p>Ejecute el proceso ETL para generar la base de datos inicial.</p>
            </div>
        <?php endif; ?>

        <!-- Último Log de ETL -->
        <?php if ($lastLog): ?>
            <h2 class="section-title">Último Proceso ETL</h2>

            <div class="stat-card" style="text-align: left;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong>Archivo:</strong> <?= esc($lastLog['name']) ?><br>
                        <strong>Fecha:</strong> <?= date('d/m/Y H:i:s', $lastLog['created']) ?><br>
                        <strong>Tamaño:</strong> <?= number_format($lastLog['size'] / 1024, 2) ?> KB
                    </div>
                    <div>
                        <span class="status-badge status-<?= $lastLog['status'] ?>">
                            <?= strtoupper($lastLog['status']) ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Versiones Históricas -->
        <?php if (!empty($versions)): ?>
            <h2 class="section-title">Versiones Históricas</h2>

            <table class="versions-table">
                <thead>
                    <tr>
                        <th>Versión</th>
                        <th>Fecha de Creación</th>
                        <th>Tamaño</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($versions as $version): ?>
                        <tr>
                            <td><?= esc($version['name']) ?></td>
                            <td><?= date('d/m/Y H:i:s', $version['created']) ?></td>
                            <td><?= number_format($version['size'] / 1024 / 1024, 2) ?> MB</td>
                            <td>
                                <a href="<?= admin_url('data-manager/download-db/' . urlencode($version['name'])) ?>" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">
                                    ⬇ Descargar
                                </a>
                                <form method="POST" action="<?= admin_url('data-manager/restore/' . urlencode($version['name'])) ?>" style="display: inline;" onsubmit="return confirm('¿Restaurar esta versión?')">
                                    <button type="submit" class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;">
                                        ↻ Restaurar
                                    </button>
                                </form>
                                <form method="POST" action="<?= admin_url('data-manager/delete/' . urlencode($version['name'])) ?>" style="display: inline;" onsubmit="return confirm('¿Eliminar esta versión?')">
                                    <button type="submit" class="btn" style="padding: 6px 12px; font-size: 12px; background: #dc3545; color: white;">
                                        ✗ Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Panel Administrativo BMP</title>
    <link rel="stylesheet" href="/wp/dashboard-dinamico-final/public/css/admin.css">
    <style>
        .log-viewer {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            max-height: 600px;
            overflow-y: auto;
        }

        .log-line {
            margin: 2px 0;
        }

        .log-info {
            color: #4ec9b0;
        }

        .log-success {
            color: #6a9955;
            font-weight: bold;
        }

        .log-warning {
            color: #dcdcaa;
        }

        .log-error {
            color: #f48771;
            font-weight: bold;
        }

        .log-selector {
            margin: 20px 0;
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .log-selector select {
            padding: 10px;
            border: 2px solid #6D0C6D;
            border-radius: 5px;
            font-size: 14px;
            flex: 1;
            max-width: 400px;
        }

        .btn {
            padding: 10px 20px;
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

        .no-log {
            text-align: center;
            padding: 60px 20px;
            color: #666;
            font-style: italic;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #6D0C6D;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?= view('layouts/header_admin') ?>

    <div class="container" style="padding: 20px;">
        <a href="<?= admin_url('data-manager') ?>" class="back-link">← Volver a Gestión de Datos</a>

        <h1>Logs de Procesos ETL</h1>

        <?php if (!empty($logFiles)): ?>
            <div class="log-selector">
                <label for="log-select"><strong>Seleccionar log:</strong></label>
                <select id="log-select" onchange="window.location.href='<?= admin_url('data-manager/logs/') ?>' + this.value">
                    <?php foreach ($logFiles as $log): ?>
                        <option value="<?= esc($log['name']) ?>" <?= $log['name'] === $logFile ? 'selected' : '' ?>>
                            <?= esc($log['name']) ?>
                            (<?= date('d/m/Y H:i:s', $log['created']) ?>)
                            - <?= strtoupper($log['status']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <?php if ($logFile): ?>
                    <a href="<?= admin_url('data-manager/download-log/' . urlencode($logFile)) ?>" class="btn btn-secondary">
                        ⬇ Descargar
                    </a>
                <?php endif; ?>
            </div>

            <?php if ($logContent): ?>
                <div class="log-viewer">
                    <?php
                    $lines = explode("\n", $logContent);
                    foreach ($lines as $line) {
                        if (empty(trim($line))) continue;

                        $class = 'log-line';
                        if (strpos($line, '[INFO]') !== false) {
                            $class .= ' log-info';
                        } elseif (strpos($line, '[SUCCESS]') !== false) {
                            $class .= ' log-success';
                        } elseif (strpos($line, '[WARNING]') !== false) {
                            $class .= ' log-warning';
                        } elseif (strpos($line, '[ERROR]') !== false) {
                            $class .= ' log-error';
                        }

                        echo '<div class="' . $class . '">' . esc($line) . '</div>';
                    }
                    ?>
                </div>
            <?php else: ?>
                <div class="no-log">
                    <p><strong>El log está vacío o no se pudo leer</strong></p>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="no-log">
                <p><strong>No hay logs de ETL disponibles</strong></p>
                <p>Ejecute el proceso ETL para generar logs.</p>
                <a href="<?= admin_url('data-manager') ?>" class="btn btn-primary" style="margin-top: 20px;">
                    Ir a Gestión de Datos
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-scroll al final del log
        const logViewer = document.querySelector('.log-viewer');
        if (logViewer) {
            logViewer.scrollTop = logViewer.scrollHeight;
        }
    </script>
</body>
</html>

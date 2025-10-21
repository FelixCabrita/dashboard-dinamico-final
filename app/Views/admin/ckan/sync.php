<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?> - Dashboard BMP</title>
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>">
    <style>
        .sync-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .resource-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .resource-info h3 {
            margin: 0 0 5px 0;
            color: #333;
        }

        .resource-info p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }

        .resource-status {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-success {
            background: #d4edda;
            color: #155724;
        }

        .status-processing {
            background: #fff3cd;
            color: #856404;
        }

        .status-error {
            background: #f8d7da;
            color: #721c24;
        }

        .status-never {
            background: #e9ecef;
            color: #495057;
        }

        .sync-button {
            padding: 10px 20px;
            background: #6D0C6D;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .sync-button:hover {
            background: #4a0848;
        }

        .sync-button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .meta-info {
            font-size: 12px;
            color: #999;
        }

        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: none;
        }

        .notification.show {
            display: block;
        }

        .notification.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .notification.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .notification.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #6D0C6D;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
            margin-right: 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <?= view('admin/partials/header') ?>

        <div class="sync-container">
            <h1><?= esc($title) ?></h1>

            <div id="notification" class="notification"></div>

            <?php if (empty($resources)): ?>
                <div class="resource-card">
                    <p>No hay recursos configurados para sincronización.</p>
                </div>
            <?php else: ?>
                <?php foreach ($resources as $resource): ?>
                    <div class="resource-card" data-resource="<?= esc($resource['key']) ?>">
                        <div class="resource-info">
                            <h3><?= esc($resource['name']) ?></h3>
                            <p><?= esc($resource['description']) ?></p>
                            <p class="meta-info">
                                <?php if ($resource['last_synced']): ?>
                                    Última sincronización: <?= esc($resource['last_synced']) ?>
                                    (<?= number_format($resource['feature_count']) ?> registros)
                                <?php else: ?>
                                    Nunca sincronizado
                                <?php endif; ?>
                            </p>
                            <?php if ($resource['error_message']): ?>
                                <p style="color: #721c24; font-size: 12px;">
                                    Error: <?= esc($resource['error_message']) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="resource-status">
                            <span class="status-badge status-<?= $resource['status'] === 'success' ? 'success' : ($resource['status'] === 'processing' ? 'processing' : ($resource['status'] === 'error' ? 'error' : 'never')) ?>">
                                <?= $resource['status'] === 'success' ? 'Sincronizado' : ($resource['status'] === 'processing' ? 'Procesando...' : ($resource['status'] === 'error' ? 'Error' : 'Pendiente')) ?>
                            </span>

                            <button
                                class="sync-button"
                                onclick="syncResource('<?= esc($resource['key']) ?>')"
                                <?= $resource['status'] === 'processing' ? 'disabled' : '' ?>
                            >
                                Sincronizar
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showNotification(message, type = 'info') {
            const notification = document.getElementById('notification');
            notification.textContent = message;
            notification.className = 'notification ' + type + ' show';

            setTimeout(() => {
                notification.className = 'notification';
            }, 5000);
        }

        async function syncResource(resourceName) {
            const card = document.querySelector(`[data-resource="${resourceName}"]`);
            const button = card.querySelector('.sync-button');
            const statusBadge = card.querySelector('.status-badge');

            // Deshabilitar botón y mostrar loading
            button.disabled = true;
            button.innerHTML = '<span class="spinner"></span> Iniciando...';
            statusBadge.textContent = 'Procesando...';
            statusBadge.className = 'status-badge status-processing';

            try {
                const response = await fetch('<?= base_url('admin-panel-bmp/ckan/sync/trigger') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        resource_name: resourceName
                    })
                });

                const result = await response.json();

                if (result.success) {
                    showNotification(result.message, 'success');

                    // Actualizar UI
                    button.textContent = 'Sincronizando...';

                    // Verificar estado cada 10 segundos
                    const checkInterval = setInterval(async () => {
                        const statusResponse = await fetch(`<?= base_url('admin-panel-bmp/ckan/sync/status') ?>/${resourceName}`);
                        const statusData = await statusResponse.json();

                        if (statusData.success && statusData.status === 'success') {
                            clearInterval(checkInterval);

                            statusBadge.textContent = 'Sincronizado';
                            statusBadge.className = 'status-badge status-success';
                            button.textContent = 'Sincronizar';
                            button.disabled = false;

                            showNotification('¡Sincronización completada! La tabla está lista para usar.', 'success');

                            // Recargar para mostrar datos actualizados
                            setTimeout(() => location.reload(), 2000);
                        } else if (statusData.status === 'error') {
                            clearInterval(checkInterval);

                            statusBadge.textContent = 'Error';
                            statusBadge.className = 'status-badge status-error';
                            button.textContent = 'Reintentar';
                            button.disabled = false;

                            showNotification('Error en la sincronización. Revisa los logs.', 'error');
                        }
                    }, 10000); // Cada 10 segundos

                } else {
                    throw new Error(result.message || 'Error desconocido');
                }

            } catch (error) {
                console.error('Error:', error);
                showNotification('Error: ' + error.message, 'error');

                // Restaurar botón
                button.textContent = 'Sincronizar';
                button.disabled = false;
                statusBadge.textContent = 'Error';
                statusBadge.className = 'status-badge status-error';
            }
        }
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Panel Administrativo BMP</title>
    <link rel="stylesheet" href="/wp/dashboard-dinamico-final/public/css/admin.css">
    <style>
        .components-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
        }

        .components-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .stat-box {
            background: white;
            border: 2px solid #6D0C6D;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
        }

        .stat-box h4 {
            margin: 0 0 10px 0;
            color: #6D0C6D;
            font-size: 12px;
            text-transform: uppercase;
        }

        .stat-box .value {
            font-size: 28px;
            font-weight: bold;
            color: #010101;
        }

        .components-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .component-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s;
        }

        .component-card:hover {
            border-color: #6D0C6D;
            box-shadow: 0 4px 12px rgba(109, 12, 109, 0.1);
        }

        .component-card .type-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .type-kpi { background: #e3f2fd; color: #1976d2; }
        .type-table { background: #f3e5f5; color: #7b1fa2; }
        .type-line_chart { background: #e8f5e9; color: #388e3c; }
        .type-bar_chart { background: #fff3e0; color: #f57c00; }
        .type-pie_chart { background: #fce4ec; color: #c2185b; }

        .component-card h3 {
            margin: 10px 0;
            color: #010101;
            font-size: 18px;
        }

        .component-card p {
            color: #666;
            font-size: 14px;
            margin: 10px 0;
        }

        .component-card .actions {
            display: flex;
            gap: 8px;
            margin-top: 15px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
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

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 40px 0;
        }

        .empty-state h3 {
            color: #6D0C6D;
            margin-bottom: 15px;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filters form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #6D0C6D;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?= view('layouts/header_admin') ?>

    <div class="container" style="padding: 20px;">
        <div class="components-header">
            <h1>🧩 Gestión de Componentes</h1>
            <a href="<?= admin_url('components/create') ?>" class="btn btn-primary">
                + Crear Componente
            </a>
        </div>

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

        <!-- Estadísticas -->
        <div class="components-stats">
            <div class="stat-box">
                <h4>Total</h4>
                <div class="value"><?= $stats['total'] ?></div>
            </div>
            <div class="stat-box">
                <h4>KPIs</h4>
                <div class="value"><?= $stats['by_type']['kpi'] ?></div>
            </div>
            <div class="stat-box">
                <h4>Tablas</h4>
                <div class="value"><?= $stats['by_type']['table'] ?></div>
            </div>
            <div class="stat-box">
                <h4>Gráficos</h4>
                <div class="value"><?= $stats['by_type']['line_chart'] + $stats['by_type']['bar_chart'] + $stats['by_type']['pie_chart'] ?></div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filters">
            <form method="GET">
                <div class="filter-group">
                    <label>Tipo de Componente</label>
                    <select name="type">
                        <option value="">Todos</option>
                        <option value="kpi" <?= ($filters['type'] ?? '') === 'kpi' ? 'selected' : '' ?>>KPI</option>
                        <option value="table" <?= ($filters['type'] ?? '') === 'table' ? 'selected' : '' ?>>Tabla</option>
                        <option value="line_chart" <?= ($filters['type'] ?? '') === 'line_chart' ? 'selected' : '' ?>>Gráfico de Líneas</option>
                        <option value="bar_chart" <?= ($filters['type'] ?? '') === 'bar_chart' ? 'selected' : '' ?>>Gráfico de Barras</option>
                        <option value="pie_chart" <?= ($filters['type'] ?? '') === 'pie_chart' ? 'selected' : '' ?>>Gráfico Circular</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Buscar</label>
                    <input type="text" name="search" placeholder="Nombre o tags..." value="<?= esc($filters['search'] ?? '') ?>">
                </div>
                <div class="filter-group" style="flex: 0;">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                </div>
            </form>
        </div>

        <!-- Grid de Componentes -->
        <?php if (empty($components)): ?>
            <div class="empty-state">
                <h3>No hay componentes creados</h3>
                <p>Comienza creando tu primer componente visual para tus dashboards.</p>
                <a href="<?= admin_url('components/create') ?>" class="btn btn-primary" style="margin-top: 20px;">
                    + Crear Primer Componente
                </a>
            </div>
        <?php else: ?>
            <div class="components-grid">
                <?php foreach ($components as $component): ?>
                    <div class="component-card">
                        <span class="type-badge type-<?= esc($component['type']) ?>">
                            <?= strtoupper(str_replace('_', ' ', $component['type'])) ?>
                        </span>
                        <h3><?= esc($component['name']) ?></h3>
                        <p><?= esc($component['description'] ?? 'Sin descripción') ?></p>

                        <?php if (!empty($component['tags'])): ?>
                            <div style="margin: 10px 0; font-size: 12px; color: #999;">
                                Tags: <?= implode(', ', array_map('esc', $component['tags'])) ?>
                            </div>
                        <?php endif; ?>

                        <small style="display: block; margin-top: 10px; color: #999;">
                            Creado: <?= date('d/m/Y', strtotime($component['metadata']['created_at'])) ?>
                        </small>

                        <div class="actions">
                            <a href="<?= admin_url('components/edit/' . $component['id']) ?>" class="btn btn-primary btn-small">
                                ✎ Editar
                            </a>
                            <form method="POST" action="<?= admin_url('components/duplicate/' . $component['id']) ?>" style="display: inline;">
                                <button type="submit" class="btn btn-secondary btn-small">↻ Duplicar</button>
                            </form>
                            <form method="POST" action="<?= admin_url('components/delete/' . $component['id']) ?>" style="display: inline;" onsubmit="return confirm('¿Eliminar este componente?')">
                                <button type="submit" class="btn btn-danger btn-small">✗ Eliminar</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Paginación -->
            <?php if ($pagination['totalPages'] > 1): ?>
                <div style="text-align: center; margin: 30px 0;">
                    <?php for ($i = 1; $i <= $pagination['totalPages']; $i++): ?>
                        <a href="?page=<?= $i ?><?= !empty($filters['type']) ? '&type=' . $filters['type'] : '' ?><?= !empty($filters['search']) ? '&search=' . $filters['search'] : '' ?>"
                           class="btn <?= $i === $pagination['page'] ? 'btn-primary' : 'btn-secondary' ?> btn-small"
                           style="margin: 0 5px;">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>

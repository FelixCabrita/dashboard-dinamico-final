<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>

    <!-- Estilos base -->
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>">

    <!-- Estilos de componentes -->
    <link rel="stylesheet" href="<?= base_url('css/components/kpi.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/components/table.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/components/chart.css') ?>">

    <!-- Temas -->
    <link rel="stylesheet" href="<?= base_url('css/themes/corporativo.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/themes/forestal.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/themes/hidrico.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/themes/ambiental.css') ?>">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.min.js"></script>

    <!-- CountUp.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/countup.js/2.8.0/countUp.umd.min.js"></script>

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #2c3e50;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #6D0C6D 0%, #4a0848 100%);
            color: white;
            padding: 30px 20px;
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }

        .header p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .filters-panel {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .filters-panel h3 {
            margin: 0 0 15px 0;
            color: #6D0C6D;
            font-size: 18px;
        }

        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }

        .filter-group label {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #34495e;
        }

        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #6D0C6D;
            box-shadow: 0 0 0 3px rgba(109, 12, 109, 0.1);
        }

        .filter-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #6D0C6D;
            color: white;
        }

        .btn-primary:hover {
            background: #4a0848;
        }

        .btn-secondary {
            background: #7f8c8d;
            color: white;
        }

        .btn-secondary:hover {
            background: #6c757d;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #6D0C6D;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 13px;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .components-section {
            margin-bottom: 40px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #6D0C6D;
        }

        .components-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .component-wrapper {
            min-height: 200px;
        }

        @media (max-width: 768px) {
            .components-grid {
                grid-template-columns: 1fr;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="theme-corporativo">

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1><?= esc($title) ?></h1>
        <p>Testing y visualización de componentes dinámicos</p>
    </div>

    <!-- Estadísticas -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-value"><?= $totalComponents ?></div>
            <div class="stat-label">Total Componentes</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= count($componentsByType['kpi']) ?></div>
            <div class="stat-label">KPIs</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= count($componentsByType['table']) ?></div>
            <div class="stat-label">Tablas</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">
                <?= count($componentsByType['line_chart']) + count($componentsByType['bar_chart']) + count($componentsByType['pie_chart']) ?>
            </div>
            <div class="stat-label">Gráficos</div>
        </div>
    </div>

    <!-- KPIs -->
    <?php if (!empty($componentsByType['kpi'])): ?>
        <div class="components-section">
            <h2 class="section-title">📊 KPIs</h2>
            <div class="components-grid">
                <?php foreach ($componentsByType['kpi'] as $component): ?>
                    <div class="component-wrapper" id="component_<?= esc($component['id']) ?>">
                        <div style="padding: 20px; text-align: center; color: #7f8c8d;">
                            Cargando componente...
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tablas -->
    <?php if (!empty($componentsByType['table'])): ?>
        <div class="components-section">
            <h2 class="section-title">📋 Tablas</h2>
            <div class="components-grid" style="grid-template-columns: 1fr;">
                <?php foreach ($componentsByType['table'] as $component): ?>
                    <div class="component-wrapper" id="component_<?= esc($component['id']) ?>">
                        <div style="padding: 20px; text-align: center; color: #7f8c8d;">
                            Cargando componente...
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Gráficos -->
    <?php
    $charts = array_merge(
        $componentsByType['line_chart'],
        $componentsByType['bar_chart'],
        $componentsByType['pie_chart']
    );
    ?>
    <?php if (!empty($charts)): ?>
        <div class="components-section">
            <h2 class="section-title">📈 Gráficos</h2>
            <div class="components-grid">
                <?php foreach ($charts as $component): ?>
                    <div class="component-wrapper" id="component_<?= esc($component['id']) ?>">
                        <div style="padding: 20px; text-align: center; color: #7f8c8d;">
                            Cargando componente...
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
// Renderizar todos los componentes al cargar
document.addEventListener('DOMContentLoaded', function() {
    console.log('Renderizando componentes...');

    const componentIds = <?= json_encode(array_column($components, 'id')) ?>;

    // Renderizar cada componente
    componentIds.forEach(id => {
        renderComponent(id);
    });
});

/**
 * Renderizar un componente
 */
function renderComponent(id) {
    const container = document.getElementById('component_' + id);
    if (!container) return;

    // Mostrar loading
    container.innerHTML = '<div style="padding: 20px; text-align: center; color: #7f8c8d;">⏳ Cargando...</div>';

    // Construir URL
    const url = '<?= base_url('test-components/render/') ?>' + id;

    // Fetch
    fetch(url)
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                container.innerHTML = result.html;

                // Inicializar componentes después de inyectar HTML
                initializeComponentScripts(container);
            } else {
                container.innerHTML = `<div class="component-error" style="padding: 20px; background: #fee; border: 2px solid #f66; border-radius: 8px; color: #c00;">
                    <strong>Error:</strong> ${result.message}
                </div>`;
            }
        })
        .catch(error => {
            console.error('Error al renderizar componente:', id, error);
            container.innerHTML = `<div class="component-error" style="padding: 20px; background: #fee; border: 2px solid #f66; border-radius: 8px; color: #c00;">
                <strong>Error:</strong> ${error.message}
            </div>`;
        });
}

/**
 * Inicializar scripts de componentes después de inyección de HTML
 */
function initializeComponentScripts(container) {
    // Inicializar DataTables
    const tableComponent = container.querySelector('[data-component-type="table"]');
    if (tableComponent) {
        const tableId = tableComponent.getAttribute('data-table-id');
        if (tableId && typeof $.fn.DataTable !== 'undefined') {
            $('#' + tableId).DataTable({
                responsive: true,
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'copy',
                        text: 'Copiar',
                        className: 'btn-datatable'
                    },
                    {
                        extend: 'csv',
                        text: 'CSV',
                        className: 'btn-datatable'
                    },
                    {
                        extend: 'excel',
                        text: 'Excel',
                        className: 'btn-datatable'
                    },
                    {
                        extend: 'pdf',
                        text: 'PDF',
                        className: 'btn-datatable',
                        orientation: 'landscape',
                        pageSize: 'LEGAL'
                    },
                    {
                        extend: 'print',
                        text: 'Imprimir',
                        className: 'btn-datatable'
                    }
                ],
                order: [[0, 'asc']],
                scrollX: true
            });
        }
    }

    // Inicializar Chart.js
    const chartComponent = container.querySelector('[data-component-type="chart"]');
    if (chartComponent && typeof Chart !== 'undefined') {
        const canvasId = chartComponent.getAttribute('data-canvas-id');
        const canvas = document.getElementById(canvasId);

        if (canvas) {
            try {
                // Obtener datos del componente
                const labels = JSON.parse(chartComponent.getAttribute('data-chart-labels'));
                const values = JSON.parse(chartComponent.getAttribute('data-chart-values'));
                const chartType = chartComponent.getAttribute('data-chart-type');
                const color = chartComponent.getAttribute('data-chart-color');
                const measureLabel = chartComponent.getAttribute('data-measure-label');
                const unit = chartComponent.getAttribute('data-chart-unit');

                // Funciones helper
                function hexToRgb(hex) {
                    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
                    return result ? {
                        r: parseInt(result[1], 16),
                        g: parseInt(result[2], 16),
                        b: parseInt(result[3], 16)
                    } : { r: 109, g: 12, b: 109 };
                }

                function generateColorPalette(count, baseColor) {
                    const colors = [];
                    const rgb = hexToRgb(baseColor);

                    for (let i = 0; i < count; i++) {
                        const factor = i / count;
                        const r = Math.round(rgb.r + (255 - rgb.r) * factor * 0.5);
                        const g = Math.round(rgb.g + (255 - rgb.g) * factor * 0.5);
                        const b = Math.round(rgb.b + (255 - rgb.b) * factor * 0.5);
                        colors.push(`rgba(${r}, ${g}, ${b}, 0.8)`);
                    }
                    return colors;
                }

                // Generar colores
                let backgroundColor, borderColor;
                if (chartType === 'pie') {
                    backgroundColor = generateColorPalette(values.length, color);
                    borderColor = '#fff';
                } else {
                    backgroundColor = color + 'CC'; // 80% opacity
                    borderColor = color;
                }

                // Configuración del gráfico
                const chartConfig = {
                    type: chartType,
                    data: {
                        labels: labels,
                        datasets: [{
                            label: measureLabel,
                            data: values,
                            backgroundColor: backgroundColor,
                            borderColor: borderColor,
                            borderWidth: 2,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        aspectRatio: chartType === 'pie' ? 1 : 2,
                        plugins: {
                            legend: {
                                display: chartType === 'pie',
                                position: 'right'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        let label = context.label || '';
                                        if (label) label += ': ';

                                        const value = context.parsed.y !== undefined
                                            ? context.parsed.y
                                            : context.parsed;

                                        label += value.toLocaleString('es-ES');

                                        if (unit) {
                                            label += ' ' + unit;
                                        }

                                        return label;
                                    }
                                }
                            },
                            zoom: {
                                zoom: {
                                    wheel: {
                                        enabled: chartType !== 'pie'
                                    },
                                    pinch: {
                                        enabled: chartType !== 'pie'
                                    },
                                    mode: 'xy'
                                },
                                pan: {
                                    enabled: chartType !== 'pie',
                                    mode: 'xy'
                                }
                            }
                        },
                        scales: chartType !== 'pie' ? {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return value.toLocaleString('es-ES') +
                                               (unit ? ' ' + unit : '');
                                    }
                                }
                            }
                        } : {}
                    }
                };

                // Crear gráfico
                new Chart(canvas, chartConfig);

            } catch (e) {
                console.error('Error al inicializar gráfico:', e);
            }
        }
    }

    // Inicializar CountUp.js para KPIs
    const kpiComponent = container.querySelector('[data-component-type="kpi"]');
    if (kpiComponent) {
        const kpiElements = kpiComponent.querySelectorAll('.kpi-number');
        kpiElements.forEach(element => {
            if (typeof CountUp !== 'undefined') {
                const value = parseFloat(element.dataset.value);
                const unit = element.dataset.unit || '';
                const suffix = element.dataset.suffix || '';

                const options = {
                    duration: 2,
                    separator: '.',
                    decimal: ',',
                    suffix: (unit ? ' ' + unit : '') + (suffix ? ' ' + suffix : '')
                };

                const countUp = new CountUp(element.id, value, options);
                if (!countUp.error) {
                    countUp.start();
                }
            }
        });
    }
}
</script>

</body>
</html>

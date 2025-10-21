<?php
/**
 * Vista de Componente Gráfico
 * Template para renderizar gráficos con Chart.js
 */

use App\Libraries\ComponentRenderer;

$config = $component['config'];
$style = $config['style'] ?? [];
$color = $style['color'] ?? '#6D0C6D';

$dimension = !empty($config['dimensions']) ? $config['dimensions'][0] : null;
$measure = !empty($config['measures']) ? $config['measures'][0] : null;
$aggregation = $config['aggregation'] ?? 'sum';

// Preparar datos para Chart.js
$chartData = [];
if ($dimension && $measure) {
    foreach ($data as $row) {
        $label = $row[$dimension] ?? 'Sin categoría';
        $value = $row[$measure] ?? 0;

        if (!isset($chartData[$label])) {
            $chartData[$label] = 0;
        }

        // Agregar según tipo
        switch ($aggregation) {
            case 'sum':
                $chartData[$label] += floatval($value);
                break;
            case 'count':
                $chartData[$label]++;
                break;
            case 'avg':
                // Para promedio necesitamos contar
                if (!isset($chartData[$label . '_count'])) {
                    $chartData[$label . '_count'] = 0;
                }
                $chartData[$label] += floatval($value);
                $chartData[$label . '_count']++;
                break;
        }
    }

    // Si es promedio, dividir
    if ($aggregation === 'avg') {
        foreach ($chartData as $key => $value) {
            if (strpos($key, '_count') === false) {
                $count = $chartData[$key . '_count'] ?? 1;
                $chartData[$key] = $value / $count;
                unset($chartData[$key . '_count']);
            }
        }
    }
}

// Ordenar por valor descendente y tomar top 10
arsort($chartData);
$chartData = array_slice($chartData, 0, 10, true);

$labels = array_keys($chartData);
$values = array_values($chartData);

// Determinar tipo de gráfico Chart.js
$chartType = 'line'; // default
switch ($component['type']) {
    case 'line_chart':
        $chartType = 'line';
        break;
    case 'bar_chart':
        $chartType = 'bar';
        break;
    case 'pie_chart':
        $chartType = 'pie';
        break;
}

// Theme class
$themeClass = $options['theme'] ?? 'default';
$containerClass = $options['containerClass'] ?? '';

// Etiquetas
$dimensionLabel = ComponentRenderer::getFieldLabel($component, $dimension ?? '');
$measureLabel = ComponentRenderer::getFieldLabel($component, $measure ?? '');
?>

<div class="component-chart <?= esc($themeClass) ?> <?= esc($containerClass) ?>"
     id="<?= esc($uniqueId) ?>"
     data-component-id="<?= esc($component['id']) ?>"
     data-component-type="chart"
     data-canvas-id="<?= esc($uniqueId) ?>_canvas"
     data-chart-type="<?= esc($chartType) ?>"
     data-chart-color="<?= esc($color) ?>"
     data-chart-labels="<?= esc(json_encode($labels)) ?>"
     data-chart-values="<?= esc(json_encode($values)) ?>"
     data-measure-label="<?= esc($measureLabel) ?>"
     data-chart-unit="<?= esc($style['unit'] ?? '') ?>">

    <!-- Título del componente -->
    <?php if (!empty($component['name'])): ?>
        <div class="chart-header">
            <h3 class="chart-title" style="color: <?= esc($color) ?>;">
                <?= esc($component['name']) ?>
            </h3>
            <?php if (!empty($component['description'])): ?>
                <p class="chart-description"><?= esc($component['description']) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Canvas del gráfico -->
    <div class="chart-container">
        <canvas id="<?= esc($uniqueId) ?>_canvas"></canvas>
    </div>

    <!-- Información adicional -->
    <div class="chart-footer">
        <small>
            <?= esc($measureLabel) ?> por <?= esc($dimensionLabel) ?>
            (Top 10)
        </small>
    </div>
</div>

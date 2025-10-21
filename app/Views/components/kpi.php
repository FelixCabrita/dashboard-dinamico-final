<?php
/**
 * Vista de Componente KPI
 * Template para renderizar tarjetas KPI con animación CountUp.js
 */

use App\Libraries\ComponentRenderer;

$config = $component['config'];
$style = $config['style'] ?? [];
$color = $style['color'] ?? '#6D0C6D';
$icon = $style['icon'] ?? '📊';
$unit = $style['unit'] ?? '';
$suffix = $style['suffix'] ?? '';

// Calcular valor KPI
$measure = !empty($config['measures']) ? $config['measures'][0] : null;
$aggregation = $config['aggregation'] ?? 'sum';

if ($measure) {
    $value = ComponentRenderer::calculateKPIValue($data, $measure, $aggregation);
} else {
    $value = count($data);
}

// Formatear valor
$displayValue = number_format($value, 0, ',', '.');

// Obtener etiqueta personalizada
$label = ComponentRenderer::getFieldLabel($component, $measure ?? 'total');

// Theme class
$themeClass = $options['theme'] ?? 'default';
$containerClass = $options['containerClass'] ?? '';
?>

<div class="component-kpi <?= esc($themeClass) ?> <?= esc($containerClass) ?>"
     id="<?= esc($uniqueId) ?>"
     data-component-id="<?= esc($component['id']) ?>"
     data-component-type="kpi">

    <div class="kpi-card" style="border-color: <?= esc($color) ?>;">

        <!-- Icono -->
        <div class="kpi-icon" style="color: <?= esc($color) ?>;">
            <?= esc($icon) ?>
        </div>

        <!-- Valor -->
        <div class="kpi-value">
            <span class="kpi-number"
                  id="<?= esc($uniqueId) ?>_value"
                  data-value="<?= esc($value) ?>"
                  data-unit="<?= esc($unit) ?>"
                  data-suffix="<?= esc($suffix) ?>">
                <?= esc($displayValue) ?>
            </span>
            <?php if ($unit): ?>
                <span class="kpi-unit"><?= esc($unit) ?></span>
            <?php endif; ?>
            <?php if ($suffix): ?>
                <span class="kpi-suffix"><?= esc($suffix) ?></span>
            <?php endif; ?>
        </div>

        <!-- Etiqueta -->
        <div class="kpi-label">
            <?= esc($label) ?>
        </div>

        <!-- Descripción opcional -->
        <?php if (!empty($component['description'])): ?>
            <div class="kpi-description">
                <?= esc($component['description']) ?>
            </div>
        <?php endif; ?>

        <!-- Indicador de tendencia (opcional) -->
        <?php if (isset($options['trend'])): ?>
            <div class="kpi-trend <?= $options['trend'] > 0 ? 'up' : 'down' ?>">
                <?= $options['trend'] > 0 ? '↑' : '↓' ?>
                <?= abs($options['trend']) ?>%
            </div>
        <?php endif; ?>

    </div>
</div>

<!-- Inicialización CountUp.js -->
<script>
(function() {
    const elementId = '<?= esc($uniqueId) ?>_value';
    const element = document.getElementById(elementId);

    if (element && typeof CountUp !== 'undefined') {
        const value = parseFloat(element.dataset.value);
        const unit = element.dataset.unit || '';
        const suffix = element.dataset.suffix || '';

        // Configurar CountUp
        const options = {
            duration: 2,
            separator: '.',
            decimal: ',',
            suffix: (unit ? ' ' + unit : '') + (suffix ? ' ' + suffix : '')
        };

        const countUp = new CountUp(elementId, value, options);

        if (!countUp.error) {
            countUp.start();
        } else {
            console.error('CountUp error:', countUp.error);
        }
    }
})();
</script>

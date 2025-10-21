<?php
/**
 * Vista de Componente Tabla
 * Template para renderizar tablas con DataTables
 */

use App\Libraries\ComponentRenderer;

$config = $component['config'];
$dimensions = $config['dimensions'] ?? [];
$measures = $config['measures'] ?? [];
$allColumns = array_merge($dimensions, $measures);

// Theme class
$themeClass = $options['theme'] ?? 'default';
$containerClass = $options['containerClass'] ?? '';
?>

<div class="component-table <?= esc($themeClass) ?> <?= esc($containerClass) ?>"
     id="<?= esc($uniqueId) ?>"
     data-component-id="<?= esc($component['id']) ?>"
     data-component-type="table"
     data-table-id="<?= esc($uniqueId) ?>_datatable">

    <!-- Título del componente -->
    <?php if (!empty($component['name'])): ?>
        <div class="table-header">
            <h3 class="table-title"><?= esc($component['name']) ?></h3>
            <?php if (!empty($component['description'])): ?>
                <p class="table-description"><?= esc($component['description']) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Tabla DataTable -->
    <div class="table-container">
        <table id="<?= esc($uniqueId) ?>_datatable" class="display responsive nowrap" style="width:100%">
            <thead>
                <tr>
                    <?php foreach ($allColumns as $column): ?>
                        <th><?= esc(ComponentRenderer::getFieldLabel($component, $column)) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                    <tr>
                        <?php foreach ($allColumns as $column): ?>
                            <td>
                                <?php
                                $value = $row[$column] ?? '-';

                                // Formatear números
                                if (is_numeric($value)) {
                                    echo number_format($value, 2, ',', '.');
                                } else {
                                    echo esc($value);
                                }
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Información adicional -->
    <div class="table-footer">
        <small>Total de registros: <?= count($data) ?></small>
    </div>
</div>

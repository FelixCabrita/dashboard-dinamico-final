<?php

namespace App\Libraries;

use App\Models\ComponentModel;

/**
 * ComponentRenderer
 * Motor de renderizado de componentes visuales
 *
 * Genera HTML + JS inline para cada tipo de componente
 */
class ComponentRenderer
{
    /**
     * ComponentModel
     *
     * @var ComponentModel
     */
    protected $componentModel;

    /**
     * QueryBuilder para obtener datos
     *
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * CacheManager
     *
     * @var CacheManager
     */
    protected $cache;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->componentModel = new ComponentModel();
        $this->queryBuilder = new QueryBuilder();
        $this->cache = new CacheManager();
    }

    /**
     * Renderizar un componente por su ID
     *
     * @param string $componentId ID del componente
     * @param array $filters Filtros adicionales a aplicar
     * @param array $options Opciones de renderizado (theme, containerClass, etc.)
     * @return string HTML generado
     */
    public function render(string $componentId, array $filters = [], array $options = []): string
    {
        // Cargar componente
        $component = $this->componentModel->find($componentId);

        if (!$component) {
            return $this->renderError('Componente no encontrado');
        }

        // Validar tipo
        if (!in_array($component['type'], ['kpi', 'table', 'line_chart', 'bar_chart', 'pie_chart'])) {
            return $this->renderError('Tipo de componente no válido');
        }

        // Obtener datos
        try {
            $data = $this->fetchComponentData($component, $filters);
        } catch (\Exception $e) {
            log_message('error', 'Error al obtener datos para componente ' . $componentId . ': ' . $e->getMessage());
            return $this->renderError('Error al cargar datos: ' . $e->getMessage());
        }

        // Delegar renderizado según tipo
        switch ($component['type']) {
            case 'kpi':
                return $this->renderKPI($component, $data, $options);

            case 'table':
                return $this->renderTable($component, $data, $options);

            case 'line_chart':
            case 'bar_chart':
            case 'pie_chart':
                return $this->renderChart($component, $data, $options);

            default:
                return $this->renderError('Tipo no implementado');
        }
    }

    /**
     * Obtener datos para un componente
     *
     * @param array $component Configuración del componente
     * @param array $filters Filtros adicionales
     * @return array Datos obtenidos
     */
    protected function fetchComponentData(array $component, array $filters = []): array
    {
        $config = $component['config'];
        $table = $config['data_source'] ?? null;

        if (!$table) {
            throw new \Exception('No se especificó una fuente de datos');
        }

        // Generar clave de caché
        $cacheKey = $this->cache->generateKey('component_data', [
            'component_id' => $component['id'],
            'filters' => $filters
        ]);

        // Intentar obtener de caché
        return $this->cache->remember($cacheKey, function() use ($config, $table, $filters) {

            // Preparar query
            $componentFilters = $config['filters'] ?? [];
            $dimensions = $config['dimensions'] ?? [];
            $measures = $config['measures'] ?? [];
            $allFields = array_merge($dimensions, $measures);

            // Construir query usando la API correcta de QueryBuilder
            $query = $this->queryBuilder->reset()->table($table);

            // SELECT fields
            if (!empty($allFields)) {
                $query->select($allFields);
            }

            // WHERE - Filtros del componente
            if (!empty($componentFilters)) {
                $query->applyFilters($componentFilters);
            }

            // WHERE - Filtros globales
            if (!empty($filters)) {
                $query->applyFilters($filters);
            }

            // GROUP BY
            if (!empty($dimensions)) {
                $query->groupBy($dimensions);
            }

            // LIMIT
            $query->limit(1000);

            // EXECUTE
            return $query->get();

        }, 300); // Caché de 5 minutos
    }

    /**
     * Renderizar componente KPI
     *
     * @param array $component Configuración del componente
     * @param array $data Datos del componente
     * @param array $options Opciones de renderizado
     * @return string HTML generado
     */
    protected function renderKPI(array $component, array $data, array $options): string
    {
        $viewData = [
            'component' => $component,
            'data' => $data,
            'options' => $options,
            'uniqueId' => 'kpi_' . uniqid()
        ];

        return view('components/kpi', $viewData);
    }

    /**
     * Renderizar componente Tabla
     *
     * @param array $component Configuración del componente
     * @param array $data Datos del componente
     * @param array $options Opciones de renderizado
     * @return string HTML generado
     */
    protected function renderTable(array $component, array $data, array $options): string
    {
        $viewData = [
            'component' => $component,
            'data' => $data,
            'options' => $options,
            'uniqueId' => 'table_' . uniqid()
        ];

        return view('components/table', $viewData);
    }

    /**
     * Renderizar componente Gráfico
     *
     * @param array $component Configuración del componente
     * @param array $data Datos del componente
     * @param array $options Opciones de renderizado
     * @return string HTML generado
     */
    protected function renderChart(array $component, array $data, array $options): string
    {
        $viewData = [
            'component' => $component,
            'data' => $data,
            'options' => $options,
            'uniqueId' => 'chart_' . uniqid()
        ];

        return view('components/chart', $viewData);
    }

    /**
     * Renderizar mensaje de error
     *
     * @param string $message Mensaje de error
     * @return string HTML de error
     */
    protected function renderError(string $message): string
    {
        return '<div class="component-error" style="padding: 20px; background: #fee; border: 2px solid #f66; border-radius: 8px; color: #c00;">
            <strong>Error:</strong> ' . esc($message) . '
        </div>';
    }

    /**
     * Obtener helper de etiquetas
     *
     * @param array $component Configuración del componente
     * @param string $field Campo a obtener etiqueta
     * @return string Etiqueta personalizada o nombre del campo
     */
    public static function getFieldLabel(array $component, string $field): string
    {
        $labels = $component['config']['labels'] ?? [];

        if (isset($labels[$field]) && !empty($labels[$field])) {
            return $labels[$field];
        }

        // Convertir snake_case a Title Case
        return ucwords(str_replace('_', ' ', $field));
    }

    /**
     * Calcular valor agregado para KPI
     *
     * @param array $data Datos del componente
     * @param string $measure Campo a agregar
     * @param string $aggregation Tipo de agregación
     * @return float Valor calculado
     */
    public static function calculateKPIValue(array $data, string $measure, string $aggregation = 'sum'): float
    {
        if (empty($data)) {
            return 0;
        }

        $values = array_column($data, $measure);
        $values = array_filter($values, 'is_numeric');

        if (empty($values)) {
            return 0;
        }

        switch ($aggregation) {
            case 'sum':
                return array_sum($values);

            case 'avg':
                return array_sum($values) / count($values);

            case 'min':
                return min($values);

            case 'max':
                return max($values);

            case 'count':
                return count($values);

            default:
                return array_sum($values);
        }
    }
}

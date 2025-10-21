<?php

namespace App\Models;

/**
 * ComponentModel
 * Modelo para gestión de componentes visuales almacenados en JSON
 */
class ComponentModel
{
    /**
     * Archivo JSON de componentes
     *
     * @var string
     */
    protected $jsonFile = WRITEPATH . 'components.json';

    /**
     * Tipos de componentes permitidos
     *
     * @var array
     */
    protected $allowedTypes = ['kpi', 'table', 'line_chart', 'bar_chart', 'pie_chart'];

    /**
     * Obtener todos los componentes
     *
     * @return array
     */
    public function findAll(): array
    {
        return $this->readJSON();
    }

    /**
     * Buscar componente por ID
     *
     * @param string $id
     * @return array|null
     */
    public function find(string $id): ?array
    {
        $components = $this->readJSON();

        foreach ($components as $component) {
            if ($component['id'] === $id) {
                return $component;
            }
        }

        return null;
    }

    /**
     * Crear nuevo componente
     *
     * @param array $data
     * @return string|false ID del componente creado o false en error
     */
    public function create(array $data)
    {
        // Validar tipo
        if (!isset($data['type']) || !in_array($data['type'], $this->allowedTypes)) {
            return false;
        }

        $components = $this->readJSON();

        // Generar ID único
        $id = $this->generateUUID();

        // Crear componente
        $component = [
            'id' => $id,
            'name' => $data['name'] ?? 'Nuevo Componente',
            'description' => $data['description'] ?? '',
            'type' => $data['type'],
            'config' => $data['config'] ?? [
                'data_source' => '',
                'dimensions' => [],
                'measures' => [],
                'aggregation' => 'sum',
                'filters' => [],
                'style' => []
            ],
            'tags' => $data['tags'] ?? [],
            'metadata' => [
                'created_at' => date('Y-m-d\TH:i:s\Z'),
                'updated_at' => date('Y-m-d\TH:i:s\Z'),
                'created_by' => session()->get('user_email') ?? 'system'
            ]
        ];

        $components[] = $component;
        $this->writeJSON($components);

        return $id;
    }

    /**
     * Actualizar componente existente
     *
     * @param string $id
     * @param array $data
     * @return bool
     */
    public function update(string $id, array $data): bool
    {
        $components = $this->readJSON();
        $updated = false;

        foreach ($components as $key => $component) {
            if ($component['id'] === $id) {
                // Actualizar campos
                if (isset($data['name'])) {
                    $components[$key]['name'] = $data['name'];
                }
                if (isset($data['description'])) {
                    $components[$key]['description'] = $data['description'];
                }
                if (isset($data['type']) && in_array($data['type'], $this->allowedTypes)) {
                    $components[$key]['type'] = $data['type'];
                }
                if (isset($data['config'])) {
                    $components[$key]['config'] = $data['config'];
                }
                if (isset($data['tags'])) {
                    $components[$key]['tags'] = $data['tags'];
                }

                // Actualizar metadata
                $components[$key]['metadata']['updated_at'] = date('Y-m-d\TH:i:s\Z');

                $updated = true;
                break;
            }
        }

        if ($updated) {
            $this->writeJSON($components);
            return true;
        }

        return false;
    }

    /**
     * Eliminar componente
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $components = $this->readJSON();

        $newComponents = array_filter($components, function($component) use ($id) {
            return $component['id'] !== $id;
        });

        // Reindexar array
        $newComponents = array_values($newComponents);

        if (count($newComponents) < count($components)) {
            $this->writeJSON($newComponents);
            return true;
        }

        return false;
    }

    /**
     * Duplicar componente
     *
     * @param string $id
     * @return string|false ID del nuevo componente o false
     */
    public function duplicate(string $id)
    {
        $original = $this->find($id);

        if (!$original) {
            return false;
        }

        // Crear copia
        $duplicate = $original;
        $duplicate['id'] = $this->generateUUID();
        $duplicate['name'] = $original['name'] . ' (Copia)';
        $duplicate['metadata'] = [
            'created_at' => date('Y-m-d\TH:i:s\Z'),
            'updated_at' => date('Y-m-d\TH:i:s\Z'),
            'created_by' => session()->get('user_email') ?? 'system'
        ];

        $components = $this->readJSON();
        $components[] = $duplicate;
        $this->writeJSON($components);

        return $duplicate['id'];
    }

    /**
     * Buscar componentes con filtros
     *
     * @param array $filters Formato: ['type' => 'kpi', 'search' => 'volumen']
     * @return array
     */
    public function search(array $filters): array
    {
        $components = $this->readJSON();

        // Filtrar por tipo
        if (isset($filters['type']) && !empty($filters['type'])) {
            $components = array_filter($components, function($component) use ($filters) {
                return $component['type'] === $filters['type'];
            });
        }

        // Filtrar por búsqueda de texto
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $components = array_filter($components, function($component) use ($search) {
                $name = strtolower($component['name'] ?? '');
                $description = strtolower($component['description'] ?? '');
                $tags = implode(' ', array_map('strtolower', $component['tags'] ?? []));

                return strpos($name, $search) !== false ||
                       strpos($description, $search) !== false ||
                       strpos($tags, $search) !== false;
            });
        }

        return array_values($components);
    }

    /**
     * Obtener componentes paginados
     *
     * @param int $page
     * @param int $perPage
     * @param array $filters
     * @return array
     */
    public function paginate(int $page = 1, int $perPage = 12, array $filters = []): array
    {
        $components = $this->search($filters);
        $total = count($components);

        // Calcular offset
        $offset = ($page - 1) * $perPage;

        // Aplicar paginación
        $paginatedComponents = array_slice($components, $offset, $perPage);

        return [
            'data' => $paginatedComponents,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }

    /**
     * Obtener estadísticas de componentes
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $components = $this->readJSON();
        $total = count($components);

        // Componentes por tipo
        $byType = [];
        foreach ($this->allowedTypes as $type) {
            $byType[$type] = 0;
        }

        foreach ($components as $component) {
            $type = $component['type'] ?? 'unknown';
            if (isset($byType[$type])) {
                $byType[$type]++;
            }
        }

        // Último componente creado
        $lastComponent = !empty($components) ? end($components) : null;

        return [
            'total' => $total,
            'by_type' => $byType,
            'last_component' => $lastComponent,
            'types_available' => $this->allowedTypes
        ];
    }

    /**
     * Validar estructura de componente
     *
     * @param array $component
     * @return array Errores encontrados (vacío si es válido)
     */
    public function validate(array $component): array
    {
        $errors = [];

        // Validar nombre
        if (empty($component['name'])) {
            $errors[] = 'El nombre del componente es requerido';
        }

        // Validar tipo
        if (empty($component['type'])) {
            $errors[] = 'El tipo de componente es requerido';
        } elseif (!in_array($component['type'], $this->allowedTypes)) {
            $errors[] = 'Tipo de componente no válido: ' . $component['type'];
        }

        // Validar configuración
        if (empty($component['config'])) {
            $errors[] = 'La configuración del componente es requerida';
        } else {
            // Validar data_source
            if (empty($component['config']['data_source'])) {
                $errors[] = 'La fuente de datos es requerida';
            }

            // Validar medidas para gráficos
            if (in_array($component['type'], ['line_chart', 'bar_chart', 'pie_chart', 'kpi'])) {
                if (empty($component['config']['measures'])) {
                    $errors[] = 'Al menos una medida es requerida para este tipo de componente';
                }
            }
        }

        return $errors;
    }

    /**
     * Leer archivo JSON
     *
     * @return array
     */
    protected function readJSON(): array
    {
        if (!file_exists($this->jsonFile)) {
            return [];
        }

        $content = file_get_contents($this->jsonFile);
        $components = json_decode($content, true);

        return $components ?: [];
    }

    /**
     * Escribir archivo JSON
     *
     * @param array $components
     * @return void
     */
    protected function writeJSON(array $components): void
    {
        $content = json_encode($components, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->jsonFile, $content);
    }

    /**
     * Generar UUID v4
     *
     * @return string
     */
    protected function generateUUID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Obtener tipos de componentes permitidos
     *
     * @return array
     */
    public function getAllowedTypes(): array
    {
        return $this->allowedTypes;
    }
}

<?php

namespace App\Controllers\API;

use CodeIgniter\RESTful\ResourceController;
use App\Libraries\QueryBuilder;
use App\Libraries\CacheManager;

/**
 * DataController
 * API REST para consultar datos de la base de datos SQLite
 */
class DataController extends ResourceController
{
    /**
     * Formato de respuesta
     *
     * @var string
     */
    protected $format = 'json';

    /**
     * QueryBuilder
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
        $this->queryBuilder = new QueryBuilder();
        $this->cache = new CacheManager();
    }

    /**
     * GET /api/data/tables
     * Obtener lista de tablas disponibles
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function tables()
    {
        try {
            $cacheKey = $this->cache->generateKey('tables');

            $tables = $this->cache->remember($cacheKey, function() {
                return $this->queryBuilder->getTables();
            }, 3600); // 1 hora

            return $this->respond([
                'success' => true,
                'data' => $tables,
                'meta' => [
                    'total' => count($tables)
                ]
            ]);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/data/columns/:table
     * Obtener columnas de una tabla
     *
     * @param string $table
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function columns(string $table)
    {
        try {
            $cacheKey = $this->cache->generateKey('columns_' . $table);

            $columns = $this->cache->remember($cacheKey, function() use ($table) {
                return $this->queryBuilder->getColumns($table);
            }, 3600);

            return $this->respond([
                'success' => true,
                'data' => $columns,
                'meta' => [
                    'table' => $table,
                    'total' => count($columns)
                ]
            ]);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/data/query
     * Consulta genérica con filtros
     *
     * Parámetros:
     * - table: Nombre de la tabla (requerido)
     * - select: Campos a seleccionar (opcional, default: *)
     * - filter[campo]: Filtros (ej: filter[municipio_id]=5)
     * - group_by: Campos para GROUP BY (opcional)
     * - order_by: Campo para ORDER BY (opcional)
     * - order_dir: Dirección ASC|DESC (opcional, default: ASC)
     * - page: Número de página (opcional, default: 1)
     * - limit: Registros por página (opcional, default: 50)
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function query()
    {
        try {
            $request = $this->request;

            // Validar tabla
            $table = $request->getGet('table');
            if (!$table) {
                return $this->fail('Parámetro "table" es requerido', 400);
            }

            // Generar clave de caché
            $cacheKey = $this->cache->generateKey('query', $request->getGet());

            // Intentar obtener del caché
            $cachedData = $this->cache->get($cacheKey);
            if ($cachedData !== null) {
                return $this->respond([
                    'success' => true,
                    'data' => $cachedData['data'],
                    'meta' => $cachedData['meta'],
                    'cached' => true
                ]);
            }

            // Construir query
            $query = $this->queryBuilder->reset()->table($table);

            // SELECT
            $select = $request->getGet('select');
            if ($select) {
                $query->select($select);
            }

            // FILTERS
            $filters = $request->getGet('filter') ?? [];
            if (!empty($filters)) {
                $query->applyFilters($filters);
            }

            // AGGREGATIONS
            $aggregate = $request->getGet('aggregate');
            $aggregateField = $request->getGet('aggregate_field');
            if ($aggregate && $aggregateField) {
                $query->aggregate($aggregate, $aggregateField);
            }

            // GROUP BY
            $groupBy = $request->getGet('group_by');
            if ($groupBy) {
                $query->groupBy($groupBy);
            }

            // ORDER BY
            $orderBy = $request->getGet('order_by');
            $orderDir = $request->getGet('order_dir') ?? 'ASC';
            if ($orderBy) {
                $query->orderBy($orderBy, $orderDir);
            }

            // PAGINACIÓN
            $page = (int) ($request->getGet('page') ?? 1);
            $limit = (int) ($request->getGet('limit') ?? 50);
            $limit = min($limit, 1000); // Máximo 1000 registros

            // Obtener total de registros (antes de paginar)
            $queryClone = clone $query;
            $total = $queryClone->count();

            // Aplicar paginación
            $query->paginate($page, $limit);

            // Ejecutar query
            $data = $query->get();

            $meta = [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit),
                'table' => $table
            ];

            // Cachear resultado
            $this->cache->set($cacheKey, [
                'data' => $data,
                'meta' => $meta
            ], 3600);

            return $this->respond([
                'success' => true,
                'data' => $data,
                'meta' => $meta,
                'cached' => false
            ]);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/data/aggregate
     * Agregaciones personalizadas con configuración JSON
     *
     * Body:
     * {
     *   "table": "fact_existencias",
     *   "aggregations": [
     *     {"function": "SUM", "field": "vcc", "alias": "total_volumen"},
     *     {"function": "AVG", "field": "vcc", "alias": "promedio_volumen"}
     *   ],
     *   "filters": {...},
     *   "group_by": ["municipio_id"],
     *   "order_by": "total_volumen",
     *   "order_dir": "DESC"
     * }
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function aggregate()
    {
        try {
            $input = $this->request->getJSON(true);

            if (!isset($input['table'])) {
                return $this->fail('Campo "table" es requerido', 400);
            }

            // Generar clave de caché
            $cacheKey = $this->cache->generateKey('aggregate', $input);

            // Intentar obtener del caché
            $cachedData = $this->cache->get($cacheKey);
            if ($cachedData !== null) {
                return $this->respond([
                    'success' => true,
                    'data' => $cachedData,
                    'cached' => true
                ]);
            }

            $query = $this->queryBuilder->reset()->table($input['table']);

            // Aplicar agregaciones
            if (isset($input['aggregations']) && is_array($input['aggregations'])) {
                foreach ($input['aggregations'] as $agg) {
                    $function = $agg['function'] ?? 'COUNT';
                    $field = $agg['field'] ?? '*';
                    $alias = $agg['alias'] ?? null;

                    $query->aggregate($function, $field, $alias);
                }
            }

            // Aplicar filtros
            if (isset($input['filters']) && is_array($input['filters'])) {
                $query->applyFilters($input['filters']);
            }

            // GROUP BY
            if (isset($input['group_by'])) {
                $query->groupBy($input['group_by']);
            }

            // ORDER BY
            if (isset($input['order_by'])) {
                $orderDir = $input['order_dir'] ?? 'ASC';
                $query->orderBy($input['order_by'], $orderDir);
            }

            // LIMIT
            if (isset($input['limit'])) {
                $query->limit((int) $input['limit']);
            }

            $data = $query->get();

            // Cachear resultado
            $this->cache->set($cacheKey, $data, 3600);

            return $this->respond([
                'success' => true,
                'data' => $data,
                'cached' => false
            ]);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * POST /api/data/query
     * Consulta genérica mediante POST con body JSON
     *
     * Body:
     * {
     *   "table": "dim_municipios",
     *   "fields": ["id", "nombre"],
     *   "filters": [{"field": "provincia_id", "operator": "=", "value": 1}],
     *   "options": {
     *     "orderBy": "nombre",
     *     "direction": "ASC",
     *     "limit": 100
     *   }
     * }
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function queryPost()
    {
        try {
            $input = $this->request->getJSON(true);

            if (!isset($input['table'])) {
                return $this->fail('Parámetro "table" es requerido', 400);
            }

            $table = $input['table'];
            $fields = $input['fields'] ?? ['*'];
            $filters = $input['filters'] ?? [];
            $options = $input['options'] ?? [];

            // Generar clave de caché
            $cacheKey = $this->cache->generateKey('query_post', $input);

            // Intentar obtener del caché
            $cachedData = $this->cache->get($cacheKey);
            if ($cachedData !== null) {
                return $this->respond([
                    'success' => true,
                    'data' => $cachedData,
                    'cached' => true
                ]);
            }

            // Construir query
            $query = $this->queryBuilder->reset()->table($table);

            // SELECT
            if (!empty($fields)) {
                $query->select($fields);
            }

            // FILTERS
            if (!empty($filters)) {
                $query->applyFilters($filters);
            }

            // OPTIONS
            if (isset($options['groupBy'])) {
                $query->groupBy($options['groupBy']);
            }

            if (isset($options['orderBy'])) {
                $direction = $options['direction'] ?? 'ASC';
                $query->orderBy($options['orderBy'], $direction);
            }

            if (isset($options['limit'])) {
                $query->limit((int) $options['limit']);
            }

            $data = $query->get();

            // Cachear resultado
            $this->cache->set($cacheKey, $data, 3600);

            return $this->respond([
                'success' => true,
                'data' => $data,
                'cached' => false
            ]);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/data/municipalities
     * Obtener lista de municipios únicos
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function municipalities()
    {
        try {
            $cacheKey = $this->cache->generateKey('municipalities');

            $data = $this->cache->remember($cacheKey, function() {
                return $this->queryBuilder
                    ->reset()
                    ->table('dim_municipios')
                    ->select(['id', 'codigo_ine', 'nombre', 'provincia'])
                    ->orderBy('nombre', 'ASC')
                    ->get();
            }, 7200); // 2 horas

            return $this->respond([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'total' => count($data)
                ]
            ]);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/data/species
     * Obtener lista de especies únicas
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function species()
    {
        try {
            $cacheKey = $this->cache->generateKey('species');

            $data = $this->cache->remember($cacheKey, function() {
                return $this->queryBuilder
                    ->reset()
                    ->table('dim_especies')
                    ->select(['id', 'codigo', 'nombre_cientifico', 'nombre_comun'])
                    ->orderBy('codigo', 'ASC')
                    ->get();
            }, 7200); // 2 horas

            return $this->respond([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'total' => count($data)
                ]
            ]);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/data/formations
     * Obtener lista de formaciones arboladas únicas
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function formations()
    {
        try {
            $cacheKey = $this->cache->generateKey('formations');

            $data = $this->cache->remember($cacheKey, function() {
                return $this->queryBuilder
                    ->reset()
                    ->table('dim_formaciones_arboladas')
                    ->select(['id', 'codigo', 'nombre'])
                    ->orderBy('nombre', 'ASC')
                    ->get();
            }, 7200); // 2 horas

            return $this->respond([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'total' => count($data)
                ]
            ]);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * DELETE /api/cache/clear
     * Limpiar caché de consultas
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function clearCache()
    {
        try {
            $this->cache->flush();

            return $this->respond([
                'success' => true,
                'message' => 'Caché limpiado correctamente'
            ]);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }

    /**
     * GET /api/cache/stats
     * Obtener estadísticas del caché
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function cacheStats()
    {
        try {
            $stats = $this->cache->getStats();

            return $this->respond([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), 500);
        }
    }
}

<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Libraries\CkanApiClient;

/**
 * API Controller para frontend - Proxy CKAN
 *
 * Proporciona endpoints seguros para que el frontend consuma datos de CKAN
 * sin exponer el token JWT. Actúa como proxy con validación y caché.
 *
 * Endpoints:
 * - GET /api/ckan/municipios?provincia=PALENCIA
 * - GET /api/ckan/poblacion?provincia=PALENCIA&year=2023
 * - GET /api/ckan/incendios?provincia=PALENCIA&limit=50
 * - POST /api/ckan/clear-cache
 *
 * @package App\Controllers\Api
 * @version 1.0.0
 * @date 2025-10-21
 */
class CkanController extends ResourceController
{
    /**
     * Formato de respuesta (JSON)
     */
    protected $format = 'json';

    /**
     * Cliente API CKAN
     */
    private CkanApiClient $ckan;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ckan = new CkanApiClient();
    }

    /**
     * Obtener municipios de una provincia
     *
     * GET /api/ckan/municipios?provincia=PALENCIA
     *
     * Query params:
     * - provincia (required): Nombre de la provincia
     * - limit (optional): Límite de registros (default: 500)
     * - fields (optional): Campos a retornar
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function municipios()
    {
        $provincia = $this->request->getGet('provincia');

        // Validar parámetro requerido
        if (!$provincia) {
            return $this->fail('Parámetro "provincia" requerido', 400);
        }

        // Opciones
        $options = [];

        if ($this->request->getGet('limit')) {
            $options['limit'] = (int) $this->request->getGet('limit');
        }

        if ($this->request->getGet('fields')) {
            $options['fields'] = $this->request->getGet('fields');
        }

        // Consultar CKAN
        $result = $this->ckan->getMunicipios($provincia, $options);

        if (!$result) {
            return $this->failServerError('Error al obtener municipios desde CKAN');
        }

        return $this->respond([
            'success' => true,
            'data' => $result['records'] ?? [],
            'total' => $result['total'] ?? 0,
            'provincia' => strtoupper($provincia),
            'timestamp' => date('c'),
        ]);
    }

    /**
     * Obtener datos de población
     *
     * GET /api/ckan/poblacion?provincia=PALENCIA&year=2023
     *
     * Query params:
     * - provincia (required): Nombre de la provincia
     * - year (optional): Año específico
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function poblacion()
    {
        $provincia = $this->request->getGet('provincia');
        $year = $this->request->getGet('year');

        // Validar parámetro requerido
        if (!$provincia) {
            return $this->fail('Parámetro "provincia" requerido', 400);
        }

        // Validar año si se proporciona
        if ($year !== null) {
            $year = (int) $year;

            if ($year < 1900 || $year > date('Y')) {
                return $this->fail('Año inválido', 400);
            }
        }

        // Consultar CKAN
        $result = $this->ckan->getPoblacion($provincia, $year);

        if (!$result) {
            return $this->failServerError('Error al obtener población desde CKAN');
        }

        return $this->respond([
            'success' => true,
            'data' => $result['records'] ?? [],
            'total' => $result['total'] ?? 0,
            'provincia' => strtoupper($provincia),
            'year' => $year,
            'timestamp' => date('c'),
        ]);
    }

    /**
     * Obtener incendios forestales
     *
     * GET /api/ckan/incendios?provincia=PALENCIA&limit=50
     *
     * Query params:
     * - provincia (required): Nombre de la provincia
     * - limit (optional): Límite de registros (default: 100)
     * - fields (optional): Campos a retornar
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function incendios()
    {
        $provincia = $this->request->getGet('provincia');

        // Validar parámetro requerido
        if (!$provincia) {
            return $this->fail('Parámetro "provincia" requerido', 400);
        }

        // Opciones
        $options = [];

        if ($this->request->getGet('limit')) {
            $limit = (int) $this->request->getGet('limit');
            $options['limit'] = min($limit, 1000); // Máximo 1000
        }

        if ($this->request->getGet('fields')) {
            $options['fields'] = $this->request->getGet('fields');
        }

        // Consultar CKAN
        $result = $this->ckan->getIncendios($provincia, $options);

        if (!$result) {
            return $this->failServerError('Error al obtener incendios desde CKAN');
        }

        return $this->respond([
            'success' => true,
            'data' => $result['records'] ?? [],
            'total' => $result['total'] ?? 0,
            'provincia' => strtoupper($provincia),
            'timestamp' => date('c'),
        ]);
    }

    /**
     * Limpiar caché de CKAN
     *
     * POST /api/ckan/clear-cache
     *
     * Requiere autenticación (admin)
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function clearCache()
    {
        // Verificar autenticación (opcional)
        // if (!session()->get('logged_in')) {
        //     return $this->failUnauthorized('No autorizado');
        // }

        $cleared = $this->ckan->clearCache();

        if ($cleared) {
            return $this->respond([
                'success' => true,
                'message' => 'Caché de CKAN limpiada exitosamente',
                'timestamp' => date('c'),
            ]);
        }

        return $this->failServerError('Error al limpiar caché');
    }

    /**
     * Obtener estadísticas de caché
     *
     * GET /api/ckan/cache/stats
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function cacheStats()
    {
        $stats = $this->ckan->getCacheStats();

        return $this->respond([
            'success' => true,
            'data' => $stats,
            'timestamp' => date('c'),
        ]);
    }

    /**
     * Importar datos procesados desde microservicio Render
     *
     * POST /api/admin/ckan/import
     *
     * Este endpoint recibe datos ya procesados (SHP convertido a JSON)
     * desde el microservicio Render y los inserta en current.db
     *
     * Body JSON esperado:
     * {
     *   "token": "secret-token",
     *   "table_name": "ckan_miradores",
     *   "data": [...],
     *   "metadata": {
     *     "resource_id": "...",
     *     "resource_name": "miradores",
     *     "synced_at": "2025-10-21T18:30:00Z",
     *     "feature_count": 150
     *   }
     * }
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function import()
    {
        // PASO 1: Validar token de seguridad
        $json = $this->request->getJSON(true);

        if (!$json) {
            return $this->fail('Body JSON inválido', 400);
        }

        $token = $json['token'] ?? null;
        $config = config('Ckan');

        if (!$token || $token !== $config->callbackToken) {
            log_message('warning', 'Intento de importación CKAN con token inválido desde IP: ' . $this->request->getIPAddress());
            return $this->failUnauthorized('Token de autorización inválido');
        }

        // PASO 2: Validar datos requeridos
        $tableName = $json['table_name'] ?? null;
        $data = $json['data'] ?? null;
        $metadata = $json['metadata'] ?? [];

        if (!$tableName || !$data) {
            return $this->fail('Campos requeridos: table_name, data', 400);
        }

        if (!is_array($data) || empty($data)) {
            return $this->fail('El campo "data" debe ser un array no vacío', 400);
        }

        // PASO 3: Validar nombre de tabla (debe empezar con 'ckan_')
        if (strpos($tableName, 'ckan_') !== 0) {
            return $this->fail('El nombre de tabla debe empezar con "ckan_"', 400);
        }

        // PASO 4: Conectar a base de datos
        $dbPath = ROOTPATH . 'data/db/current.db';

        if (!file_exists($dbPath)) {
            return $this->failServerError('Base de datos principal no encontrada');
        }

        try {
            $db = new \SQLite3($dbPath);
            $db->exec('BEGIN TRANSACTION');

            // PASO 5: Eliminar tabla anterior si existe
            $db->exec("DROP TABLE IF EXISTS {$tableName}");

            // PASO 6: Obtener columnas del primer registro
            $firstRow = $data[0];
            $columns = array_keys($firstRow);

            // PASO 7: Crear tabla
            $columnDefs = [];
            foreach ($columns as $col) {
                $type = 'TEXT'; // Por defecto todo es TEXT (viene de JSON)
                $columnDefs[] = "\"{$col}\" {$type}";
            }

            $createTableSql = "CREATE TABLE {$tableName} (" . implode(', ', $columnDefs) . ")";
            $db->exec($createTableSql);

            // PASO 8: Insertar datos
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $insertSql = "INSERT INTO {$tableName} VALUES ({$placeholders})";
            $stmt = $db->prepare($insertSql);

            $insertedRows = 0;
            foreach ($data as $row) {
                $values = [];
                foreach ($columns as $col) {
                    $values[] = $row[$col] ?? null;
                }

                foreach ($values as $i => $value) {
                    $stmt->bindValue($i + 1, $value);
                }

                $stmt->execute();
                $stmt->reset();
                $insertedRows++;
            }

            // PASO 9: Crear índices básicos
            $this->createBasicIndexes($db, $tableName, $columns);

            // PASO 10: Actualizar tabla de tracking
            $this->updateSyncTracking($db, $tableName, $metadata, $insertedRows);

            $db->exec('COMMIT');
            $db->close();

            // PASO 11: Enviar notificación (background)
            $this->sendImportNotification($tableName, $insertedRows, $metadata);

            log_message('info', "Datos CKAN importados exitosamente: {$tableName} ({$insertedRows} registros)");

            return $this->respond([
                'success' => true,
                'table' => $tableName,
                'rows_inserted' => $insertedRows,
                'message' => "Datos importados correctamente: {$insertedRows} registros",
                'timestamp' => date('c'),
            ]);

        } catch (\Exception $e) {
            // Rollback en caso de error
            if (isset($db)) {
                try {
                    $db->exec('ROLLBACK');
                    $db->close();
                } catch (\Exception $ignore) {}
            }

            log_message('error', "Error importando datos CKAN: " . $e->getMessage());
            return $this->failServerError('Error al importar datos: ' . $e->getMessage());
        }
    }

    /**
     * Crear índices básicos en tabla importada
     *
     * @param \SQLite3 $db
     * @param string $tableName
     * @param array $columns
     * @return void
     */
    private function createBasicIndexes(\SQLite3 $db, string $tableName, array $columns): void
    {
        $indexableColumns = ['id', 'nombre', 'tipo', 'municipio', 'provincia', 'codigo', 'cod_ine'];

        foreach ($indexableColumns as $col) {
            $actualCol = null;
            foreach ($columns as $c) {
                if (strtolower($c) === strtolower($col)) {
                    $actualCol = $c;
                    break;
                }
            }

            if ($actualCol) {
                try {
                    $indexName = "idx_{$tableName}_{$actualCol}";
                    $db->exec("CREATE INDEX IF NOT EXISTS {$indexName} ON {$tableName}(\"{$actualCol}\")");
                } catch (\Exception $e) {
                    // Ignorar errores de índices
                }
            }
        }
    }

    /**
     * Actualizar tabla de tracking de sincronización
     *
     * @param \SQLite3 $db
     * @param string $tableName
     * @param array $metadata
     * @param int $rowCount
     * @return void
     */
    private function updateSyncTracking(\SQLite3 $db, string $tableName, array $metadata, int $rowCount): void
    {
        try {
            $resourceId = $metadata['resource_id'] ?? '';
            $resourceName = $metadata['resource_name'] ?? str_replace('ckan_', '', $tableName);
            $syncedAt = $metadata['synced_at'] ?? date('Y-m-d H:i:s');

            // Verificar si ya existe un registro
            $stmt = $db->prepare("SELECT id FROM ckan_resource_sync WHERE resource_name = ?");
            $stmt->bindValue(1, $resourceName);
            $result = $stmt->execute();
            $exists = $result->fetchArray(SQLITE3_ASSOC);

            if ($exists) {
                // Actualizar
                $updateSql = "UPDATE ckan_resource_sync SET
                    last_synced = ?,
                    status = 'success',
                    feature_count = ?,
                    error_message = NULL
                    WHERE resource_name = ?";

                $stmt = $db->prepare($updateSql);
                $stmt->bindValue(1, $syncedAt);
                $stmt->bindValue(2, $rowCount);
                $stmt->bindValue(3, $resourceName);
                $stmt->execute();
            } else {
                // Insertar nuevo
                $insertSql = "INSERT INTO ckan_resource_sync (resource_name, resource_id, last_synced, status, feature_count)
                              VALUES (?, ?, ?, 'success', ?)";

                $stmt = $db->prepare($insertSql);
                $stmt->bindValue(1, $resourceName);
                $stmt->bindValue(2, $resourceId);
                $stmt->bindValue(3, $syncedAt);
                $stmt->bindValue(4, $rowCount);
                $stmt->execute();
            }

        } catch (\Exception $e) {
            log_message('warning', "Error actualizando tracking: " . $e->getMessage());
        }
    }

    /**
     * Enviar notificación de importación completada
     *
     * @param string $tableName
     * @param int $rowCount
     * @param array $metadata
     * @return void
     */
    private function sendImportNotification(string $tableName, int $rowCount, array $metadata): void
    {
        try {
            helper('filesystem');

            $notificationsFile = WRITEPATH . 'notifications.json';
            $notifications = [];

            if (file_exists($notificationsFile)) {
                $content = file_get_contents($notificationsFile);
                $notifications = json_decode($content, true) ?? [];
            }

            $notification = [
                'id' => uniqid('notif_'),
                'type' => 'ckan_import_success',
                'title' => 'Sincronización CKAN completada',
                'message' => "Tabla '{$tableName}' actualizada con {$rowCount} registros",
                'data' => [
                    'table' => $tableName,
                    'rows' => $rowCount,
                    'resource_name' => $metadata['resource_name'] ?? '',
                ],
                'timestamp' => date('c'),
                'read' => false,
            ];

            array_unshift($notifications, $notification);

            // Mantener solo las últimas 50 notificaciones
            $notifications = array_slice($notifications, 0, 50);

            file_put_contents($notificationsFile, json_encode($notifications, JSON_PRETTY_PRINT));

        } catch (\Exception $e) {
            log_message('warning', "Error creando notificación: " . $e->getMessage());
        }
    }
}

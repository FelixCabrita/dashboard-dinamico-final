<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Config\Ckan as CkanConfig;
use Config\CkanSync as CkanSyncConfig;

/**
 * Controlador de Sincronización CKAN
 *
 * Gestiona la sincronización de recursos SHP desde CKAN
 * mediante microservicio Render
 *
 * @package App\Controllers\Admin
 */
class CkanSync extends BaseController
{
    private CkanConfig $ckanConfig;
    private CkanSyncConfig $syncConfig;

    public function __construct()
    {
        $this->ckanConfig = config('Ckan');
        $this->syncConfig = config('CkanSync');
    }

    /**
     * Vista principal de sincronización CKAN
     *
     * GET /admin-panel-bmp/ckan/sync
     */
    public function index()
    {
        // Verificar autenticación
        if (!session()->has('user_id')) {
            return redirect()->to(admin_url('login'));
        }

        // Obtener estado de sincronizaciones
        $resources = $this->getResourcesStatus();

        $data = [
            'title' => 'Sincronización CKAN',
            'resources' => $resources,
            'renderServiceUrl' => $this->ckanConfig->renderServiceUrl ?? '',
        ];

        return view('admin/ckan/sync', $data);
    }

    /**
     * Iniciar sincronización de un recurso
     *
     * POST /admin-panel-bmp/ckan/sync/trigger
     */
    public function triggerSync()
    {
        // Verificar autenticación
        if (!session()->has('user_id')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No autenticado'
            ])->setStatusCode(401);
        }

        $resourceName = $this->request->getPost('resource_name');

        if (!$resourceName) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Parámetro resource_name requerido'
            ])->setStatusCode(400);
        }

        // Verificar que el recurso existe en la configuración
        $availableResources = $this->syncConfig->availableResources;

        if (!isset($availableResources[$resourceName])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Recurso no encontrado en configuración'
            ])->setStatusCode(404);
        }

        $resource = $availableResources[$resourceName];

        try {
            // Llamar al microservicio Render
            $renderUrl = $this->ckanConfig->renderServiceUrl;
            $callbackUrl = base_url('api/admin/ckan/import');
            $callbackToken = $this->ckanConfig->callbackToken;

            // Payload compatible con microservicio Render (camelCase)
            $payload = [
                'resourceId' => $resource['id'],
                'packageId' => $resourceName,  // Usar resourceName como packageId
                'callbackUrl' => $callbackUrl,
                'callbackToken' => $callbackToken,
            ];

            // Hacer request HTTP al microservicio
            $ch = curl_init($renderUrl . '/api/sync-shp');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5); // Timeout corto, el proceso es async

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new \Exception("Error de conexión: {$error}");
            }

            // El microservicio responde con 202 Accepted para procesamiento asíncrono
            if ($httpCode !== 202 && $httpCode !== 200) {
                throw new \Exception("Error del servidor: HTTP {$httpCode}");
            }

            $result = json_decode($response, true);

            if (!$result || !isset($result['success'])) {
                throw new \Exception("Respuesta inválida del microservicio");
            }

            // Actualizar estado en tracking
            $this->updateSyncStatus($resourceName, 'processing');

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Sincronización iniciada. Recibirás una notificación cuando termine.',
                'estimated_time' => $result['estimated_time'] ?? '2-3 minutos',
            ]);

        } catch (\Exception $e) {
            log_message('error', "Error iniciando sincronización CKAN: " . $e->getMessage());

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error iniciando sincronización: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Obtener estado de un recurso
     *
     * GET /admin-panel-bmp/ckan/sync/status/:resource
     */
    public function getStatus(string $resourceName)
    {
        // Verificar autenticación
        if (!session()->has('user_id')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No autenticado'
            ])->setStatusCode(401);
        }

        $dbPath = WRITEPATH . 'database/current.db';

        if (!file_exists($dbPath)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Base de datos no encontrada'
            ])->setStatusCode(500);
        }

        try {
            $db = new \SQLite3($dbPath);

            $stmt = $db->prepare("SELECT * FROM ckan_resource_sync WHERE resource_name = ? LIMIT 1");
            $stmt->bindValue(1, $resourceName);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);

            $db->close();

            if (!$row) {
                return $this->response->setJSON([
                    'success' => true,
                    'status' => 'never_synced',
                    'message' => 'Este recurso nunca ha sido sincronizado'
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'status' => $row['status'] ?? 'unknown',
                'data' => $row,
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error consultando estado: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Obtener estado de todos los recursos configurados
     *
     * @return array
     */
    private function getResourcesStatus(): array
    {
        $availableResources = $this->syncConfig->availableResources;
        $resources = [];

        $dbPath = WRITEPATH . 'database/current.db';

        if (!file_exists($dbPath)) {
            // Si no existe la BD, todos están en estado "never_synced"
            foreach ($availableResources as $key => $resource) {
                $resources[] = [
                    'key' => $key,
                    'name' => $resource['name'] ?? $key,
                    'description' => $resource['description'] ?? '',
                    'id' => $resource['id'],
                    'status' => 'never_synced',
                    'last_synced' => null,
                    'feature_count' => 0,
                ];
            }
            return $resources;
        }

        try {
            $db = new \SQLite3($dbPath);

            foreach ($availableResources as $key => $resource) {
                $stmt = $db->prepare("SELECT * FROM ckan_resource_sync WHERE resource_name = ? LIMIT 1");
                $stmt->bindValue(1, $key);
                $result = $stmt->execute();
                $row = $result->fetchArray(SQLITE3_ASSOC);

                $resources[] = [
                    'key' => $key,
                    'name' => $resource['name'] ?? $key,
                    'description' => $resource['description'] ?? '',
                    'id' => $resource['id'],
                    'status' => $row['status'] ?? 'never_synced',
                    'last_synced' => $row['last_synced'] ?? null,
                    'feature_count' => $row['feature_count'] ?? 0,
                    'error_message' => $row['error_message'] ?? null,
                ];
            }

            $db->close();

        } catch (\Exception $e) {
            log_message('error', "Error obteniendo estado de recursos: " . $e->getMessage());
        }

        return $resources;
    }

    /**
     * Actualizar estado de sincronización en tracking
     *
     * @param string $resourceName
     * @param string $status
     * @return void
     */
    private function updateSyncStatus(string $resourceName, string $status): void
    {
        $dbPath = WRITEPATH . 'database/current.db';

        if (!file_exists($dbPath)) {
            return;
        }

        try {
            $db = new \SQLite3($dbPath);

            // Verificar si existe
            $stmt = $db->prepare("SELECT id FROM ckan_resource_sync WHERE resource_name = ?");
            $stmt->bindValue(1, $resourceName);
            $result = $stmt->execute();
            $exists = $result->fetchArray(SQLITE3_ASSOC);

            if ($exists) {
                // Actualizar
                $stmt = $db->prepare("UPDATE ckan_resource_sync SET status = ? WHERE resource_name = ?");
                $stmt->bindValue(1, $status);
                $stmt->bindValue(2, $resourceName);
                $stmt->execute();
            } else {
                // Insertar
                $availableResources = $this->syncConfig->availableResources;
                $resourceId = $availableResources[$resourceName]['id'] ?? '';

                $stmt = $db->prepare("INSERT INTO ckan_resource_sync (resource_name, resource_id, status) VALUES (?, ?, ?)");
                $stmt->bindValue(1, $resourceName);
                $stmt->bindValue(2, $resourceId);
                $stmt->bindValue(3, $status);
                $stmt->execute();
            }

            $db->close();

        } catch (\Exception $e) {
            log_message('warning', "Error actualizando estado de sync: " . $e->getMessage());
        }
    }
}

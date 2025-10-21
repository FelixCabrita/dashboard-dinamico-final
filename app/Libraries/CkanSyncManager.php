<?php

namespace App\Libraries;

use Config\CkanSync as CkanSyncConfig;
use App\Libraries\CkanApiClient;
use App\Libraries\ShpProcessor;
use CodeIgniter\Database\ConnectionInterface;

/**
 * Gestor de Sincronización de Recursos CKAN
 *
 * Descarga, procesa y sincroniza recursos SHP desde CKAN
 * convirtiéndolos a SQLite y GeoJSON para consulta local.
 *
 * Arquitectura:
 * 1. Obtener lista de recursos desde CKAN
 * 2. Verificar si requiere actualización (comparar last_modified)
 * 3. Descargar archivo ZIP
 * 4. Extraer archivos SHP
 * 5. Procesar con ogr2ogr → SQLite + GeoJSON
 * 6. Actualizar registro de sincronización
 * 7. Limpiar archivos temporales
 * 8. Notificar si hay errores
 *
 * @package App\Libraries
 * @version 1.0.0
 * @date 2025-10-21
 */
class CkanSyncManager
{
    private CkanSyncConfig $config;
    private CkanApiClient $ckan;
    private ShpProcessor $shpProcessor;
    private ConnectionInterface $db;
    private array $stats = [];

    public function __construct()
    {
        $this->config = config('CkanSync');
        $this->ckan = new CkanApiClient();
        $this->shpProcessor = new ShpProcessor();
        $this->db = \Config\Database::connect();

        // Crear directorios si no existen
        $this->createDirectories();

        // Inicializar estadísticas
        $this->stats = [
            'processed' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
            'start_time' => microtime(true)
        ];
    }

    /**
     * Crear estructura de directorios necesaria
     *
     * @return void
     */
    private function createDirectories(): void
    {
        $dirs = [
            $this->config->baseDir,
            $this->config->sqliteDir,
            $this->config->geojsonDir,
            $this->config->tempDir,
            $this->config->tempDir . '/downloads',
            $this->config->logDir
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                $this->log('info', "Directorio creado: {$dir}");
            }
        }
    }

    /**
     * Sincronizar todos los recursos prioritarios
     *
     * @return array Estadísticas de la sincronización
     */
    public function syncAll(): array
    {
        $this->log('info', '=== Iniciando sincronización de recursos SHP ===');

        foreach ($this->config->priorityResources as $name => $resourceId) {
            try {
                $this->log('info', "Procesando recurso: {$name} ({$resourceId})");
                $this->syncResource($name, $resourceId);
                $this->stats['processed']++;
            } catch (\Exception $e) {
                $this->stats['errors']++;
                $this->log('error', "Error en {$name}: " . $e->getMessage());
            }
        }

        $this->stats['duration'] = microtime(true) - $this->stats['start_time'];
        $this->log('info', '=== Sincronización completada ===');
        $this->log('info', 'Estadísticas: ' . json_encode($this->stats));

        // Enviar notificación si hay errores
        if ($this->stats['errors'] > 0) {
            $this->sendErrorNotification();
        }

        return $this->stats;
    }

    /**
     * Sincronizar un recurso específico
     *
     * @param string $name Nombre interno del recurso
     * @param string $resourceId UUID del recurso en CKAN
     * @param bool $force Forzar sincronización aunque esté actualizado
     * @return bool
     * @throws \RuntimeException Si falla la sincronización
     */
    public function syncResource(string $name, string $resourceId, bool $force = false): bool
    {
        // 1. Obtener metadata del recurso desde CKAN
        $this->log('info', "Obteniendo metadata del recurso {$resourceId}...");
        $resource = $this->ckan->getResource($resourceId);

        if (!$resource) {
            throw new \RuntimeException("No se pudo obtener metadata del recurso {$resourceId}");
        }

        // 2. Verificar si necesita actualización
        if (!$force && $this->isUpToDate($name, $resource)) {
            $this->log('info', "Recurso {$name} está actualizado, omitiendo...");
            $this->stats['skipped']++;
            return false;
        }

        // 3. Descargar archivo ZIP
        $this->log('info', "Descargando archivo...");
        $zipPath = $this->downloadResource($resource);

        // 4. Extraer archivos SHP
        $this->log('info', "Extrayendo archivos SHP...");
        $shpPath = $this->extractZip($zipPath);

        // 5. Procesar SHP y guardar en SQLite
        $sqlitePath = $this->config->sqliteDir . "/shp_{$name}.db";
        $this->log('info', "Procesando SHP → SQLite: {$sqlitePath}");
        $this->shpProcessor->processToSqlite($shpPath, $sqlitePath, $name);

        // 6. Generar GeoJSON si está habilitado
        if ($this->config->generateGeoJSON) {
            $geojsonPath = $this->config->geojsonDir . "/{$name}.geojson";
            $this->log('info', "Generando GeoJSON: {$geojsonPath}");
            $this->shpProcessor->convertToGeoJSON($shpPath, $geojsonPath);
        }

        // 7. Actualizar registro de sincronización
        $this->updateSyncRecord($name, $resource);

        // 8. Limpiar archivos temporales
        $this->cleanup($zipPath, dirname($shpPath));

        $this->stats['updated']++;
        $this->log('info', "Recurso {$name} sincronizado exitosamente ✓");

        return true;
    }

    /**
     * Verificar si el recurso está actualizado
     *
     * @param string $name Nombre interno del recurso
     * @param array $resource Metadata del recurso desde CKAN
     * @return bool
     */
    private function isUpToDate(string $name, array $resource): bool
    {
        $query = $this->db->table('ckan_resource_sync')
            ->where('resource_name', $name)
            ->get();

        if ($query->getNumRows() === 0) {
            return false; // No existe, necesita sincronización
        }

        $sync = $query->getRow();

        // Comparar fecha de última modificación
        if (isset($resource['last_modified'])) {
            $lastModified = strtotime($resource['last_modified']);
            $lastSync = strtotime($sync->last_synced);

            if ($lastModified > $lastSync) {
                return false; // Hay cambios en CKAN
            }
        }

        // Verificar frecuencia mínima de sincronización
        $daysSinceSync = (time() - strtotime($sync->last_synced)) / 86400;

        if ($daysSinceSync > $this->config->syncFrequencyDays) {
            $this->log('info', "Forzando resincronización: {$daysSinceSync} días desde última sync");
            return false;
        }

        return true; // Está actualizado
    }

    /**
     * Descargar recurso desde CKAN
     *
     * @param array $resource Metadata del recurso
     * @return string Ruta al archivo descargado
     * @throws \RuntimeException Si falla la descarga
     */
    private function downloadResource(array $resource): string
    {
        $url = $resource['url'];
        $filename = basename(parse_url($url, PHP_URL_PATH));

        // Si no hay filename válido, generar uno
        if (empty($filename) || strlen($filename) < 3) {
            $filename = $resource['id'] . '.zip';
        }

        $destination = $this->config->tempDir . '/downloads/' . $filename;

        $this->log('info', "Descargando desde: {$url}");

        $ch = curl_init($url);
        $fp = fopen($destination, 'w');

        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->config->downloadTimeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . config('Ckan')->apiToken
            ]
        ]);

        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        fclose($fp);

        if (!$success || $httpCode !== 200) {
            unlink($destination);
            throw new \RuntimeException("Error al descargar recurso: HTTP {$httpCode}");
        }

        $size = filesize($destination);
        $this->log('info', "Descarga completada: " . number_format($size / 1024, 2) . " KB");

        return $destination;
    }

    /**
     * Extraer archivo ZIP y encontrar archivo SHP
     *
     * @param string $zipPath Ruta al archivo ZIP
     * @return string Ruta al archivo .shp encontrado
     * @throws \RuntimeException Si no se encuentra archivo SHP
     */
    private function extractZip(string $zipPath): string
    {
        $extractDir = dirname($zipPath) . '/' . pathinfo($zipPath, PATHINFO_FILENAME);

        if (!is_dir($extractDir)) {
            mkdir($extractDir, 0755, true);
        }

        $zip = new \ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("No se pudo abrir ZIP: {$zipPath}");
        }

        $zip->extractTo($extractDir);
        $zip->close();

        $this->log('info', "Archivos extraídos en: {$extractDir}");

        // Buscar archivo .shp (puede estar en subdirectorios)
        $shpFile = $this->findFileRecursive($extractDir, '*.shp');

        if (!$shpFile) {
            throw new \RuntimeException("No se encontró archivo .shp en el ZIP");
        }

        $this->log('info', "Archivo SHP encontrado: " . basename($shpFile));

        return $shpFile;
    }

    /**
     * Buscar archivo recursivamente en un directorio
     *
     * @param string $dir Directorio base
     * @param string $pattern Patrón de archivo (ej: *.shp)
     * @return string|null Ruta al archivo encontrado o null
     */
    private function findFileRecursive(string $dir, string $pattern): ?string
    {
        $files = glob($dir . '/' . $pattern);

        if (!empty($files)) {
            return $files[0];
        }

        // Buscar en subdirectorios
        $subdirs = glob($dir . '/*', GLOB_ONLYDIR);

        foreach ($subdirs as $subdir) {
            $found = $this->findFileRecursive($subdir, $pattern);
            if ($found) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Actualizar registro de sincronización en BD
     *
     * @param string $name Nombre interno del recurso
     * @param array $resource Metadata del recurso
     * @return void
     */
    private function updateSyncRecord(string $name, array $resource): void
    {
        $data = [
            'resource_name' => $name,
            'resource_id' => $resource['id'],
            'last_modified' => $resource['last_modified'] ?? null,
            'last_synced' => date('Y-m-d H:i:s'),
            'file_size' => $resource['size'] ?? null,
            'format' => $resource['format'] ?? 'SHP',
            'status' => 'success',
            'error_message' => null,
        ];

        $existing = $this->db->table('ckan_resource_sync')
            ->where('resource_name', $name)
            ->get()
            ->getRow();

        if ($existing) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->db->table('ckan_resource_sync')
                ->where('resource_name', $name)
                ->update($data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->db->table('ckan_resource_sync')->insert($data);
        }
    }

    /**
     * Limpiar archivos temporales
     *
     * @param string $zipPath Ruta al ZIP descargado
     * @param string $extractDir Directorio de extracción
     * @return void
     */
    private function cleanup(string $zipPath, string $extractDir): void
    {
        // Eliminar ZIP
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        // Eliminar directorio de extracción
        if (is_dir($extractDir)) {
            $this->deleteDirectory($extractDir);
        }

        $this->log('debug', "Archivos temporales limpiados");
    }

    /**
     * Eliminar directorio recursivamente
     *
     * @param string $dir Directorio a eliminar
     * @return void
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    /**
     * Logging con niveles
     *
     * @param string $level Nivel: error, warning, info, debug
     * @param string $message Mensaje
     * @return void
     */
    private function log(string $level, string $message): void
    {
        // Filtrar por nivel configurado
        $levels = ['error' => 0, 'warning' => 1, 'info' => 2, 'debug' => 3];
        $currentLevel = $levels[$this->config->logLevel] ?? 2;
        $messageLevel = $levels[$level] ?? 2;

        if ($messageLevel > $currentLevel) {
            return; // No loguear
        }

        // Log a archivo
        $logFile = $this->config->logDir . '/sync_' . date('Y_m_d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $line = "[{$timestamp}] [{$level}] {$message}\n";

        file_put_contents($logFile, $line, FILE_APPEND);

        // Log a sistema de CI4 si es error
        if ($level === 'error') {
            log_message('error', $message);
        }
    }

    /**
     * Enviar notificación de errores por email
     *
     * @return void
     */
    private function sendErrorNotification(): void
    {
        try {
            $email = \Config\Services::email();

            $email->setTo($this->config->notificationEmail);
            $email->setSubject('CKAN Sync - Errores en sincronización');
            $email->setMessage(
                "Se encontraron {$this->stats['errors']} errores durante la sincronización.\n\n" .
                "Estadísticas completas:\n" .
                json_encode($this->stats, JSON_PRETTY_PRINT) . "\n\n" .
                "Revisar logs en: {$this->config->logDir}\n\n" .
                "Fecha: " . date('Y-m-d H:i:s')
            );

            $email->send();

            $this->log('info', "Notificación de error enviada a {$this->config->notificationEmail}");

        } catch (\Exception $e) {
            $this->log('error', "Error enviando notificación: " . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas de la última sincronización
     *
     * @return array
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}

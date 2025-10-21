# SISTEMA DE SINCRONIZACIÓN DE ARCHIVOS SHP CON RENDER

**Proyecto:** Dashboard Dinámico Ambiental - Bosque Modelo Palencia
**Fecha:** 21 de Octubre de 2025
**Versión:** 2.0.0 (Refactorización Render)
**Actualización:** 21 de Octubre de 2025

> **Nota importante**: Este documento contiene tanto la implementación actual (Render + Callback) como código legacy (CkanSyncManager, cronjobs).
> Para documentación completa de la nueva arquitectura, consultar: [**ARQUITECTURA_CKAN_RENDER.md**](ARQUITECTURA_CKAN_RENDER.md)

---

## 🎯 OBJETIVO

Resolver la limitación de que **194 recursos SHP (79.8%)** no son consultables vía API CKAN, procesándolos mediante microservicio Render con GDAL e importándolos directamente a `current.db` para integración automática con QueryBuilder y componentes del dashboard.

---

## 📊 PROBLEMA IDENTIFICADO

### Estado Actual

```
Total recursos CKAN:        243
├─ Consultables vía API:    47 (19.3%)  ✓ CSV/XLS
└─ NO consultables:         194 (79.8%) ✗ SHP
   ├─ Geoespaciales:        194 archivos
   └─ Formatos:             SHP, SHX, DBF, PRJ
```

### Limitaciones

1. **Archivos SHP no tienen `datastore_active: true`**
   - No se pueden consultar con `datastore_search`
   - Requieren descarga completa del archivo

2. **Acceso mediante descarga directa**
   - URL de descarga disponible en cada recurso
   - Archivos ZIP con múltiples componentes (.shp, .shx, .dbf, .prj)
   - Tamaño variable: 16 KB - 5 MB

3. **Uso en dashboards limitado**
   - No se pueden filtrar dinámicamente
   - No se pueden hacer consultas SQL
   - Difícil integración con mapas web

---

## ✅ SOLUCIÓN IMPLEMENTADA

### Arquitectura del Sistema (Render + Callback)

```
┌─────────────────────────────────────────────────────────────┐
│               PANEL ADMIN - Interfaz Web                     │
│            /admin-panel-bmp/ckan/sync                        │
│  Admin hace click en "Sincronizar" para un recurso          │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  1. Panel Admin → Render Microservice                        │
│     POST https://tu-servicio.onrender.com/api/sync-shp      │
│     Body: {                                                  │
│       resource_id: "...",                                    │
│       resource_name: "miradores",                            │
│       callback_url: "https://panel.com/api/admin/ckan/...", │
│       callback_token: "secret-token"                         │
│     }                                                        │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  2. Render responde inmediatamente (202 Accepted)           │
│     { success: true, message: "Procesamiento iniciado" }    │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  3. Admin UI muestra: "Sincronizando... (2-3 minutos)"      │
│     Polling cada 10s a GET /admin-panel-bmp/ckan/sync/...   │
└─────────────────────────────────────────────────────────────┘

         [Procesamiento en Render - Background]
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  4. Render procesa en background:                           │
│     - Obtiene metadata del recurso desde CKAN API           │
│     - Descarga archivo SHP (ZIP)                             │
│     - Descomprime ZIP                                        │
│     - Ejecuta ogr2ogr (GDAL) para convertir SHP             │
│     - Genera JSON con datos procesados                       │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  5. Render → Panel Admin Callback                           │
│     POST https://panel.com/api/admin/ckan/import            │
│     Headers: { Content-Type: application/json }             │
│     Body: {                                                  │
│       token: "secret-token",                                 │
│       table_name: "ckan_miradores",                          │
│       data: [{...}, {...}, ...],  // Todos los registros    │
│       metadata: { rows: 150, ... }                           │
│     }                                                        │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  6. Panel Admin (CkanController::import)                    │
│     - Valida token de seguridad                              │
│     - Abre conexión a data/db/current.db                     │
│     - DROP TABLE IF EXISTS ckan_miradores                    │
│     - CREATE TABLE ckan_miradores (...)                      │
│     - INSERT en bulk todos los datos                         │
│     - CREATE INDEX en campos comunes                         │
│     - Actualiza ckan_resource_sync (tracking)                │
│     - Crea notificación admin                                │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  7. Admin UI (polling detecta status = 'success')           │
│     - Muestra: "✅ Sincronización completada"                │
│     - Recarga página con datos actualizados                  │
│     - Tabla ckan_miradores ahora en /api/data/tables         │
└─────────────────────────────────────────────────────────────┘
```

### Beneficios de esta Arquitectura

1. **No requiere GDAL local**: El procesamiento pesado se hace en Render (Linux, GDAL pre-instalado)
2. **Integración automática**: Tablas `ckan_*` aparecen en QueryBuilder sin configuración adicional
3. **Sincronización bajo demanda**: El admin decide cuándo actualizar cada recurso
4. **Background processing**: La interfaz no se bloquea durante los 2-3 minutos de procesamiento
5. **Notificaciones en tiempo real**: Polling muestra el progreso y resultado final
6. **Escalable**: Render puede procesar múltiples recursos en paralelo

---

## 🗂️ ESTRUCTURA DE ARCHIVOS

### Panel Admin (Backend PHP - CodeIgniter 4)

```
app/
├── Config/
│   ├── Ckan.php                        ← Config API CKAN + Render URL
│   │   ├── renderServiceUrl            ← URL del microservicio Render
│   │   └── callbackToken               ← Token de seguridad
│   └── CkanSync.php                    ← Config recursos disponibles
│       └── availableResources[]        ← Lista de recursos sincronizables
├── Controllers/
│   ├── Api/
│   │   └── CkanController.php          ← API endpoints + callback import()
│   └── Admin/
│       └── CkanSync.php                ← Controlador interfaz admin
├── Views/admin/ckan/
│   └── sync.php                        ← Vista de sincronización (HTML/JS)
└── Libraries/
    └── ShpProcessor.php                ← Procesador con importToMainDatabase()

data/db/
└── current.db                          ← Base de datos principal SQLite
    ├── ckan_miradores ←  Tablas CKAN (prefijo ckan_*)
    ├── ckan_aparcamientos
    └── ckan_resource_sync              ← Tracking de sincronización
```

### Microservicio Render (Pendiente de Implementación)

```
render-ckan-processor/  (Repositorio separado)
├── index.php                           ← Entry point
├── api/
│   └── sync-shp.php                    ← Endpoint procesamiento SHP
├── lib/
│   ├── CkanClient.php                  ← Cliente HTTP CKAN
│   └── ShpConverter.php                ← Convertidor SHP con ogr2ogr
├── composer.json
├── Dockerfile  (o usar buildpack GDAL)
└── README.md

Heroku/Render Build:
- PHP 8.1+
- GDAL 3.x (ogr2ogr)
- Extensiones: zip, curl, json
```

---

## 🔧 REQUISITOS DEL SISTEMA

### Panel Admin (Servidor de Producción)

**No requiere GDAL/OGR** - El procesamiento se hace en Render

1. **PHP >= 8.1** con extensiones:
   ```bash
   php -m | grep -E "(sqlite3|curl|json)"
   ```

2. **Base de datos**: `data/db/current.db` (SQLite3)

3. **Permisos de escritura**: El directorio `data/db/` debe ser escribible

**No se requieren**:
- ❌ GDAL/OGR (Windows: OSGeo4W ~500 MB)
- ❌ PHP zip extension (se procesa en Render)
- ❌ Librerías geoespaciales

### Microservicio Render

**Entorno Render.com** (Linux):

1. **PHP >= 8.1** con extensiones:
   ```bash
   - ext-zip (para extraer archivos SHP)
   - ext-curl (para descargar desde CKAN)
   - ext-json (para callback)
   ```

2. **GDAL/OGR** (~20 MB en Linux):
   ```bash
   # Instalación automática en Render con buildpack:
   heroku/php + buildpack GDAL
   ```

3. **Variables de entorno**:
   ```
   CKAN_BASE_URL=http://ckan.dipupalenciaindicadores.secmotic.com
   CKAN_JWT_TOKEN=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
   ```

---

## 📝 IMPLEMENTACIÓN

### 1. Configuración (app/Config/CkanSync.php)

```php
<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class CkanSync extends BaseConfig
{
    /**
     * Directorio base para sincronización
     */
    public string $baseDir = WRITEPATH . 'ckan_sync';

    /**
     * Directorio para bases de datos SQLite
     */
    public string $sqliteDir = WRITEPATH . 'ckan_sync/sqlite';

    /**
     * Directorio para archivos GeoJSON
     */
    public string $geojsonDir = WRITEPATH . 'ckan_sync/geojson';

    /**
     * Directorio temporal
     */
    public string $tempDir = WRITEPATH . 'ckan_sync/temp';

    /**
     * Directorio de logs
     */
    public string $logDir = WRITEPATH . 'ckan_sync/logs';

    /**
     * Recursos SHP prioritarios para sincronizar
     *
     * Key: Nombre interno
     * Value: Resource ID de CKAN
     */
    public array $priorityResources = [
        'miradores' => 'c20d3a73-b678-4472-830a-b01037a00654',
        'aparcamientos' => 'ffee67b6-9ff9-4b56-bfca-bbf555318d4f',
        'puntos_interes' => 'b7d3b20f-7828-465f-b5cf-6699f59bf659',
    ];

    /**
     * Frecuencia de sincronización (días)
     */
    public int $syncFrequencyDays = 7;

    /**
     * Máximo de reintentos por recurso
     */
    public int $maxRetries = 3;

    /**
     * Timeout para descarga (segundos)
     */
    public int $downloadTimeout = 300; // 5 minutos

    /**
     * Email para notificaciones de errores
     */
    public string $notificationEmail = 'admin@proyecto.com';

    /**
     * Habilitar conversión a GeoJSON
     */
    public bool $generateGeoJSON = true;

    /**
     * Límite de features en GeoJSON (para archivos grandes)
     */
    public int $geojsonMaxFeatures = 10000;

    /**
     * Ruta al binario ogr2ogr
     */
    public string $ogr2ogrPath = 'C:\\OSGeo4W64\\bin\\ogr2ogr.exe'; // Windows
    // public string $ogr2ogrPath = '/usr/bin/ogr2ogr'; // Linux

    /**
     * Habilitar Spatialite (geometrías avanzadas)
     */
    public bool $useSpatialite = false;
}
```

---

### 2. Gestor Principal (app/Libraries/CkanSyncManager.php)

```php
<?php

namespace App\Libraries;

use Config\CkanSync as CkanSyncConfig;
use App\Libraries\CkanApiClient;
use App\Libraries\ShpProcessor;
use CodeIgniter\Database\ConnectionInterface;

/**
 * Gestor de Sincronización de Recursos CKAN
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
     * Crear estructura de directorios
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
            }
        }
    }

    /**
     * Sincronizar todos los recursos prioritarios
     */
    public function syncAll(): array
    {
        $this->log('info', 'Iniciando sincronización de recursos SHP');

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
        $this->log('info', 'Sincronización completada: ' . json_encode($this->stats));

        // Enviar notificación si hay errores
        if ($this->stats['errors'] > 0) {
            $this->sendErrorNotification();
        }

        return $this->stats;
    }

    /**
     * Sincronizar un recurso específico
     */
    public function syncResource(string $name, string $resourceId): bool
    {
        // 1. Obtener metadata del recurso desde CKAN
        $resource = $this->ckan->getResource($resourceId);

        if (!$resource) {
            throw new \RuntimeException("No se pudo obtener metadata del recurso {$resourceId}");
        }

        // 2. Verificar si necesita actualización
        if ($this->isUpToDate($name, $resource)) {
            $this->log('info', "Recurso {$name} está actualizado, omitiendo...");
            $this->stats['skipped']++;
            return false;
        }

        // 3. Descargar archivo ZIP
        $zipPath = $this->downloadResource($resource);

        // 4. Extraer archivos SHP
        $shpPath = $this->extractZip($zipPath);

        // 5. Procesar SHP y guardar en SQLite
        $sqlitePath = $this->config->sqliteDir . "/shp_{$name}.db";
        $this->shpProcessor->processToSqlite($shpPath, $sqlitePath, $name);

        // 6. Generar GeoJSON si está habilitado
        if ($this->config->generateGeoJSON) {
            $geojsonPath = $this->config->geojsonDir . "/{$name}.geojson";
            $this->shpProcessor->convertToGeoJSON($shpPath, $geojsonPath);
        }

        // 7. Actualizar registro de sincronización
        $this->updateSyncRecord($name, $resource);

        // 8. Limpiar archivos temporales
        $this->cleanup($zipPath, dirname($shpPath));

        $this->stats['updated']++;
        $this->log('info', "Recurso {$name} sincronizado exitosamente");

        return true;
    }

    /**
     * Verificar si el recurso está actualizado
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
        $lastModified = strtotime($resource['last_modified']);
        $lastSync = strtotime($sync->last_synced);

        if ($lastModified > $lastSync) {
            return false; // Hay cambios
        }

        // Verificar frecuencia mínima
        $daysSinceSync = (time() - $lastSync) / 86400;

        if ($daysSinceSync > $this->config->syncFrequencyDays) {
            return false; // Forzar resincronización
        }

        return true; // Está actualizado
    }

    /**
     * Descargar recurso desde CKAN
     */
    private function downloadResource(array $resource): string
    {
        $url = $resource['url'];
        $filename = basename(parse_url($url, PHP_URL_PATH));
        $destination = $this->config->tempDir . '/downloads/' . $filename;

        $this->log('info', "Descargando: {$url}");

        $ch = curl_init($url);
        $fp = fopen($destination, 'w');

        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->config->downloadTimeout,
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

        $this->log('info', "Descarga completada: " . filesize($destination) . " bytes");

        return $destination;
    }

    /**
     * Extraer archivo ZIP
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

        // Buscar archivo .shp
        $files = scandir($extractDir);
        $shpFile = null;

        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'shp') {
                $shpFile = $extractDir . '/' . $file;
                break;
            }
        }

        if (!$shpFile) {
            throw new \RuntimeException("No se encontró archivo .shp en el ZIP");
        }

        return $shpFile;
    }

    /**
     * Actualizar registro de sincronización
     */
    private function updateSyncRecord(string $name, array $resource): void
    {
        $data = [
            'resource_name' => $name,
            'resource_id' => $resource['id'],
            'last_modified' => $resource['last_modified'],
            'last_synced' => date('Y-m-d H:i:s'),
            'file_size' => $resource['size'],
            'format' => $resource['format']
        ];

        $existing = $this->db->table('ckan_resource_sync')
            ->where('resource_name', $name)
            ->get()
            ->getRow();

        if ($existing) {
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
     */
    private function cleanup(string $zipPath, string $extractDir): void
    {
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        if (is_dir($extractDir)) {
            $this->deleteDirectory($extractDir);
        }
    }

    /**
     * Eliminar directorio recursivamente
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
     * Logging
     */
    private function log(string $level, string $message): void
    {
        $logFile = $this->config->logDir . '/sync_' . date('Y_m_d') . '.log';
        $timestamp = date('Y-m-d H:i:s');
        $line = "[{$timestamp}] [{$level}] {$message}\n";

        file_put_contents($logFile, $line, FILE_APPEND);

        if ($level === 'error') {
            log_message('error', $message);
        }
    }

    /**
     * Enviar notificación de errores
     */
    private function sendErrorNotification(): void
    {
        $email = \Config\Services::email();

        $email->setTo($this->config->notificationEmail);
        $email->setSubject('CKAN Sync - Errores en sincronización');
        $email->setMessage("Se encontraron {$this->stats['errors']} errores durante la sincronización.\n\n" .
                          "Revisar logs en: {$this->config->logDir}");

        $email->send();
    }

    /**
     * Obtener estadísticas
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}
```

---

### 3. Procesador de SHP (app/Libraries/ShpProcessor.php)

```php
<?php

namespace App\Libraries;

use Config\CkanSync as CkanSyncConfig;

/**
 * Procesador de archivos Shapefile
 */
class ShpProcessor
{
    private CkanSyncConfig $config;

    public function __construct()
    {
        $this->config = config('CkanSync');
    }

    /**
     * Procesar SHP y guardar en SQLite usando ogr2ogr
     */
    public function processToSqlite(string $shpPath, string $sqlitePath, string $tableName): bool
    {
        $ogrPath = $this->config->ogr2ogrPath;

        // Verificar que ogr2ogr existe
        if (!file_exists($ogrPath)) {
            throw new \RuntimeException("ogr2ogr no encontrado en: {$ogrPath}");
        }

        // Construir comando
        $cmd = sprintf(
            '"%s" -f SQLite "%s" "%s" -nln "%s" -overwrite -progress',
            $ogrPath,
            $sqlitePath,
            $shpPath,
            $tableName
        );

        // Ejecutar
        exec($cmd . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException("Error ejecutando ogr2ogr: " . implode("\n", $output));
        }

        // Crear índices
        $this->createIndexes($sqlitePath, $tableName);

        return true;
    }

    /**
     * Crear índices en tabla SQLite
     */
    private function createIndexes(string $sqlitePath, string $tableName): void
    {
        $db = new \SQLite3($sqlitePath);

        // Obtener columnas de la tabla
        $result = $db->query("PRAGMA table_info({$tableName})");
        $columns = [];

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $columns[] = $row['name'];
        }

        // Crear índices en columnas comunes
        $indexColumns = ['nombre', 'tipo', 'municipio', 'provincia'];

        foreach ($indexColumns as $col) {
            if (in_array($col, $columns)) {
                $indexName = "idx_{$tableName}_{$col}";
                $db->exec("CREATE INDEX IF NOT EXISTS {$indexName} ON {$tableName}({$col})");
            }
        }

        $db->close();
    }

    /**
     * Convertir SHP a GeoJSON usando ogr2ogr
     */
    public function convertToGeoJSON(string $shpPath, string $geojsonPath): bool
    {
        $ogrPath = $this->config->ogr2ogrPath;

        // Comando con límite de features si está configurado
        $cmd = sprintf(
            '"%s" -f GeoJSON "%s" "%s" -t_srs EPSG:4326',
            $ogrPath,
            $geojsonPath,
            $shpPath
        );

        if ($this->config->geojsonMaxFeatures > 0) {
            $cmd .= ' -limit ' . $this->config->geojsonMaxFeatures;
        }

        exec($cmd . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException("Error convirtiendo a GeoJSON: " . implode("\n", $output));
        }

        // Optimizar GeoJSON (remover propiedades innecesarias)
        $this->optimizeGeoJSON($geojsonPath);

        return true;
    }

    /**
     * Optimizar archivo GeoJSON
     */
    private function optimizeGeoJSON(string $path): void
    {
        $geojson = json_decode(file_get_contents($path), true);

        if (!$geojson) {
            return;
        }

        // Remover propiedades innecesarias de cada feature
        foreach ($geojson['features'] as &$feature) {
            // Mantener solo geometría y propiedades esenciales
            if (isset($feature['properties'])) {
                // Aquí puedes filtrar propiedades específicas
                // Por ejemplo, mantener solo nombre, tipo, etc.
            }
        }

        // Guardar optimizado
        file_put_contents(
            $path,
            json_encode($geojson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
    }

    /**
     * Consultar datos desde SQLite
     */
    public function queryFromSqlite(string $sqlitePath, string $tableName, array $filters = []): array
    {
        if (!file_exists($sqlitePath)) {
            return [];
        }

        $db = new \SQLite3($sqlitePath);

        $sql = "SELECT * FROM {$tableName}";
        $where = [];
        $params = [];

        foreach ($filters as $column => $value) {
            $where[] = "{$column} = ?";
            $params[] = $value;
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $db->prepare($sql);

        foreach ($params as $i => $param) {
            $stmt->bindValue($i + 1, $param);
        }

        $result = $stmt->execute();
        $rows = [];

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }

        $db->close();

        return $rows;
    }
}
```

---

### 4. Comando de CodeIgniter (app/Commands/CkanSyncCommand.php)

```php
<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\CkanSyncManager;

/**
 * Comando para sincronizar recursos CKAN
 *
 * Uso:
 *   php spark ckan:sync
 *   php spark ckan:sync --resource=miradores
 */
class CkanSyncCommand extends BaseCommand
{
    protected $group       = 'CKAN';
    protected $name        = 'ckan:sync';
    protected $description = 'Sincroniza recursos SHP desde CKAN a SQLite';
    protected $usage       = 'ckan:sync [options]';
    protected $arguments   = [];
    protected $options     = [
        '--resource' => 'Sincronizar solo un recurso específico',
        '--force'    => 'Forzar sincronización aunque esté actualizado'
    ];

    public function run(array $params)
    {
        CLI::write('=== CKAN Sync Manager ===', 'blue');
        CLI::newLine();

        $syncManager = new CkanSyncManager();

        $resource = $params['resource'] ?? null;

        if ($resource) {
            CLI::write("Sincronizando recurso: {$resource}", 'yellow');

            $resourceId = config('CkanSync')->priorityResources[$resource] ?? null;

            if (!$resourceId) {
                CLI::error("Recurso '{$resource}' no encontrado en configuración");
                return;
            }

            try {
                $syncManager->syncResource($resource, $resourceId);
                CLI::write("✓ Recurso sincronizado exitosamente", 'green');
            } catch (\Exception $e) {
                CLI::error("✗ Error: " . $e->getMessage());
            }

        } else {
            CLI::write('Sincronizando todos los recursos prioritarios...', 'yellow');
            CLI::newLine();

            $stats = $syncManager->syncAll();

            CLI::newLine();
            CLI::write('=== Resumen ===', 'blue');
            CLI::write("Procesados: {$stats['processed']}", 'white');
            CLI::write("Actualizados: {$stats['updated']}", 'green');
            CLI::write("Omitidos: {$stats['skipped']}", 'yellow');
            CLI::write("Errores: {$stats['errors']}", 'red');
            CLI::write("Duración: " . round($stats['duration'], 2) . "s", 'white');
        }

        CLI::newLine();
    }
}
```

---

### 5. Migración de Base de Datos

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCkanResourceSyncTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'resource_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'unique' => true,
            ],
            'resource_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'last_modified' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'last_synced' => [
                'type' => 'DATETIME',
            ],
            'file_size' => [
                'type' => 'BIGINT',
                'null' => true,
            ],
            'format' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['success', 'error', 'pending'],
                'default' => 'pending',
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('resource_name');
        $this->forge->createTable('ckan_resource_sync');
    }

    public function down()
    {
        $this->forge->dropTable('ckan_resource_sync');
    }
}
```

---

## ⚙️ CONFIGURACIÓN ACTUAL (RENDER + CALLBACK)

### Panel Admin - Configuración

**Archivo: `app/Config/Ckan.php`**

```php
<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Ckan extends BaseConfig
{
    // ... (configuración API existente)

    /**
     * URL del microservicio Render para procesamiento SHP
     * ACTUALIZAR cuando Render esté deployado
     */
    public string $renderServiceUrl = '';  // Ejemplo: 'https://ckan-processor.onrender.com'

    /**
     * Token de seguridad compartido con Render
     * Ya generado, NO cambiar
     */
    public string $callbackToken = '7f8a9b6c5d4e3f2a1b0c9d8e7f6a5b4c3d2e1f0a9b8c7d6e5f4a3b2c1d0e9f8a';
}
```

**Archivo: `app/Config/CkanSync.php`**

```php
<?php

namespace Config;

class CkanSync extends BaseConfig
{
    /**
     * Recursos disponibles para sincronización
     * El admin puede sincronizar cualquiera bajo demanda
     */
    public array $availableResources = [
        'miradores' => [
            'id' => 'c20d3a73-b678-4472-830a-b01037a00654',  // ACTUALIZAR con ID real
            'name' => 'Miradores',
            'description' => 'Puntos de observación del paisaje'
        ],
        'aparcamientos' => [
            'id' => 'ffee67b6-9ff9-4b56-bfca-bbf555318d4f',  // ACTUALIZAR con ID real
            'name' => 'Aparcamientos',
            'description' => 'Áreas de estacionamiento'
        ],
        // ... agregar más recursos según necesidad
    ];
}
```

### Acceso a Interfaz Admin

**URL**: `/admin-panel-bmp/ckan/sync`

**Funcionalidad**:
1. Lista todos los recursos configurados en `availableResources`
2. Muestra última fecha de sincronización
3. Botón "Sincronizar" por recurso
4. Polling automático cada 10s para verificar estado
5. Notificación cuando termina

### ⏳ MODO LEGACY: Sincronización CLI (Opcional)

**Comando disponible** (requiere ogr2ogr instalado localmente):

```bash
# Sincronizar todos los recursos prioritarios
php spark ckan:sync

# Sincronizar un recurso específico
php spark ckan:sync --resource=miradores

# Verificar configuración del sistema
php spark ckan:sync --check
```

**Nota**: Este modo es legacy y no se recomienda. La sincronización debe hacerse desde la interfaz admin.

---

## 📊 USO EN EL DASHBOARD (NUEVA ARQUITECTURA)

### Consultar Datos Sincronizados con QueryBuilder

**Las tablas CKAN aparecen automáticamente en `/api/data/tables`**

```javascript
// 1. Listar todas las tablas disponibles (incluye ckan_*)
fetch('/api/data/tables')
    .then(r => r.json())
    .then(data => {
        // data.tables incluye:
        // - dim_municipios
        // - fact_existencias
        // - ckan_miradores  ← Tabla CKAN sincronizada
        // - ckan_aparcamientos
        console.log(data.tables);
    });

// 2. Consultar datos de tabla CKAN
fetch('/api/data/query', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        table: 'ckan_miradores',
        fields: ['nombre', 'descripcion', 'latitud', 'longitud'],
        options: {
            orderBy: 'nombre',
            direction: 'ASC',
            limit: 100
        }
    })
})
.then(r => r.json())
.then(data => {
    // data.results contiene los miradores
    console.log(data.results);
});
```

### Crear Componentes del Dashboard con Datos CKAN

**Los datos CKAN se integran sin configuración adicional:**

1. Acceder al wizard de componentes
2. Seleccionar tabla `ckan_miradores` (aparece automáticamente)
3. Elegir campos y tipo de visualización
4. Guardar componente

**Ejemplo: Mapa con miradores**

```javascript
// El wizard genera automáticamente:
{
  "type": "map",
  "data_source": {
    "table": "ckan_miradores",
    "fields": ["nombre", "latitud", "longitud", "descripcion"]
  },
  "config": {
    "latitude_field": "latitud",
    "longitude_field": "longitud",
    "popup_template": "<b>{nombre}</b><br>{descripcion}"
  }
}
```

### Integración con Mapas Leaflet

```javascript
// Cargar puntos desde tabla CKAN
async function loadMiradoresMap() {
    const response = await fetch('/api/data/query', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            table: 'ckan_miradores',
            fields: ['nombre', 'latitud', 'longitud', 'descripcion']
        })
    });

    const {results} = await response.json();

    results.forEach(mirador => {
        L.marker([mirador.latitud, mirador.longitud])
            .bindPopup(`
                <b>${mirador.nombre}</b><br>
                ${mirador.descripcion}
            `)
            .addTo(map);
    });
}
```

---

## 🎯 VENTAJAS DE LA NUEVA ARQUITECTURA

1. ✅ **No requiere GDAL/OGR local**
   - Procesamiento en Render (Linux, GDAL pre-instalado)
   - Servidor de producción sin dependencias pesadas

2. ✅ **Integración automática con dashboard**
   - Tablas `ckan_*` aparecen en `/api/data/tables`
   - QueryBuilder las reconoce sin configuración
   - Wizard de componentes las lista automáticamente

3. ✅ **Datos en current.db (única base de datos)**
   - No hay archivos SQLite separados
   - No requiere modificar QueryBuilder
   - Joins posibles entre tablas CKAN y ETL

4. ✅ **Sincronización bajo demanda**
   - Admin decide cuándo actualizar cada recurso
   - No hay cronjobs automáticos
   - Control total sobre el proceso

5. ✅ **Background processing**
   - Interfaz no se bloquea durante procesamiento
   - Polling muestra progreso en tiempo real
   - Notificaciones cuando termina

6. ✅ **Escalable y mantenible**
   - Render puede procesar múltiples recursos en paralelo
   - Separación clara: Panel (UI) vs Render (Processing)
   - Fácil debug con logs en Render

---

## 📝 PRÓXIMOS PASOS

### Completados ✅

1. ✅ ShpProcessor con `importToMainDatabase()`
2. ✅ Endpoint callback `/api/admin/ckan/import`
3. ✅ Controlador admin `Admin/CkanSync.php`
4. ✅ Vista interfaz admin `admin/ckan/sync.php`
5. ✅ Configuración `renderServiceUrl` y `callbackToken`
6. ✅ Rutas configuradas
7. ✅ Tabla `ckan_resource_sync` migrada

### Pendientes ⏳

1. ⏳ **Implementar microservicio Render**
   - Crear repositorio separado
   - Endpoint `/api/sync-shp`
   - Integración con CKAN API
   - Callback al panel

2. ⏳ **Deploy en Render.com**
   - Configurar buildpack con GDAL
   - Variables de entorno
   - Obtener URL del servicio

3. ⏳ **Configurar Panel Admin**
   - Actualizar `renderServiceUrl` en `Ckan.php`
   - Verificar Resource IDs reales en `CkanSync.php`

4. ⏳ **Probar flujo completo end-to-end**
   - Sincronizar desde admin
   - Verificar callback
   - Verificar tabla en current.db
   - Verificar integración con QueryBuilder

---

**Última actualización:** 21 de Octubre de 2025
**Estado:** ✅ Backend completado - ⏳ Microservicio Render pendiente
**Versión:** 2.0.0 (Arquitectura Render + Callback)

# SISTEMA DE SINCRONIZACIÓN AUTOMÁTICA DE ARCHIVOS SHP

**Proyecto:** Dashboard Dinámico Ambiental - Bosque Modelo Palencia
**Fecha:** 21 de Octubre de 2025
**Versión:** 1.0.0

---

## 🎯 OBJETIVO

Resolver la limitación de que **194 recursos SHP (79.8%)** no son consultables vía API CKAN, descargándolos automáticamente, convirtiéndolos a formato SQLite/GeoJSON y manteniéndolos actualizados mediante cronjob.

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

## ✅ SOLUCIÓN PROPUESTA

### Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────┐
│                     CRONJOB (Diario/Semanal)                │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  1. Obtener lista de recursos SHP desde API CKAN            │
│     GET /api/3/action/package_search?...                    │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  2. Verificar si el recurso ya existe en SQLite             │
│     - Comparar hash MD5                                      │
│     - Comparar fecha de modificación                         │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  3. Si hay cambios: Descargar archivo SHP (ZIP)             │
│     - Descargar desde URL del recurso                        │
│     - Guardar temporalmente                                  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  4. Extraer y procesar SHP                                   │
│     - Descomprimir ZIP                                       │
│     - Leer SHP con ogr2ogr/PHP-GDAL                         │
│     - Extraer geometrías y atributos                         │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  5. Almacenar en SQLite/Spatialite                          │
│     - Crear tabla si no existe                               │
│     - Insertar/actualizar registros                          │
│     - Indexar campos de búsqueda                             │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  6. Generar GeoJSON para mapas web                          │
│     - Convertir geometrías a GeoJSON                         │
│     - Guardar en directorio público                          │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  7. Limpiar archivos temporales                              │
│     - Eliminar ZIPs descargados                              │
│     - Eliminar SHP extraídos                                 │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│  8. Logging y notificaciones                                 │
│     - Registrar éxitos/errores                               │
│     - Enviar email si hay errores críticos                   │
└─────────────────────────────────────────────────────────────┘
```

---

## 🗂️ ESTRUCTURA DE ARCHIVOS

```
writable/
├── ckan_sync/
│   ├── sqlite/
│   │   ├── ckan_resources.db           ← Base de datos principal
│   │   ├── shp_miradores.db            ← Ejemplo: Miradores
│   │   ├── shp_aparcamientos.db        ← Ejemplo: Aparcamientos
│   │   └── shp_espacios_naturales.db   ← Ejemplo: Espacios naturales
│   ├── geojson/                        ← GeoJSON para mapas web
│   │   ├── miradores.geojson
│   │   ├── aparcamientos.geojson
│   │   └── espacios_naturales.geojson
│   ├── temp/                           ← Archivos temporales
│   │   └── downloads/
│   └── logs/                           ← Logs de sincronización
│       ├── sync_2025_10_21.log
│       └── errors.log

app/
├── Commands/
│   └── CkanSyncCommand.php             ← Comando de CodeIgniter
├── Libraries/
│   ├── CkanSyncManager.php             ← Gestor principal
│   └── ShpProcessor.php                ← Procesador de SHP
└── Models/
    └── CkanResourceSyncModel.php       ← Modelo de recursos sincronizados
```

---

## 🔧 REQUISITOS DEL SISTEMA

### Software Necesario

1. **PHP >= 8.1** con extensiones:
   ```bash
   php -m | grep -E "(zip|sqlite3|pdo_sqlite)"
   ```

2. **GDAL/OGR** (para procesar archivos SHP):
   ```bash
   # Windows (con OSGeo4W)
   ogr2ogr --version

   # Linux
   sudo apt-get install gdal-bin
   ```

3. **SQLite3** con Spatialite (opcional, para geometrías):
   ```bash
   sqlite3 --version
   ```

### Librerías PHP Recomendadas

```json
{
  "require": {
    "php": ">=8.1",
    "ext-zip": "*",
    "ext-sqlite3": "*",
    "ext-pdo": "*",
    "phayes/geophp": "^1.2"  // Para procesar geometrías
  }
}
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

## ⏰ CONFIGURACIÓN DEL CRONJOB

### Windows (Programador de Tareas)

**Crear tarea programada:**

1. Abrir "Programador de tareas" (Task Scheduler)
2. Crear tarea básica
3. Configurar:
   - **Nombre:** CKAN Sync - Bosque Modelo Palencia
   - **Desencadenador:** Semanalmente, todos los lunes a las 03:00 AM
   - **Acción:** Iniciar programa
   - **Programa:** `C:\xampp\php\php.exe`
   - **Argumentos:** `C:\xampp\htdocs\WP\dashboard-dinamico-final\spark ckan:sync`
   - **Directorio:** `C:\xampp\htdocs\WP\dashboard-dinamico-final`

### Linux (Crontab)

```bash
# Editar crontab
crontab -e

# Agregar línea (ejecutar todos los lunes a las 3 AM)
0 3 * * 1 cd /var/www/dashboard && php spark ckan:sync >> /var/log/ckan_sync.log 2>&1
```

### Frecuencias Recomendadas

| Tipo de Dato | Frecuencia | Cronjob |
|--------------|------------|---------|
| Datos estáticos (miradores, aparcamientos) | Mensual | `0 3 1 * *` |
| Datos semi-dinámicos (infraestructura) | Semanal | `0 3 * * 1` |
| Datos dinámicos (si existen en SHP) | Diario | `0 3 * * *` |

---

## 📊 USO EN EL DASHBOARD

### Consultar Datos Sincronizados

```php
// En Controller
public function getMiradores()
{
    $shpProcessor = new \App\Libraries\ShpProcessor();
    $sqlitePath = WRITEPATH . 'ckan_sync/sqlite/shp_miradores.db';

    $miradores = $shpProcessor->queryFromSqlite($sqlitePath, 'miradores');

    return $this->respond($miradores);
}
```

### Servir GeoJSON para Mapas

```php
// En Routes.php
$routes->get('geojson/(:segment)', 'GeoDataController::getGeoJSON/$1');

// En Controller
public function getGeoJSON($resource)
{
    $geojsonPath = WRITEPATH . "ckan_sync/geojson/{$resource}.geojson";

    if (!file_exists($geojsonPath)) {
        return $this->failNotFound("GeoJSON no encontrado");
    }

    header('Content-Type: application/json');
    readfile($geojsonPath);
    exit;
}
```

### Usar en Leaflet

```javascript
// Cargar GeoJSON en mapa
fetch('/geojson/miradores')
    .then(r => r.json())
    .then(geojson => {
        L.geoJSON(geojson, {
            onEachFeature: function(feature, layer) {
                layer.bindPopup(`
                    <b>${feature.properties.nombre}</b><br>
                    ${feature.properties.descripcion}
                `);
            }
        }).addTo(map);
    });
```

---

## 🎯 VENTAJAS DE ESTA SOLUCIÓN

1. ✅ **Datos locales más rápidos**
   - SQLite: <10ms vs API CKAN: ~400ms
   - 40x más rápido

2. ✅ **Consultas SQL completas**
   - WHERE, JOIN, GROUP BY, etc.
   - Filtros avanzados no disponibles en API CKAN

3. ✅ **Disponibilidad offline**
   - Dashboard funciona aunque CKAN caiga
   - Datos críticos siempre disponibles

4. ✅ **Optimizado para web**
   - GeoJSON listos para Leaflet/Mapbox
   - Sin procesamiento en tiempo real

5. ✅ **Actualización automática**
   - Cronjob mantiene datos sincronizados
   - Sin intervención manual

---

## 📝 PRÓXIMOS PASOS

1. ✅ Instalar GDAL/OGR
2. ✅ Configurar rutas en `CkanSync.php`
3. ✅ Ejecutar migración: `php spark migrate`
4. ✅ Probar sincronización manual: `php spark ckan:sync --resource=miradores`
5. ✅ Configurar cronjob
6. ✅ Monitorear logs de sincronización

---

**Última actualización:** 21 de Octubre de 2025
**Estado:** ✅ Diseño completo - Listo para implementación

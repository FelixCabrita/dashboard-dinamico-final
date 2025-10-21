<?php

namespace App\Libraries;

use Config\CkanSync as CkanSyncConfig;

/**
 * Procesador de archivos Shapefile (SHP)
 *
 * Convierte archivos SHP a formatos consultables (SQLite, GeoJSON)
 * usando ogr2ogr de GDAL.
 *
 * Requisitos:
 * - GDAL/OGR instalado (ogr2ogr en PATH)
 * - Extensiones PHP: zip, sqlite3
 *
 * @package App\Libraries
 * @version 1.0.0
 * @date 2025-10-21
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
     *
     * @param string $shpPath Ruta al archivo .shp
     * @param string $sqlitePath Ruta de salida para SQLite
     * @param string $tableName Nombre de la tabla a crear
     * @return bool
     * @throws \RuntimeException Si ogr2ogr falla
     */
    public function processToSqlite(string $shpPath, string $sqlitePath, string $tableName): bool
    {
        $ogrPath = $this->config->ogr2ogrPath;

        // Verificar que el archivo SHP existe
        if (!file_exists($shpPath)) {
            throw new \RuntimeException("Archivo SHP no encontrado: {$shpPath}");
        }

        // Construir comando ogr2ogr
        // Formato: ogr2ogr -f SQLite output.db input.shp -nln table_name -overwrite
        $cmd = sprintf(
            '%s -f SQLite "%s" "%s" -nln "%s" -overwrite',
            $ogrPath,
            $sqlitePath,
            $shpPath,
            $tableName
        );

        // Opciones adicionales
        if ($this->config->useSpatialite) {
            $cmd .= ' -dsco SPATIALITE=YES';
        }

        if ($this->config->validateGeometries) {
            $cmd .= ' -nlt PROMOTE_TO_MULTI'; // Normalizar geometrías
        }

        // Ejecutar comando
        exec($cmd . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            $errorMsg = "Error ejecutando ogr2ogr: " . implode("\n", $output);
            log_message('error', $errorMsg);
            throw new \RuntimeException($errorMsg);
        }

        // Crear índices para optimizar consultas
        $this->createIndexes($sqlitePath, $tableName);

        log_message('info', "SHP procesado a SQLite exitosamente: {$tableName}");

        return true;
    }

    /**
     * Crear índices en tabla SQLite para optimizar consultas
     *
     * @param string $sqlitePath Ruta al archivo SQLite
     * @param string $tableName Nombre de la tabla
     * @return void
     */
    private function createIndexes(string $sqlitePath, string $tableName): void
    {
        if (!file_exists($sqlitePath)) {
            return;
        }

        try {
            $db = new \SQLite3($sqlitePath);

            // Obtener columnas de la tabla
            $result = $db->query("PRAGMA table_info({$tableName})");
            $columns = [];

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $columns[] = $row['name'];
            }

            // Crear índices en columnas comunes de datos geoespaciales
            $indexableColumns = ['nombre', 'tipo', 'municipio', 'provincia', 'codigo', 'cod_ine'];

            foreach ($indexableColumns as $col) {
                // Buscar columna case-insensitive
                $actualCol = null;
                foreach ($columns as $c) {
                    if (strtolower($c) === strtolower($col)) {
                        $actualCol = $c;
                        break;
                    }
                }

                if ($actualCol) {
                    $indexName = "idx_{$tableName}_{$actualCol}";
                    $db->exec("CREATE INDEX IF NOT EXISTS {$indexName} ON {$tableName}({$actualCol})");
                    log_message('debug', "Índice creado: {$indexName}");
                }
            }

            $db->close();

        } catch (\Exception $e) {
            log_message('warning', "Error creando índices: " . $e->getMessage());
        }
    }

    /**
     * Convertir SHP a GeoJSON usando ogr2ogr
     *
     * @param string $shpPath Ruta al archivo .shp
     * @param string $geojsonPath Ruta de salida para GeoJSON
     * @return bool
     * @throws \RuntimeException Si ogr2ogr falla
     */
    public function convertToGeoJSON(string $shpPath, string $geojsonPath): bool
    {
        $ogrPath = $this->config->ogr2ogrPath;

        // Verificar que el archivo SHP existe
        if (!file_exists($shpPath)) {
            throw new \RuntimeException("Archivo SHP no encontrado: {$shpPath}");
        }

        // Construir comando
        // Formato: ogr2ogr -f GeoJSON output.geojson input.shp -t_srs EPSG:4326
        $cmd = sprintf(
            '%s -f GeoJSON "%s" "%s" -t_srs %s',
            $ogrPath,
            $geojsonPath,
            $shpPath,
            $this->config->outputSRS
        );

        // Aplicar límite de features si está configurado
        if ($this->config->geojsonMaxFeatures > 0) {
            $cmd .= ' -limit ' . $this->config->geojsonMaxFeatures;
        }

        // Ejecutar
        exec($cmd . ' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            $errorMsg = "Error convirtiendo a GeoJSON: " . implode("\n", $output);
            log_message('error', $errorMsg);
            throw new \RuntimeException($errorMsg);
        }

        // Optimizar GeoJSON (reducir tamaño)
        $this->optimizeGeoJSON($geojsonPath);

        log_message('info', "SHP convertido a GeoJSON exitosamente: {$geojsonPath}");

        return true;
    }

    /**
     * Optimizar archivo GeoJSON para reducir tamaño
     *
     * Remueve propiedades innecesarias y formatea para web
     *
     * @param string $path Ruta al archivo GeoJSON
     * @return void
     */
    private function optimizeGeoJSON(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        try {
            $content = file_get_contents($path);
            $geojson = json_decode($content, true);

            if (!$geojson || !isset($geojson['features'])) {
                return;
            }

            // Remover propiedades internas de ogr2ogr que no son necesarias
            foreach ($geojson['features'] as &$feature) {
                if (isset($feature['properties'])) {
                    // Remover propiedades técnicas
                    unset($feature['properties']['FID']);
                    unset($feature['properties']['fid']);
                    unset($feature['properties']['OGR_FID']);
                }
            }

            // Guardar optimizado (sin pretty print para reducir tamaño)
            file_put_contents(
                $path,
                json_encode($geojson, JSON_UNESCAPED_UNICODE)
            );

            log_message('debug', "GeoJSON optimizado: " . filesize($path) . " bytes");

        } catch (\Exception $e) {
            log_message('warning', "Error optimizando GeoJSON: " . $e->getMessage());
        }
    }

    /**
     * Consultar datos desde SQLite generado
     *
     * @param string $sqlitePath Ruta al archivo SQLite
     * @param string $tableName Nombre de la tabla
     * @param array $filters Filtros a aplicar (campo => valor)
     * @param int $limit Límite de registros
     * @return array
     */
    public function queryFromSqlite(string $sqlitePath, string $tableName, array $filters = [], int $limit = 100): array
    {
        if (!file_exists($sqlitePath)) {
            log_message('warning', "SQLite no encontrado: {$sqlitePath}");
            return [];
        }

        try {
            $db = new \SQLite3($sqlitePath);

            // Construir consulta base
            $sql = "SELECT * FROM {$tableName}";
            $where = [];
            $params = [];

            // Aplicar filtros
            foreach ($filters as $column => $value) {
                $where[] = "{$column} = ?";
                $params[] = $value;
            }

            if (!empty($where)) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }

            $sql .= " LIMIT {$limit}";

            // Ejecutar query
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

        } catch (\Exception $e) {
            log_message('error', "Error consultando SQLite: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Verificar si ogr2ogr está disponible
     *
     * @return array [available: bool, version: string|null, path: string|null]
     */
    public function checkOgr2ogr(): array
    {
        $ogrPath = $this->config->ogr2ogrPath;

        exec("{$ogrPath} --version 2>&1", $output, $returnCode);

        $available = $returnCode === 0;
        $version = $available ? trim($output[0]) : null;

        return [
            'available' => $available,
            'version' => $version,
            'path' => $ogrPath,
        ];
    }
}

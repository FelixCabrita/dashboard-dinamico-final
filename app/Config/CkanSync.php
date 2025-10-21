<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Configuración del Sistema de Sincronización CKAN
 *
 * Este archivo configura el sistema de sincronización automática
 * de recursos SHP desde CKAN a SQLite/GeoJSON local.
 *
 * @package Config
 * @version 1.0.0
 * @date 2025-10-21
 */
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
     * Directorio temporal para descargas
     */
    public string $tempDir = WRITEPATH . 'ckan_sync/temp';

    /**
     * Directorio de logs
     */
    public string $logDir = WRITEPATH . 'ckan_sync/logs';

    /**
     * Recursos SHP disponibles para sincronizar
     *
     * Estructura:
     * 'key' => [
     *     'id' => 'resource-id-from-ckan',
     *     'name' => 'Nombre descriptivo',
     *     'description' => 'Descripción breve'
     * ]
     *
     * El admin puede sincronizar cualquiera de estos recursos bajo demanda
     * Nota: Actualizar IDs desde docs/DOCUMENTACION_API_CKAN.md
     */
    public array $availableResources = [
        'miradores' => [
            'id' => 'c20d3a73-b678-4472-830a-b01037a00654',
            'name' => 'Miradores',
            'description' => 'Puntos de observación del paisaje',
        ],
        'aparcamientos' => [
            'id' => 'ffee67b6-9ff9-4b56-bfca-bbf555318d4f',
            'name' => 'Aparcamientos',
            'description' => 'Zonas de estacionamiento',
        ],
        'puntos_interes' => [
            'id' => 'b7d3b20f-7828-465f-b5cf-6699f59bf659',
            'name' => 'Puntos de Interés',
            'description' => 'Lugares destacados del territorio',
        ],
        // Agregar más recursos SHP aquí según necesidad
        // Consultar inventario completo en docs/DOCUMENTACION_API_CKAN.md
    ];

    /**
     * Frecuencia de sincronización (días)
     *
     * Recursos se resincronizarán automáticamente después de N días,
     * incluso si no han cambiado en CKAN
     */
    public int $syncFrequencyDays = 7;

    /**
     * Máximo de reintentos por recurso en caso de error
     */
    public int $maxRetries = 3;

    /**
     * Timeout para descarga de archivos (segundos)
     *
     * Archivos SHP típicamente pesan 100KB - 5MB
     * Timeout de 5 minutos es conservador para conexiones lentas
     */
    public int $downloadTimeout = 300; // 5 minutos

    /**
     * Email para notificaciones de errores
     *
     * Se envía email si la sincronización falla
     */
    public string $notificationEmail = 'admin@bosquepalencia.es';

    /**
     * Habilitar conversión a GeoJSON
     *
     * true: Genera archivos .geojson para mapas web
     * false: Solo SQLite (reduce tiempo de procesamiento)
     */
    public bool $generateGeoJSON = true;

    /**
     * Límite de features en GeoJSON
     *
     * Para archivos muy grandes (>10K features), limitar
     * para evitar que el navegador se cuelgue
     *
     * 0 = sin límite
     */
    public int $geojsonMaxFeatures = 10000;

    /**
     * Ruta al binario ogr2ogr
     *
     * Windows (OSGeo4W): C:\OSGeo4W64\bin\ogr2ogr.exe
     * Linux: /usr/bin/ogr2ogr
     * GitHub Actions: ogr2ogr (en PATH)
     */
    public string $ogr2ogrPath = 'ogr2ogr'; // Auto-detect en PATH

    /**
     * Habilitar Spatialite (geometrías avanzadas)
     *
     * Requiere extensión Spatialite cargada en SQLite
     * Si false, solo almacena geometrías como WKT/WKB
     */
    public bool $useSpatialite = false;

    /**
     * Nivel de verbosidad de logs
     *
     * 'error': Solo errores críticos
     * 'warning': Errores + warnings
     * 'info': Todo (incluye progreso de sync)
     * 'debug': Máximo detalle
     */
    public string $logLevel = 'info';

    /**
     * Retención de logs (días)
     *
     * Logs más antiguos que N días se eliminan automáticamente
     */
    public int $logRetentionDays = 30;

    /**
     * Habilitar caché de metadata de recursos
     *
     * Cachea respuestas de resource_show para reducir llamadas a API
     */
    public bool $cacheMetadata = true;

    /**
     * TTL de caché de metadata (segundos)
     */
    public int $metadataCacheTTL = 86400; // 24 horas

    /**
     * Comprimir archivos SQLite después de procesar
     *
     * Reduce tamaño ~30% pero requiere más tiempo
     */
    public bool $compressSqlite = false;

    /**
     * Validar geometrías al procesar SHP
     *
     * Valida que geometrías sean válidas (no corrupt)
     * Aumenta tiempo de procesamiento pero mejora calidad de datos
     */
    public bool $validateGeometries = true;

    /**
     * Sistema de coordenadas de salida para GeoJSON
     *
     * EPSG:4326 (WGS84) es el estándar web
     */
    public string $outputSRS = 'EPSG:4326';
}

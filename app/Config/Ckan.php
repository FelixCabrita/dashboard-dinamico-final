<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Configuración de la API CKAN
 *
 * Configuración para acceder a la API CKAN de Diputación de Palencia
 * y gestionar autenticación, caché y recursos críticos.
 *
 * @package Config
 * @version 1.0.0
 * @date 2025-10-21
 */
class Ckan extends BaseConfig
{
    /**
     * Base URL del servidor CKAN
     *
     * DESARROLLO: http://ckan.dev.dipupalenciaindicadores.secmotic.com
     * PRODUCCIÓN: http://ckan.dipupalenciaindicadores.secmotic.com
     *
     * IMPORTANTE: Cambiar a producción antes de deploy
     */
    public string $baseUrl = 'http://ckan.dev.dipupalenciaindicadores.secmotic.com';

    /**
     * API Token JWT para autenticación
     *
     * CRÍTICO: NO exponer en frontend. Mantener solo en servidor.
     * Este token proporciona acceso a datasets privados y públicos.
     *
     * Token actual: Válido desde 21/10/2025
     * Fuente: Documentación del cliente (docs/documentacion_api.md)
     */
    public string $apiToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiJNcjE4aXZWWkpVN0ZYbWVpR2JwTTBHZTR4dzdnaS1fY2s0QjROQXNWeXJZIiwiaWF0IjoxNzU4NzkxMTE1fQ._ibqSjv1RGPc0NYz3YV-IlUU02z6kyb0he6OILRZO8Q';

    /**
     * Timeout para requests HTTP (segundos)
     *
     * API CKAN típicamente responde en 400-500ms
     * Timeout de 30s permite manejar queries pesadas
     */
    public int $timeout = 30;

    /**
     * Número máximo de reintentos en caso de error 5xx
     *
     * Reintentos con backoff exponencial: 2s, 4s, 8s
     */
    public int $maxRetries = 3;

    /**
     * Habilitar caché de respuestas API
     *
     * OBLIGATORIO: API CKAN tiene latencia alta (~1.3s cold start)
     * Caché reduce latencia a <10ms y disminuye carga del servidor
     */
    public bool $cacheEnabled = true;

    /**
     * TTL de caché en segundos
     *
     * 3600s (1 hora) es razonable para datos que cambian poco
     * Ajustar según frecuencia de actualización de datasets
     */
    public int $cacheTTL = 3600; // 1 hora

    /**
     * Prefix para keys de caché
     *
     * Evita colisiones con otros sistemas de caché
     */
    public string $cachePrefix = 'ckan_';

    /**
     * Habilitar logging de requests lentos
     *
     * Ayuda a identificar queries problemáticas
     */
    public bool $logSlowQueries = true;

    /**
     * Threshold para considerar query lenta (milisegundos)
     *
     * API CKAN normal: 400-500ms
     * Cold start: 1300ms
     * Queries >2000ms son anormales
     */
    public int $slowQueryThreshold = 2000;

    /**
     * Resource IDs de recursos críticos
     *
     * Estos son los recursos más importantes del sistema,
     * utilizados frecuentemente y optimizados con caché.
     *
     * Fuente: docs/DOCUMENTACION_API_CKAN.md
     */
    public array $criticalResources = [
        /**
         * Registro de Municipios de Castilla y León
         * Dataset: general
         * Registros: 2,248 (191 en Palencia)
         * Formato: CSV
         * Actualización: DIARIA
         * Uso: Filtros geográficos, dropdowns, mapas
         */
        'municipios' => '41c0aa65-3964-4926-836d-d501f9e55e52',

        /**
         * Incendios Forestales
         * Dataset: flora_y_fauna
         * Registros: 20,608 (1,952 en Palencia)
         * Formato: CSV
         * Actualización: Tiempo real durante campaña
         * Uso: Mapas de riesgo, heatmaps, alertas
         */
        'incendios' => 'f74b444d-e0f9-4638-916e-ad595bf5f0dc',

        /**
         * Población Total por Provincias y Sexo
         * Dataset: demografia_y_empleo
         * Registros: 594 (66 en Palencia)
         * Formato: CSV
         * Serie temporal: 1986-2023
         * Uso: Gráficos demográficos, KPIs, tendencias
         */
        'poblacion' => '771bfd9e-d306-4202-bc08-dab243d38a2a',
    ];

    /**
     * Límite por defecto de registros en consultas
     *
     * Evita traer datasets completos accidentalmente
     * Se puede sobreescribir por consulta
     */
    public int $defaultLimit = 100;

    /**
     * Máximo permitido de registros por consulta
     *
     * Protección contra consultas demasiado grandes
     * API CKAN tiene limit interno de ~10K registros
     */
    public int $maxLimit = 1000;

    /**
     * User-Agent para identificar requests
     *
     * Identifica al cliente en logs del servidor CKAN
     */
    public string $userAgent = 'BosqueModeloPalencia/1.0 (Dashboard Dinamico Ambiental)';

    /**
     * Habilitar modo debug
     *
     * true: Muestra detalles de requests en logs
     * false: Solo errores
     */
    public bool $debugMode = false;

    /**
     * Estadísticas de la API
     *
     * Información del inventario completo
     * (Para referencia - NO modificar)
     */
    public array $inventario = [
        'datasets_total' => 20,
        'recursos_total' => 243,
        'recursos_consultables' => 47,  // datastore_active: true
        'recursos_shp' => 194,           // Requieren descarga
        'datasets_privados' => 16,       // 80% son privados
        'datasets_publicos' => 4,
        'organizaciones' => 5,
    ];

    /**
     * Limitaciones conocidas de la API
     *
     * Documentadas en docs/DOCUMENTACION_API_CKAN.md
     * (Para referencia - NO modificar)
     */
    public array $limitaciones = [
        'spatial_endpoint' => false,     // HTTP 409 - No disponible
        'range_filters' => false,        // No soporta >, <, BETWEEN
        'like_operator' => false,        // No soporta LIKE en filters
        'rate_limit_documentado' => false,
        'metadata_organizaciones_incompleta' => true,
    ];
}

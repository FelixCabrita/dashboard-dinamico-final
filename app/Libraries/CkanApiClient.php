<?php

namespace App\Libraries;

use Config\Ckan as CkanConfig;

/**
 * Cliente para API CKAN
 *
 * Proporciona métodos para consultar la API CKAN de forma segura
 * con manejo de errores, retry logic y caché automático.
 *
 * Basado en documentación: docs/DOCUMENTACION_API_CKAN.md
 * Testing validado: test_api_ckan/REPORTE_FINAL.md
 *
 * @package App\Libraries
 * @version 1.0.0
 * @date 2025-10-21
 */
class CkanApiClient
{
    private CkanConfig $config;
    private \App\Libraries\CacheManager $cache;

    public function __construct()
    {
        $this->config = config('Ckan');
        $this->cache = new \App\Libraries\CacheManager();
    }

    /**
     * Hace un request a la API CKAN con retry logic
     *
     * @param string $endpoint Endpoint de la API (ej: 'datastore_search')
     * @param array $params Parámetros de query
     * @param bool $useCache Usar sistema de caché
     * @return array|null Respuesta JSON o null si falla
     */
    private function makeRequest(string $endpoint, array $params = [], bool $useCache = true): ?array
    {
        $url = $this->config->baseUrl . '/api/3/action/' . $endpoint;

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        // Intentar caché primero
        if ($useCache && $this->config->cacheEnabled) {
            $cacheKey = $this->config->cachePrefix . md5($endpoint . json_encode($params));
            $cached = $this->cache->get($cacheKey);

            if ($cached !== null) {
                return $cached;
            }
        }

        $attempt = 0;
        $maxRetries = $this->config->maxRetries;

        while ($attempt < $maxRetries) {
            try {
                $startTime = microtime(true);

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => $this->config->timeout,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: ' . $this->config->apiToken,
                        'Content-Type: application/json',
                        'User-Agent: ' . $this->config->userAgent
                    ],
                    CURLOPT_SSL_VERIFYPEER => false, // Para desarrollo
                ]);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlError = curl_error($ch);
                curl_close($ch);

                $duration = (microtime(true) - $startTime) * 1000;

                // Log queries lentas
                if ($this->config->logSlowQueries && $duration > $this->config->slowQueryThreshold) {
                    log_message('warning', "CKAN slow query: {$endpoint} ({$duration}ms)");
                }

                // Success
                if ($httpCode === 200 && $response) {
                    $data = json_decode($response, true);

                    if (isset($data['success']) && $data['success'] === true) {
                        $result = $data['result'];

                        // Guardar en caché
                        if ($useCache && $this->config->cacheEnabled) {
                            $this->cache->set($cacheKey, $result, $this->config->cacheTTL);
                        }

                        return $result;
                    }
                }

                // Retry en errores 5xx
                if ($httpCode >= 500 && $httpCode < 600) {
                    $attempt++;
                    if ($attempt < $maxRetries) {
                        $backoff = pow(2, $attempt); // Backoff exponencial: 2s, 4s, 8s
                        log_message('info', "CKAN retry {$attempt}/{$maxRetries} after {$backoff}s for {$endpoint}");
                        sleep($backoff);
                        continue;
                    }
                }

                // Error 4xx o máximo de reintentos alcanzado
                log_message('error', "CKAN API error: {$endpoint} - HTTP {$httpCode} - {$curlError}");
                return null;

            } catch (\Exception $e) {
                log_message('error', "CKAN API exception: {$endpoint} - " . $e->getMessage());

                $attempt++;
                if ($attempt >= $maxRetries) {
                    return null;
                }
                sleep(pow(2, $attempt));
            }
        }

        return null;
    }

    /**
     * Obtiene datos de un recurso con datastore_search
     *
     * @param string $resourceId UUID del recurso
     * @param array $params Parámetros de consulta
     * @param bool $useCache Usar caché (default: true)
     * @return array|null
     */
    public function datastoreSearch(string $resourceId, array $params = [], bool $useCache = true): ?array
    {
        $params['resource_id'] = $resourceId;

        // Aplicar límite por defecto
        if (!isset($params['limit'])) {
            $params['limit'] = $this->config->defaultLimit;
        }

        // Validar límite máximo
        if ($params['limit'] > $this->config->maxLimit) {
            $params['limit'] = $this->config->maxLimit;
            log_message('warning', "CKAN limit reduced to {$this->config->maxLimit}");
        }

        return $this->makeRequest('datastore_search', $params, $useCache);
    }

    /**
     * Obtiene municipios de Castilla y León
     *
     * @param string|null $provincia Nombre de la provincia (ej: 'PALENCIA')
     * @param array $options Opciones adicionales (limit, sort, fields)
     * @return array|null
     */
    public function getMunicipios(?string $provincia = null, array $options = []): ?array
    {
        $params = [
            'limit' => $options['limit'] ?? 500,
            'sort' => $options['sort'] ?? 'Municipio asc',
            'fields' => $options['fields'] ?? 'Municipio,Cod_INE,Población,Latitud,Longitud,Provincia'
        ];

        if ($provincia) {
            $params['filters'] = json_encode(['Provincia' => strtoupper($provincia)]);
        }

        return $this->datastoreSearch(
            $this->config->criticalResources['municipios'],
            $params
        );
    }

    /**
     * Obtiene datos de población por provincia
     *
     * @param string $provincia Nombre de la provincia
     * @param int|null $year Año específico (opcional)
     * @return array|null
     */
    public function getPoblacion(string $provincia, ?int $year = null): ?array
    {
        $filters = ['PROVINCIA' => strtoupper($provincia)];

        if ($year !== null) {
            $filters['FECHA'] = $year;
        }

        $params = [
            'filters' => json_encode($filters),
            'sort' => 'FECHA desc',
            'limit' => 100,
            'fields' => 'FECHA,SEXO,PROVINCIA,POBLACIÓN'
        ];

        return $this->datastoreSearch(
            $this->config->criticalResources['poblacion'],
            $params
        );
    }

    /**
     * Obtiene incendios forestales
     *
     * @param string $provincia Nombre de la provincia
     * @param array $options Opciones (limit, year, fields)
     * @return array|null
     */
    public function getIncendios(string $provincia, array $options = []): ?array
    {
        $params = [
            'q' => strtoupper($provincia), // Búsqueda full-text
            'limit' => $options['limit'] ?? 100,
            'sort' => $options['sort'] ?? 'FECHA DE INICIO desc'
        ];

        if (isset($options['fields'])) {
            $params['fields'] = $options['fields'];
        } else {
            $params['fields'] = 'FECHA DE INICIO,TÉRMINO MUNICIPAL,NIVEL MÁXIMO ALCANZADO,TIPO Y HAS DE SUPERFICIE AFECTADA,CAUSA PROBABLE,Posición';
        }

        return $this->datastoreSearch(
            $this->config->criticalResources['incendios'],
            $params
        );
    }

    /**
     * Lista todos los datasets
     *
     * @param bool $includePrivate Incluir datasets privados
     * @return array|null
     */
    public function listDatasets(bool $includePrivate = true): ?array
    {
        $params = [
            'q' => '*:*',
            'rows' => 1000,
            'include_private' => $includePrivate ? 'true' : 'false'
        ];

        return $this->makeRequest('package_search', $params);
    }

    /**
     * Obtiene detalle de un dataset
     *
     * @param string $id UUID o name del dataset
     * @return array|null
     */
    public function getDataset(string $id): ?array
    {
        return $this->makeRequest('package_show', ['id' => $id]);
    }

    /**
     * Obtiene detalle de un recurso
     *
     * @param string $id UUID del recurso
     * @return array|null
     */
    public function getResource(string $id): ?array
    {
        return $this->makeRequest('resource_show', ['id' => $id]);
    }

    /**
     * Limpia toda la caché de CKAN
     *
     * @return bool
     */
    public function clearCache(): bool
    {
        return $this->cache->flush();
    }

    /**
     * Obtiene estadísticas de caché
     *
     * @return array
     */
    public function getCacheStats(): array
    {
        return $this->cache->getStats();
    }
}

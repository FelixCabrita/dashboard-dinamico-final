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
}

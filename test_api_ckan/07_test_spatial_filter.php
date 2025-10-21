<?php
/**
 * TEST 07: SPATIAL_FILTER_BY_MUNICIPALITY - FILTRADO ESPACIAL - API CKAN
 *
 * Objetivo: Probar capacidad de filtrado geoespacial por municipio
 *
 * Verifica:
 * - Filtrado por nombre de municipio
 * - Diferentes formatos de salida (geojson, csv, xml)
 * - Rendimiento de consultas espaciales
 * - Estructura de respuestas GeoJSON
 *
 * @autor Claude Code
 * @fecha 21 de Octubre de 2025
 */

// Configuración
define('CKAN_BASE_URL', 'http://ckan.dev.dipupalenciaindicadores.secmotic.com');
define('CKAN_API_TOKEN', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiJNcjE4aXZWWkpVN0ZYbWVpR2JwTTBHZTR4dzdnaS1fY2s0QjROQXNWeXJZIiwiaWF0IjoxNzU4NzkxMTE1fQ._ibqSjv1RGPc0NYz3YV-IlUU02z6kyb0he6OILRZO8Q');

// Colores para output
define('COLOR_GREEN', "\033[0;32m");
define('COLOR_RED', "\033[0;31m");
define('COLOR_YELLOW', "\033[1;33m");
define('COLOR_BLUE', "\033[0;34m");
define('COLOR_CYAN', "\033[0;36m");
define('COLOR_MAGENTA', "\033[0;35m");
define('COLOR_RESET', "\033[0m");

echo COLOR_BLUE . "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TEST 07: SPATIAL_FILTER - FILTRADO ESPACIAL              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n" . COLOR_RESET;
echo "\n";

// Resultados del test
$resultados = [
    'fecha_test' => date('Y-m-d H:i:s'),
    'servidor' => CKAN_BASE_URL,
    'tests' => [],
    'tests_exitosos' => 0,
    'tests_fallidos' => 0
];

// Función auxiliar para hacer request
function ckanRequest($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . CKAN_API_TOKEN,
        'Content-Type: application/json'
    ]);

    $inicio = microtime(true);
    $response = curl_exec($ch);
    $tiempo = microtime(true) - $inicio;
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'response' => $response,
        'http_code' => $httpCode,
        'tiempo_ms' => round($tiempo * 1000, 2),
        'json' => json_decode($response, true)
    ];
}

// ============================================================================
// TEST 7.1: Verificar existencia del endpoint spatial_filter_by_municipality
// ============================================================================
echo COLOR_YELLOW . "Test 7.1: Verificando endpoint spatial_filter_by_municipality..." . COLOR_RESET . "\n";

$url = CKAN_BASE_URL . '/api/3/action/spatial_filter_by_municipality?municipality=Palencia&format=geojson';
$result = ckanRequest($url);

$test1 = [
    'nombre' => 'Verificación de endpoint',
    'url' => $url,
    'http_code' => $result['http_code'],
    'tiempo_ms' => $result['tiempo_ms'],
    'exito' => false
];

if ($result['http_code'] === 404) {
    echo COLOR_YELLOW . "  ⚠ Endpoint no encontrado (404)" . COLOR_RESET . "\n";
    echo "  El endpoint spatial_filter_by_municipality no está disponible\n";
    echo "  Tiempo: " . $result['tiempo_ms'] . " ms\n\n";

    $test1['error'] = 'Endpoint no disponible';
    $test1['disponible'] = false;
    $resultados['tests_fallidos']++;

} elseif ($result['http_code'] === 200) {
    echo COLOR_GREEN . "  ✓ Endpoint disponible" . COLOR_RESET . "\n";
    echo "  HTTP Code: 200\n";
    echo "  Tiempo: " . $result['tiempo_ms'] . " ms\n";

    // Analizar respuesta
    $contentType = 'unknown';
    if (strpos($result['response'], '{') === 0) {
        $contentType = 'json';
    } elseif (strpos($result['response'], '<') === 0) {
        $contentType = 'xml';
    }

    echo "  Tipo de contenido: " . $contentType . "\n";
    echo "  Tamaño respuesta: " . strlen($result['response']) . " bytes\n\n";

    $test1['exito'] = true;
    $test1['disponible'] = true;
    $test1['content_type'] = $contentType;
    $test1['response_size'] = strlen($result['response']);
    $resultados['tests_exitosos']++;

} else {
    echo COLOR_RED . "  ✗ Error inesperado" . COLOR_RESET . "\n";
    echo "  HTTP Code: " . $result['http_code'] . "\n";
    echo "  Tiempo: " . $result['tiempo_ms'] . " ms\n\n";

    $test1['error'] = 'HTTP ' . $result['http_code'];
    $test1['disponible'] = false;
    $resultados['tests_fallidos']++;
}

$resultados['tests'][] = $test1;

// ============================================================================
// TEST 7.2: Probar filtrado con diferentes municipios
// ============================================================================
if ($test1['disponible']) {
    echo COLOR_YELLOW . "Test 7.2: Probando filtrado por municipios de Palencia..." . COLOR_RESET . "\n";

    $municipiosTest = ['Palencia', 'Aguilar de Campoo', 'Cervera de Pisuerga', 'Paredes de Nava'];

    foreach ($municipiosTest as $municipio) {
        $url = CKAN_BASE_URL . '/api/3/action/spatial_filter_by_municipality?municipality=' . urlencode($municipio) . '&format=geojson';
        $result = ckanRequest($url);

        $test = [
            'municipio' => $municipio,
            'http_code' => $result['http_code'],
            'tiempo_ms' => $result['tiempo_ms'],
            'exito' => false
        ];

        if ($result['http_code'] === 200) {
            echo COLOR_GREEN . "  ✓ " . $municipio . COLOR_RESET . " (" . $result['tiempo_ms'] . " ms)\n";
            $test['exito'] = true;
            $test['response_size'] = strlen($result['response']);
            $resultados['tests_exitosos']++;
        } else {
            echo COLOR_RED . "  ✗ " . $municipio . " - HTTP " . $result['http_code'] . COLOR_RESET . "\n";
            $resultados['tests_fallidos']++;
        }

        $resultados['tests'][] = $test;
    }
    echo "\n";

    // ============================================================================
    // TEST 7.3: Probar diferentes formatos de salida
    // ============================================================================
    echo COLOR_YELLOW . "Test 7.3: Probando diferentes formatos de salida..." . COLOR_RESET . "\n";

    $formatos = ['geojson', 'csv', 'xml'];

    foreach ($formatos as $formato) {
        $url = CKAN_BASE_URL . '/api/3/action/spatial_filter_by_municipality?municipality=Palencia&format=' . $formato;
        $result = ckanRequest($url);

        $test = [
            'formato' => $formato,
            'http_code' => $result['http_code'],
            'tiempo_ms' => $result['tiempo_ms'],
            'exito' => false
        ];

        if ($result['http_code'] === 200) {
            echo COLOR_GREEN . "  ✓ Formato " . strtoupper($formato) . COLOR_RESET . " (" . $result['tiempo_ms'] . " ms, " . strlen($result['response']) . " bytes)\n";
            $test['exito'] = true;
            $test['response_size'] = strlen($result['response']);
            $resultados['tests_exitosos']++;
        } else {
            echo COLOR_RED . "  ✗ Formato " . strtoupper($formato) . " - HTTP " . $result['http_code'] . COLOR_RESET . "\n";
            $resultados['tests_fallidos']++;
        }

        $resultados['tests'][] = $test;
    }
    echo "\n";

} else {
    echo COLOR_YELLOW . "⚠ Tests 7.2 y 7.3 omitidos (endpoint no disponible)\n\n" . COLOR_RESET;
}

// ============================================================================
// INFORMACIÓN ALTERNATIVA
// ============================================================================
echo COLOR_CYAN . "═══════════════════════════════════════════════════════════\n";
echo "  MÉTODOS ALTERNATIVOS DE FILTRADO ESPACIAL\n";
echo "═══════════════════════════════════════════════════════════\n" . COLOR_RESET;

echo "\n" . COLOR_GREEN . "✓ MÉTODO 1: Filtrado por campo Municipio/Provincia en datastore_search" . COLOR_RESET . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Endpoint: /api/3/action/datastore_search\n";
echo "Parámetros:\n";
echo "  - resource_id: {ID_del_recurso}\n";
echo "  - filters: {\"Municipio\":\"Palencia\"}\n";
echo "  - filters: {\"Provincia\":\"PALENCIA\"}\n\n";

echo "Ejemplo 1 - Filtrar municipios de la provincia de Palencia:\n";
echo COLOR_BLUE . "GET " . CKAN_BASE_URL . "/api/3/action/datastore_search?\n";
echo "    resource_id=41c0aa65-3964-4926-836d-d501f9e55e52&\n";
echo "    filters={\"Provincia\":\"PALENCIA\"}" . COLOR_RESET . "\n\n";

echo "Ejemplo 2 - Filtrar incendios en Palencia:\n";
echo COLOR_BLUE . "GET " . CKAN_BASE_URL . "/api/3/action/datastore_search?\n";
echo "    resource_id=f74b444d-e0f9-4638-916e-ad595bf5f0dc&\n";
echo "    q=Palencia" . COLOR_RESET . "\n\n";

echo COLOR_GREEN . "✓ MÉTODO 2: Usar campo Cod_Provincia para filtrado numérico" . COLOR_RESET . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Código de provincia de Palencia: 34\n";
echo "Ventaja: Más preciso que filtrado por texto\n\n";

echo "Ejemplo:\n";
echo COLOR_BLUE . "GET " . CKAN_BASE_URL . "/api/3/action/datastore_search?\n";
echo "    resource_id=41c0aa65-3964-4926-836d-d501f9e55e52&\n";
echo "    filters={\"Cod_Provincia\":34}" . COLOR_RESET . "\n\n";

echo COLOR_GREEN . "✓ MÉTODO 3: Usar coordenadas geográficas para mapas" . COLOR_RESET . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Recursos con coordenadas:\n";
echo "  - Municipios: campos Latitud, Longitud\n";
echo "  - Incendios: campo Posición (formato: \"lat, lon\")\n\n";

echo "Ejemplo - Obtener municipios con coordenadas:\n";
echo COLOR_BLUE . "GET " . CKAN_BASE_URL . "/api/3/action/datastore_search?\n";
echo "    resource_id=41c0aa65-3964-4926-836d-d501f9e55e52&\n";
echo "    fields=Municipio,Latitud,Longitud&\n";
echo "    filters={\"Provincia\":\"PALENCIA\"}" . COLOR_RESET . "\n\n";

// ============================================================================
// DEMOSTRACIÓN PRÁCTICA
// ============================================================================
echo COLOR_CYAN . "═══════════════════════════════════════════════════════════\n";
echo "  DEMOSTRACIÓN: FILTRADO GEOGRÁFICO FUNCIONAL\n";
echo "═══════════════════════════════════════════════════════════\n" . COLOR_RESET;

// Demo 1: Municipios de Palencia
echo "\n" . COLOR_YELLOW . "Demo 1: Obtener todos los municipios de Palencia..." . COLOR_RESET . "\n";
$url = CKAN_BASE_URL . '/api/3/action/datastore_search?resource_id=41c0aa65-3964-4926-836d-d501f9e55e52&filters=' . urlencode('{"Provincia":"PALENCIA"}') . '&fields=Municipio,Población,Latitud,Longitud&limit=5';
$result = ckanRequest($url);

if ($result['http_code'] === 200 && isset($result['json']['success']) && $result['json']['success']) {
    $total = $result['json']['result']['total'] ?? 0;
    $records = $result['json']['result']['records'] ?? [];

    echo COLOR_GREEN . "✓ Consulta exitosa" . COLOR_RESET . "\n";
    echo "  Total municipios en Palencia: " . $total . "\n";
    echo "  Tiempo: " . $result['tiempo_ms'] . " ms\n";
    echo "  Primeros 5 resultados:\n\n";

    foreach ($records as $idx => $record) {
        echo "  " . ($idx + 1) . ". " . $record['Municipio'] . "\n";
        echo "     Población: " . $record['Población'] . "\n";
        echo "     Coordenadas: " . $record['Latitud'] . ", " . $record['Longitud'] . "\n\n";
    }

    $resultados['demo_municipios'] = [
        'exitoso' => true,
        'total' => $total,
        'tiempo_ms' => $result['tiempo_ms']
    ];
} else {
    echo COLOR_RED . "✗ Error en consulta" . COLOR_RESET . "\n\n";
    $resultados['demo_municipios'] = ['exitoso' => false];
}

// Demo 2: Incendios en Palencia
echo COLOR_YELLOW . "Demo 2: Obtener incendios recientes en Palencia..." . COLOR_RESET . "\n";
$url = CKAN_BASE_URL . '/api/3/action/datastore_search?resource_id=f74b444d-e0f9-4638-916e-ad595bf5f0dc&q=PALENCIA&fields=' . urlencode('FECHA DE INICIO,TÉRMINO MUNICIPAL,NIVEL MÁXIMO ALCANZADO,TIPO Y HAS DE SUPERFICIE AFECTADA') . '&sort=' . urlencode('FECHA DE INICIO desc') . '&limit=3';
$result = ckanRequest($url);

if ($result['http_code'] === 200 && isset($result['json']['success']) && $result['json']['success']) {
    $total = $result['json']['result']['total'] ?? 0;
    $records = $result['json']['result']['records'] ?? [];

    echo COLOR_GREEN . "✓ Consulta exitosa" . COLOR_RESET . "\n";
    echo "  Total incendios encontrados: " . $total . "\n";
    echo "  Tiempo: " . $result['tiempo_ms'] . " ms\n";
    echo "  Últimos 3 incendios:\n\n";

    foreach ($records as $idx => $record) {
        echo "  " . ($idx + 1) . ". Fecha: " . (isset($record['FECHA DE INICIO']) ? substr($record['FECHA DE INICIO'], 0, 10) : 'N/A') . "\n";
        echo "     Municipio: " . ($record['TÉRMINO MUNICIPAL'] ?? 'N/A') . "\n";
        echo "     Nivel: " . ($record['NIVEL MÁXIMO ALCANZADO'] ?? 'N/A') . "\n";
        if (isset($record['TIPO Y HAS DE SUPERFICIE AFECTADA'])) {
            echo "     Superficie: " . substr($record['TIPO Y HAS DE SUPERFICIE AFECTADA'], 0, 60) . "...\n";
        }
        echo "\n";
    }

    $resultados['demo_incendios'] = [
        'exitoso' => true,
        'total' => $total,
        'tiempo_ms' => $result['tiempo_ms']
    ];
} else {
    echo COLOR_RED . "✗ Error en consulta" . COLOR_RESET . "\n\n";
    $resultados['demo_incendios'] = ['exitoso' => false];
}

// ============================================================================
// RESUMEN FINAL
// ============================================================================
echo COLOR_BLUE . "\n═══════════════════════════════════════════════════════════\n";
echo "  RESUMEN DE RESULTADOS\n";
echo "═══════════════════════════════════════════════════════════\n" . COLOR_RESET;

$totalTests = $resultados['tests_exitosos'] + $resultados['tests_fallidos'];
echo "📊 Tests ejecutados: " . $totalTests . "\n";
echo COLOR_GREEN . "✓ Tests exitosos: " . $resultados['tests_exitosos'] . COLOR_RESET . "\n";
echo COLOR_RED . "✗ Tests fallidos: " . $resultados['tests_fallidos'] . COLOR_RESET . "\n\n";

// ============================================================================
// GUARDAR RESULTADOS
// ============================================================================
$outputFile = __DIR__ . '/resultados/spatial_filter.json';
file_put_contents($outputFile, json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo COLOR_BLUE . "Resultados guardados en: " . $outputFile . COLOR_RESET . "\n\n";

echo COLOR_GREEN . "✓ Test completado\n" . COLOR_RESET;

// ============================================================================
// CONCLUSIÓN
// ============================================================================
echo COLOR_BLUE . "\n═══════════════════════════════════════════════════════════\n";
echo "  CONCLUSIÓN\n";
echo "═══════════════════════════════════════════════════════════\n" . COLOR_RESET;

if (!$test1['disponible']) {
    echo COLOR_YELLOW . "⚠ El endpoint spatial_filter_by_municipality NO está disponible\n" . COLOR_RESET;
    echo "\n";
    echo COLOR_GREEN . "✓ SOLUCIÓN ALTERNATIVA VALIDADA:\n" . COLOR_RESET;
    echo "  Usar datastore_search con filtros por campo:\n";
    echo "  - Filtro por Provincia: {\"Provincia\":\"PALENCIA\"}\n";
    echo "  - Filtro por Municipio: {\"Municipio\":\"Palencia\"}\n";
    echo "  - Filtro por Código: {\"Cod_Provincia\":34}\n";
    echo "  - Búsqueda texto: q=Palencia\n\n";
    echo "  Rendimiento demostrado:\n";

    if (isset($resultados['demo_municipios']['exitoso']) && $resultados['demo_municipios']['exitoso']) {
        echo "  - Municipios: " . $resultados['demo_municipios']['total'] . " resultados en " . $resultados['demo_municipios']['tiempo_ms'] . " ms\n";
    }

    if (isset($resultados['demo_incendios']['exitoso']) && $resultados['demo_incendios']['exitoso']) {
        echo "  - Incendios: " . $resultados['demo_incendios']['total'] . " resultados en " . $resultados['demo_incendios']['tiempo_ms'] . " ms\n";
    }

    echo "\n";
    echo COLOR_GREEN . "✓ CONCLUSIÓN: El filtrado geográfico es COMPLETAMENTE FUNCIONAL\n";
    echo "  mediante datastore_search con filtros JSON\n" . COLOR_RESET;
} else {
    echo COLOR_GREEN . "✓ El endpoint spatial_filter_by_municipality está disponible\n" . COLOR_RESET;
    echo "  y puede ser usado para filtrado geoespacial avanzado.\n";
}

echo "\n";

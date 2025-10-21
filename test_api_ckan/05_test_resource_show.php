<?php
/**
 * TEST 05: RESOURCE_SHOW - DETALLE DE RECURSOS - API CKAN
 *
 * Objetivo: Obtener información detallada de recursos específicos
 *
 * Verifica:
 * - Metadata completa de recursos
 * - URLs de descarga
 * - Tamaños de archivos
 * - Formatos y tipos MIME
 * - Estado del datastore
 * - Fechas de creación y modificación
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
echo "║  TEST 05: RESOURCE_SHOW - DETALLE DE RECURSOS             ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n" . COLOR_RESET;
echo "\n";

// Recursos a examinar (selección estratégica por tipo)
$recursosTest = [
    [
        'id' => '41c0aa65-3964-4926-836d-d501f9e55e52',
        'nombre' => 'Municipios de Castilla y León (CSV)',
        'tipo' => 'CSV consultable',
        'importancia' => 'CRÍTICO - Base para filtros geográficos'
    ],
    [
        'id' => 'f74b444d-e0f9-4638-916e-ad595bf5f0dc',
        'nombre' => 'Incendios forestales (CSV)',
        'tipo' => 'CSV consultable',
        'importancia' => 'ALTA - Datos ambientales históricos'
    ],
    [
        'id' => 'c20d3a73-b678-4472-830a-b01037a00654',
        'nombre' => 'Miradores en espacios naturales (SHP)',
        'tipo' => 'SHP geoespacial',
        'importancia' => 'MEDIA - Datos geográficos'
    ],
    [
        'id' => '85116a07-08ea-4d2e-9452-225994031d4e',
        'nombre' => 'Asociaciones Forestales (XLS)',
        'tipo' => 'XLS consultable',
        'importancia' => 'MEDIA - Datos de asociaciones'
    ]
];

// Resultados del test
$resultados = [
    'fecha_test' => date('Y-m-d H:i:s'),
    'servidor' => CKAN_BASE_URL,
    'recursos_analizados' => [],
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

// Función para formatear tamaño
function formatSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// ============================================================================
// ANALIZAR CADA RECURSO
// ============================================================================

foreach ($recursosTest as $idx => $recursoTest) {
    echo COLOR_MAGENTA . "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║  RECURSO " . ($idx + 1) . "/4: " . str_pad(substr($recursoTest['nombre'], 0, 42), 42) . " ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n" . COLOR_RESET;
    echo "ID: " . $recursoTest['id'] . "\n";
    echo "Tipo: " . $recursoTest['tipo'] . "\n";
    echo "Importancia: " . $recursoTest['importancia'] . "\n\n";

    echo COLOR_YELLOW . "Obteniendo información detallada del recurso..." . COLOR_RESET . "\n";

    // Hacer request
    $url = CKAN_BASE_URL . '/api/3/action/resource_show?id=' . $recursoTest['id'];
    $result = ckanRequest($url);

    if ($result['http_code'] !== 200 || !isset($result['json']['success']) || !$result['json']['success']) {
        echo COLOR_RED . "✗ Error al obtener recurso" . COLOR_RESET . "\n";
        echo "  HTTP Code: " . $result['http_code'] . "\n";
        if (isset($result['json']['error'])) {
            echo "  Error: " . json_encode($result['json']['error']) . "\n";
        }
        $resultados['tests_fallidos']++;
        continue;
    }

    $resource = $result['json']['result'];
    echo COLOR_GREEN . "✓ Recurso obtenido exitosamente" . COLOR_RESET . " (" . $result['tiempo_ms'] . " ms)\n\n";
    $resultados['tests_exitosos']++;

    // Extraer información clave
    $recursoInfo = [
        'id' => $resource['id'],
        'package_id' => $resource['package_id'] ?? null,
        'name' => $resource['name'],
        'description' => $resource['description'] ?? '',
        'format' => strtoupper($resource['format'] ?? 'UNKNOWN'),
        'mimetype' => $resource['mimetype'] ?? null,
        'mimetype_inner' => $resource['mimetype_inner'] ?? null,
        'size' => $resource['size'] ?? null,
        'hash' => $resource['hash'] ?? null,
        'url' => $resource['url'] ?? null,
        'url_type' => $resource['url_type'] ?? null,
        'datastore_active' => $resource['datastore_active'] ?? false,
        'state' => $resource['state'] ?? null,
        'created' => $resource['created'] ?? null,
        'last_modified' => $resource['last_modified'] ?? null,
        'cache_url' => $resource['cache_url'] ?? null,
        'cache_last_updated' => $resource['cache_last_updated'] ?? null
    ];

    // Mostrar información general
    echo COLOR_CYAN . "📋 INFORMACIÓN GENERAL\n" . COLOR_RESET;
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "  Nombre: " . $recursoInfo['name'] . "\n";
    echo "  Formato: " . COLOR_GREEN . $recursoInfo['format'] . COLOR_RESET;

    if ($recursoInfo['datastore_active']) {
        echo COLOR_GREEN . " ⭐ CONSULTABLE VÍA API" . COLOR_RESET;
    }
    echo "\n";

    echo "  Estado: " . ($recursoInfo['state'] ?? 'N/A') . "\n";

    if ($recursoInfo['size']) {
        echo "  Tamaño: " . formatSize($recursoInfo['size']) . "\n";
    }

    echo "\n";

    // Descripción
    if (!empty($recursoInfo['description'])) {
        echo COLOR_CYAN . "📝 DESCRIPCIÓN\n" . COLOR_RESET;
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $desc = strip_tags($recursoInfo['description']);
        echo "  " . (strlen($desc) > 200 ? substr($desc, 0, 200) . "..." : $desc) . "\n\n";
    }

    // Información técnica
    echo COLOR_CYAN . "🔧 INFORMACIÓN TÉCNICA\n" . COLOR_RESET;
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    if ($recursoInfo['mimetype']) {
        echo "  MIME Type: " . $recursoInfo['mimetype'] . "\n";
    }

    if ($recursoInfo['mimetype_inner']) {
        echo "  MIME Inner: " . $recursoInfo['mimetype_inner'] . "\n";
    }

    if ($recursoInfo['hash']) {
        echo "  Hash: " . substr($recursoInfo['hash'], 0, 16) . "...\n";
    }

    echo "  URL Type: " . ($recursoInfo['url_type'] ?? 'N/A') . "\n";
    echo "  Datastore Active: " . ($recursoInfo['datastore_active'] ? COLOR_GREEN . 'YES' . COLOR_RESET : 'NO') . "\n";
    echo "\n";

    // URLs y acceso
    echo COLOR_CYAN . "🔗 ACCESO Y DESCARGA\n" . COLOR_RESET;
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    if ($recursoInfo['url']) {
        $urlParts = parse_url($recursoInfo['url']);
        $shortUrl = $urlParts['host'] . $urlParts['path'];
        if (strlen($shortUrl) > 60) {
            $shortUrl = substr($shortUrl, 0, 57) . "...";
        }
        echo "  URL: " . $shortUrl . "\n";
    }

    if ($recursoInfo['cache_url']) {
        echo "  Cache URL: Disponible\n";
        if ($recursoInfo['cache_last_updated']) {
            echo "  Cache actualizado: " . date('Y-m-d H:i', strtotime($recursoInfo['cache_last_updated'])) . "\n";
        }
    }

    echo "\n";

    // Fechas
    echo COLOR_CYAN . "📅 FECHAS\n" . COLOR_RESET;
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    if ($recursoInfo['created']) {
        echo "  Creado: " . date('Y-m-d H:i:s', strtotime($recursoInfo['created'])) . "\n";
    }

    if ($recursoInfo['last_modified']) {
        echo "  Última modificación: " . date('Y-m-d H:i:s', strtotime($recursoInfo['last_modified'])) . "\n";

        // Calcular antigüedad
        $lastMod = strtotime($recursoInfo['last_modified']);
        $now = time();
        $diff = $now - $lastMod;
        $days = floor($diff / 86400);

        if ($days == 0) {
            echo "  Antigüedad: " . COLOR_GREEN . "HOY" . COLOR_RESET . "\n";
        } elseif ($days < 7) {
            echo "  Antigüedad: " . COLOR_GREEN . $days . " día(s)" . COLOR_RESET . "\n";
        } elseif ($days < 30) {
            echo "  Antigüedad: " . COLOR_YELLOW . floor($days/7) . " semana(s)" . COLOR_RESET . "\n";
        } elseif ($days < 365) {
            echo "  Antigüedad: " . COLOR_YELLOW . floor($days/30) . " mes(es)" . COLOR_RESET . "\n";
        } else {
            echo "  Antigüedad: " . COLOR_RED . floor($days/365) . " año(s)" . COLOR_RESET . "\n";
        }
    }

    echo "\n";

    // Modo de uso
    echo COLOR_CYAN . "💡 MODO DE USO RECOMENDADO\n" . COLOR_RESET;
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    if ($recursoInfo['datastore_active']) {
        echo COLOR_GREEN . "  ✓ CONSULTA VÍA API (datastore_search)" . COLOR_RESET . "\n";
        echo "    Endpoint: /api/3/action/datastore_search\n";
        echo "    Parámetros: resource_id=" . $recursoInfo['id'] . "\n";
        echo "    Capacidades: filtros, paginación, ordenamiento\n";
        echo "    Ejemplo: ?resource_id=" . $recursoInfo['id'] . "&limit=10&q=Palencia\n";
    } else {
        echo COLOR_YELLOW . "  ⚠ DESCARGA DIRECTA (sin API)" . COLOR_RESET . "\n";
        echo "    Método: HTTP GET/Download\n";
        echo "    URL: " . ($recursoInfo['url'] ?? 'N/A') . "\n";
        if ($recursoInfo['format'] === 'SHP') {
            echo "    Procesamiento: Requiere biblioteca GIS (GDAL, Shapely)\n";
            echo "    Conversión: Puede convertirse a GeoJSON para mapas web\n";
        }
    }

    echo "\n";

    // Verificar acceso real (solo para primeros 2 recursos)
    if ($idx < 2 && $recursoInfo['datastore_active']) {
        echo COLOR_CYAN . "🧪 VERIFICACIÓN DE ACCESO\n" . COLOR_RESET;
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  Probando acceso a datos...\n";

        $testUrl = CKAN_BASE_URL . '/api/3/action/datastore_search?resource_id=' . $recursoInfo['id'] . '&limit=1';
        $testResult = ckanRequest($testUrl);

        if ($testResult['http_code'] === 200 && isset($testResult['json']['success']) && $testResult['json']['success']) {
            $total = $testResult['json']['result']['total'] ?? 0;
            $fields = count($testResult['json']['result']['fields'] ?? []);

            echo COLOR_GREEN . "  ✓ Acceso verificado" . COLOR_RESET . "\n";
            echo "    Total de registros: " . number_format($total) . "\n";
            echo "    Campos disponibles: " . $fields . "\n";
            echo "    Tiempo de respuesta: " . $testResult['tiempo_ms'] . " ms\n";

            $recursoInfo['test_acceso'] = [
                'exitoso' => true,
                'total_registros' => $total,
                'num_campos' => $fields,
                'tiempo_ms' => $testResult['tiempo_ms']
            ];
        } else {
            echo COLOR_RED . "  ✗ Error al acceder a datos" . COLOR_RESET . "\n";
            $recursoInfo['test_acceso'] = ['exitoso' => false];
        }
        echo "\n";
    }

    // Guardar resultados
    $resultados['recursos_analizados'][] = $recursoInfo;
}

// ============================================================================
// RESUMEN FINAL
// ============================================================================
echo COLOR_BLUE . "\n═══════════════════════════════════════════════════════════\n";
echo "  RESUMEN DE RESULTADOS\n";
echo "═══════════════════════════════════════════════════════════\n" . COLOR_RESET;

echo "📊 Recursos analizados: " . count($resultados['recursos_analizados']) . "\n";
echo COLOR_GREEN . "✓ Tests exitosos: " . $resultados['tests_exitosos'] . COLOR_RESET . "\n";
echo COLOR_RED . "✗ Tests fallidos: " . $resultados['tests_fallidos'] . COLOR_RESET . "\n\n";

// Estadísticas
$totalSize = 0;
$consultables = 0;
$formatos = [];

foreach ($resultados['recursos_analizados'] as $rec) {
    if ($rec['size']) {
        $totalSize += $rec['size'];
    }
    if ($rec['datastore_active']) {
        $consultables++;
    }
    $formatos[$rec['format']] = ($formatos[$rec['format']] ?? 0) + 1;
}

echo COLOR_CYAN . "📈 ESTADÍSTICAS:\n" . COLOR_RESET;
echo "  Recursos consultables: " . $consultables . " de " . count($resultados['recursos_analizados']);
echo " (" . round(($consultables / count($resultados['recursos_analizados'])) * 100, 1) . "%)\n";
echo "  Tamaño total: " . formatSize($totalSize) . "\n";
echo "  Formatos:\n";
foreach ($formatos as $formato => $cantidad) {
    echo "    - " . str_pad($formato, 10) . ": " . $cantidad . "\n";
}
echo "\n";

// ============================================================================
// GUARDAR RESULTADOS
// ============================================================================
$outputFile = __DIR__ . '/resultados/resource_show.json';
file_put_contents($outputFile, json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo COLOR_BLUE . "Resultados guardados en: " . $outputFile . COLOR_RESET . "\n\n";

echo COLOR_GREEN . "✓ Test completado\n" . COLOR_RESET;

// ============================================================================
// RECOMENDACIONES
// ============================================================================
echo COLOR_BLUE . "\n═══════════════════════════════════════════════════════════\n";
echo "  RECOMENDACIONES TÉCNICAS\n";
echo "═══════════════════════════════════════════════════════════\n" . COLOR_RESET;

echo "1. Priorizar recursos con datastore_active: true para dashboards\n";
echo "2. Implementar caché para metadata de recursos (TTL: 24 horas)\n";
echo "3. Para archivos SHP, considerar conversión a GeoJSON server-side\n";
echo "4. Monitorear tamaños de recursos antes de descargar\n";
echo "5. Validar URLs antes de intentar descarga\n";
echo "6. Usar resource_show para validar disponibilidad antes de consultas\n\n";

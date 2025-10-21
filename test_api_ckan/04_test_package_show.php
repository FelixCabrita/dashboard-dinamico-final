<?php
/**
 * TEST 04: PACKAGE_SHOW - DETALLE DE DATASETS - API CKAN
 *
 * Objetivo: Obtener información completa y detallada de datasets específicos
 *
 * Verifica:
 * - Metadata completa de datasets
 * - Lista de recursos asociados
 * - Información de organización
 * - Tags y categorías
 * - Fechas de creación y modificación
 * - Licencias y permisos
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
echo "║  TEST 04: PACKAGE_SHOW - DETALLE DE DATASETS              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n" . COLOR_RESET;
echo "\n";

// Datasets a examinar en detalle (selección estratégica)
$datasetsTest = [
    [
        'id' => '986f7a3f-c47f-469c-aa7d-f60dd4377e43',
        'name' => 'demografia_y_empleo',
        'motivo' => 'Dataset público con múltiples recursos CSV consultables'
    ],
    [
        'id' => 'a1b57aaa-ffc1-4378-9bd4-31b2a45028e3',
        'name' => 'turismo_y_patrimonio',
        'motivo' => 'Dataset privado con recursos mixtos (CSV + SHP)'
    ],
    [
        'id' => 'c8e7d4c5-8b47-4e3a-9f5e-2d3c1a0b9e8f',
        'name' => 'general',
        'motivo' => 'Dataset con recurso crítico (municipios)'
    ]
];

// Resultados del test
$resultados = [
    'fecha_test' => date('Y-m-d H:i:s'),
    'servidor' => CKAN_BASE_URL,
    'datasets_analizados' => [],
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
// ANALIZAR CADA DATASET
// ============================================================================

foreach ($datasetsTest as $idx => $datasetTest) {
    echo COLOR_MAGENTA . "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║  DATASET " . ($idx + 1) . "/3: " . str_pad(substr($datasetTest['name'], 0, 43), 43) . " ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n" . COLOR_RESET;
    echo "ID: " . $datasetTest['id'] . "\n";
    echo "Motivo: " . $datasetTest['motivo'] . "\n\n";

    echo COLOR_YELLOW . "Obteniendo información detallada del dataset..." . COLOR_RESET . "\n";

    // Hacer request usando el ID
    $url = CKAN_BASE_URL . '/api/3/action/package_show?id=' . $datasetTest['id'];
    $result = ckanRequest($url);

    if ($result['http_code'] !== 200 || !isset($result['json']['success']) || !$result['json']['success']) {
        echo COLOR_RED . "✗ Error al obtener dataset" . COLOR_RESET . "\n";
        echo "  HTTP Code: " . $result['http_code'] . "\n";
        if (isset($result['json']['error'])) {
            echo "  Error: " . json_encode($result['json']['error']) . "\n";
        }
        $resultados['tests_fallidos']++;
        continue;
    }

    $package = $result['json']['result'];
    echo COLOR_GREEN . "✓ Dataset obtenido exitosamente" . COLOR_RESET . " (" . $result['tiempo_ms'] . " ms)\n\n";
    $resultados['tests_exitosos']++;

    // Extraer información clave
    $datasetInfo = [
        'id' => $package['id'],
        'name' => $package['name'],
        'title' => $package['title'],
        'notes' => $package['notes'] ?? '',
        'metadata_created' => $package['metadata_created'] ?? null,
        'metadata_modified' => $package['metadata_modified'] ?? null,
        'author' => $package['author'] ?? null,
        'author_email' => $package['author_email'] ?? null,
        'license_id' => $package['license_id'] ?? null,
        'license_title' => $package['license_title'] ?? null,
        'private' => $package['private'] ?? false,
        'state' => $package['state'] ?? null,
        'type' => $package['type'] ?? null,
        'num_resources' => $package['num_resources'] ?? 0,
        'num_tags' => $package['num_tags'] ?? 0,
        'organization' => [
            'id' => $package['organization']['id'] ?? null,
            'name' => $package['organization']['name'] ?? null,
            'title' => $package['organization']['title'] ?? null,
            'description' => $package['organization']['description'] ?? null,
            'image_url' => $package['organization']['image_url'] ?? null
        ],
        'tags' => array_map(function($tag) {
            return $tag['name'];
        }, $package['tags'] ?? []),
        'groups' => array_map(function($group) {
            return $group['name'];
        }, $package['groups'] ?? []),
        'resources' => [],
        'extras' => $package['extras'] ?? []
    ];

    // Mostrar información general
    echo COLOR_CYAN . "📋 INFORMACIÓN GENERAL\n" . COLOR_RESET;
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "  Título: " . $datasetInfo['title'] . "\n";
    echo "  Nombre: " . $datasetInfo['name'] . "\n";
    echo "  Tipo: " . ($datasetInfo['type'] ?? 'N/A') . "\n";
    echo "  Estado: " . ($datasetInfo['state'] ?? 'N/A') . "\n";
    echo "  Privacidad: " . ($datasetInfo['private'] ? 'PRIVADO' : 'PÚBLICO') . "\n";
    echo "  Creado: " . ($datasetInfo['metadata_created'] ? date('Y-m-d H:i', strtotime($datasetInfo['metadata_created'])) : 'N/A') . "\n";
    echo "  Modificado: " . ($datasetInfo['metadata_modified'] ? date('Y-m-d H:i', strtotime($datasetInfo['metadata_modified'])) : 'N/A') . "\n\n";

    // Descripción
    if (!empty($datasetInfo['notes'])) {
        echo COLOR_CYAN . "📝 DESCRIPCIÓN\n" . COLOR_RESET;
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $desc = strip_tags($datasetInfo['notes']);
        $desc = substr($desc, 0, 200);
        echo "  " . $desc . (strlen($datasetInfo['notes']) > 200 ? "..." : "") . "\n\n";
    }

    // Organización
    echo COLOR_CYAN . "🏢 ORGANIZACIÓN\n" . COLOR_RESET;
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    if ($datasetInfo['organization']['title']) {
        echo "  Nombre: " . $datasetInfo['organization']['title'] . "\n";
        echo "  ID: " . $datasetInfo['organization']['id'] . "\n";
        if ($datasetInfo['organization']['description']) {
            echo "  Descripción: " . substr(strip_tags($datasetInfo['organization']['description']), 0, 100) . "...\n";
        }
    } else {
        echo "  (Sin información de organización)\n";
    }
    echo "\n";

    // Licencia
    if ($datasetInfo['license_title']) {
        echo COLOR_CYAN . "⚖️  LICENCIA\n" . COLOR_RESET;
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  " . $datasetInfo['license_title'] . "\n";
        echo "  ID: " . $datasetInfo['license_id'] . "\n\n";
    }

    // Tags
    if (count($datasetInfo['tags']) > 0) {
        echo COLOR_CYAN . "🏷️  TAGS (" . count($datasetInfo['tags']) . ")\n" . COLOR_RESET;
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  " . implode(', ', $datasetInfo['tags']) . "\n\n";
    }

    // Recursos
    echo COLOR_CYAN . "📁 RECURSOS (" . $datasetInfo['num_resources'] . ")\n" . COLOR_RESET;
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

    foreach ($package['resources'] ?? [] as $resIdx => $resource) {
        $resourceInfo = [
            'id' => $resource['id'],
            'name' => $resource['name'],
            'description' => $resource['description'] ?? '',
            'format' => strtoupper($resource['format'] ?? 'UNKNOWN'),
            'size' => $resource['size'] ?? null,
            'mimetype' => $resource['mimetype'] ?? null,
            'url' => $resource['url'] ?? null,
            'datastore_active' => $resource['datastore_active'] ?? false,
            'created' => $resource['created'] ?? null,
            'last_modified' => $resource['last_modified'] ?? null,
            'url_type' => $resource['url_type'] ?? null
        ];

        $datasetInfo['resources'][] = $resourceInfo;

        echo COLOR_GREEN . "  [" . ($resIdx + 1) . "] " . COLOR_RESET . $resourceInfo['name'] . "\n";
        echo "      ID: " . $resourceInfo['id'] . "\n";
        echo "      Formato: " . $resourceInfo['format'];

        if ($resourceInfo['datastore_active']) {
            echo COLOR_GREEN . " ⭐ CONSULTABLE" . COLOR_RESET;
        }
        echo "\n";

        if ($resourceInfo['size']) {
            $sizeMB = round($resourceInfo['size'] / 1024 / 1024, 2);
            $sizeKB = round($resourceInfo['size'] / 1024, 2);
            echo "      Tamaño: " . ($sizeMB > 1 ? $sizeMB . " MB" : $sizeKB . " KB") . "\n";
        }

        if ($resourceInfo['created']) {
            echo "      Creado: " . date('Y-m-d', strtotime($resourceInfo['created'])) . "\n";
        }

        if ($resourceInfo['last_modified']) {
            echo "      Modificado: " . date('Y-m-d', strtotime($resourceInfo['last_modified'])) . "\n";
        }

        echo "\n";
    }

    // Campos extras
    if (count($datasetInfo['extras']) > 0) {
        echo COLOR_CYAN . "🔧 CAMPOS ADICIONALES (" . count($datasetInfo['extras']) . ")\n" . COLOR_RESET;
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        foreach ($datasetInfo['extras'] as $extra) {
            echo "  " . $extra['key'] . ": " . substr($extra['value'], 0, 50) . "\n";
        }
        echo "\n";
    }

    // Guardar resultados
    $resultados['datasets_analizados'][] = $datasetInfo;
}

// ============================================================================
// RESUMEN FINAL
// ============================================================================
echo COLOR_BLUE . "\n═══════════════════════════════════════════════════════════\n";
echo "  RESUMEN DE RESULTADOS\n";
echo "═══════════════════════════════════════════════════════════\n" . COLOR_RESET;

echo "📊 Datasets analizados: " . count($resultados['datasets_analizados']) . "\n";
echo COLOR_GREEN . "✓ Tests exitosos: " . $resultados['tests_exitosos'] . COLOR_RESET . "\n";
echo COLOR_RED . "✗ Tests fallidos: " . $resultados['tests_fallidos'] . COLOR_RESET . "\n\n";

// Estadísticas generales
$totalRecursos = 0;
$recursosConsultables = 0;
$formatos = [];

foreach ($resultados['datasets_analizados'] as $ds) {
    $totalRecursos += count($ds['resources']);
    foreach ($ds['resources'] as $res) {
        if ($res['datastore_active']) {
            $recursosConsultables++;
        }
        $formatos[$res['format']] = ($formatos[$res['format']] ?? 0) + 1;
    }
}

echo COLOR_CYAN . "📈 ESTADÍSTICAS:\n" . COLOR_RESET;
echo "  Total de recursos analizados: " . $totalRecursos . "\n";
echo "  Recursos consultables: " . $recursosConsultables . " (" . round(($recursosConsultables / $totalRecursos) * 100, 1) . "%)\n";
echo "  Formatos encontrados: " . count($formatos) . "\n";
foreach ($formatos as $formato => $cantidad) {
    echo "    - " . str_pad($formato, 10) . ": " . $cantidad . "\n";
}
echo "\n";

// ============================================================================
// GUARDAR RESULTADOS
// ============================================================================
$outputFile = __DIR__ . '/resultados/package_show.json';
file_put_contents($outputFile, json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo COLOR_BLUE . "Resultados guardados en: " . $outputFile . COLOR_RESET . "\n\n";

echo COLOR_GREEN . "✓ Test completado\n" . COLOR_RESET;

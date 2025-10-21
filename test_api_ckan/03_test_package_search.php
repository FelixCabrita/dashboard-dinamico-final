<?php
/**
 * TEST 03: LISTAR TODOS LOS DATASETS - API CKAN
 *
 * Objetivo: Obtener información completa de todos los datasets disponibles
 *
 * Verifica:
 * - Listado completo de datasets
 * - Información de organizaciones
 * - Recursos asociados por dataset
 * - Clasificación privado/público
 * - Metadata de cada dataset
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
define('COLOR_RESET', "\033[0m");

echo COLOR_BLUE . "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TEST 03: LISTAR TODOS LOS DATASETS - API CKAN            ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n" . COLOR_RESET;
echo "\n";

// Resultados del test
$resultados = [
    'fecha_test' => date('Y-m-d H:i:s'),
    'servidor' => CKAN_BASE_URL,
    'datasets' => [],
    'organizaciones' => [],
    'estadisticas' => []
];

// ============================================================================
// TEST 3.1: Obtener listado completo de datasets
// ============================================================================
echo COLOR_YELLOW . "Test 3.1: Obteniendo listado completo de datasets..." . COLOR_RESET . "\n";

$url = CKAN_BASE_URL . '/api/3/action/package_search?q=*:*&rows=1000&include_private=true';
$inicio = microtime(true);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: ' . CKAN_API_TOKEN,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$tiempo = microtime(true) - $inicio;
curl_close($ch);

$jsonData = json_decode($response, true);

if ($httpCode !== 200 || !isset($jsonData['success']) || $jsonData['success'] !== true) {
    echo COLOR_RED . "  ✗ Error al obtener datasets" . COLOR_RESET . "\n";
    echo "    HTTP Code: " . $httpCode . "\n";
    exit(1);
}

$datasets = $jsonData['result']['results'];
$totalDatasets = $jsonData['result']['count'];

echo COLOR_GREEN . "  ✓ Datasets obtenidos exitosamente" . COLOR_RESET . "\n";
echo "    Total encontrados: " . $totalDatasets . "\n";
echo "    Tiempo: " . round($tiempo * 1000, 2) . " ms\n\n";

// ============================================================================
// TEST 3.2: Procesar información de cada dataset
// ============================================================================
echo COLOR_YELLOW . "Test 3.2: Procesando información de cada dataset..." . COLOR_RESET . "\n\n";

$organizaciones = [];
$contadorPrivados = 0;
$contadorPublicos = 0;
$totalRecursos = 0;
$recursosPorFormato = [];

foreach ($datasets as $dataset) {
    $datasetInfo = [
        'id' => $dataset['id'],
        'name' => $dataset['name'],
        'title' => $dataset['title'],
        'private' => $dataset['private'] ?? false,
        'organization' => [
            'id' => $dataset['organization']['id'] ?? null,
            'name' => $dataset['organization']['name'] ?? null,
            'title' => $dataset['organization']['title'] ?? null
        ],
        'num_resources' => count($dataset['resources'] ?? []),
        'metadata_created' => $dataset['metadata_created'] ?? null,
        'metadata_modified' => $dataset['metadata_modified'] ?? null,
        'notes' => $dataset['notes'] ?? '',
        'tags' => array_map(function($tag) {
            return $tag['name'];
        }, $dataset['tags'] ?? []),
        'resources' => []
    ];

    // Contar privados/públicos
    if ($dataset['private']) {
        $contadorPrivados++;
    } else {
        $contadorPublicos++;
    }

    // Procesar recursos
    foreach ($dataset['resources'] ?? [] as $resource) {
        $formato = strtoupper($resource['format'] ?? 'UNKNOWN');

        $resourceInfo = [
            'id' => $resource['id'],
            'name' => $resource['name'],
            'format' => $formato,
            'size' => $resource['size'] ?? null,
            'datastore_active' => $resource['datastore_active'] ?? false,
            'url' => $resource['url'] ?? null,
            'created' => $resource['created'] ?? null,
            'last_modified' => $resource['last_modified'] ?? null
        ];

        $datasetInfo['resources'][] = $resourceInfo;
        $totalRecursos++;

        // Contar por formato
        if (!isset($recursosPorFormato[$formato])) {
            $recursosPorFormato[$formato] = 0;
        }
        $recursosPorFormato[$formato]++;
    }

    // Guardar info del dataset
    $resultados['datasets'][] = $datasetInfo;

    // Recopilar información de organizaciones
    if (isset($dataset['organization']) && $dataset['organization']) {
        $orgId = $dataset['organization']['id'];

        if (!isset($organizaciones[$orgId])) {
            $organizaciones[$orgId] = [
                'id' => $orgId,
                'name' => $dataset['organization']['name'],
                'title' => $dataset['organization']['title'],
                'num_datasets' => 0,
                'datasets' => []
            ];
        }

        $organizaciones[$orgId]['num_datasets']++;
        $organizaciones[$orgId]['datasets'][] = $dataset['id'];
    }

    // Output progress
    echo COLOR_CYAN . "  [" . str_pad(count($resultados['datasets']), 2, '0', STR_PAD_LEFT) . "/" . str_pad($totalDatasets, 2, '0', STR_PAD_LEFT) . "] " . COLOR_RESET;
    echo $datasetInfo['title'] . "\n";
    echo "         Organización: " . ($datasetInfo['organization']['title'] ?? 'N/A') . "\n";
    echo "         Recursos: " . $datasetInfo['num_resources'];
    echo " | Visibilidad: " . ($datasetInfo['private'] ? 'Privado' : 'Público') . "\n";

    if ($datasetInfo['num_resources'] > 0) {
        $formatos = array_unique(array_column($datasetInfo['resources'], 'format'));
        echo "         Formatos: " . implode(', ', $formatos) . "\n";
    }

    echo "\n";
}

// Convertir organizaciones a array indexado
$resultados['organizaciones'] = array_values($organizaciones);

// ============================================================================
// ESTADÍSTICAS
// ============================================================================
echo COLOR_BLUE . "═══════════════════════════════════════════════════════════\n";
echo "  ESTADÍSTICAS GENERALES\n";
echo "═══════════════════════════════════════════════════════════\n" . COLOR_RESET;

$resultados['estadisticas'] = [
    'total_datasets' => $totalDatasets,
    'datasets_privados' => $contadorPrivados,
    'datasets_publicos' => $contadorPublicos,
    'porcentaje_privados' => round(($contadorPrivados / $totalDatasets) * 100, 1),
    'total_organizaciones' => count($resultados['organizaciones']),
    'total_recursos' => $totalRecursos,
    'promedio_recursos_por_dataset' => round($totalRecursos / $totalDatasets, 2),
    'recursos_por_formato' => $recursosPorFormato
];

echo "📊 Datasets:\n";
echo "   Total: " . $totalDatasets . "\n";
echo "   Privados: " . COLOR_RED . $contadorPrivados . COLOR_RESET . " (" . $resultados['estadisticas']['porcentaje_privados'] . "%)\n";
echo "   Públicos: " . COLOR_GREEN . $contadorPublicos . COLOR_RESET . " (" . round(($contadorPublicos / $totalDatasets) * 100, 1) . "%)\n\n";

echo "🏢 Organizaciones:\n";
echo "   Total: " . count($resultados['organizaciones']) . "\n";
foreach ($resultados['organizaciones'] as $org) {
    echo "   - " . $org['title'] . ": " . $org['num_datasets'] . " dataset(s)\n";
}
echo "\n";

echo "📁 Recursos:\n";
echo "   Total: " . $totalRecursos . "\n";
echo "   Promedio por dataset: " . $resultados['estadisticas']['promedio_recursos_por_dataset'] . "\n";
echo "   Por formato:\n";
arsort($recursosPorFormato);
foreach ($recursosPorFormato as $formato => $cantidad) {
    echo "   - " . str_pad($formato, 12) . ": " . $cantidad . "\n";
}
echo "\n";

// ============================================================================
// RECURSOS CON DATASTORE ACTIVO
// ============================================================================
echo COLOR_BLUE . "═══════════════════════════════════════════════════════════\n";
echo "  RECURSOS CONSULTABLES (datastore_active: true)\n";
echo "═══════════════════════════════════════════════════════════\n" . COLOR_RESET;

$recursosDatastore = [];
foreach ($resultados['datasets'] as $dataset) {
    foreach ($dataset['resources'] as $resource) {
        if ($resource['datastore_active']) {
            $recursosDatastore[] = [
                'dataset_title' => $dataset['title'],
                'dataset_id' => $dataset['id'],
                'resource_name' => $resource['name'],
                'resource_id' => $resource['id'],
                'format' => $resource['format']
            ];
        }
    }
}

echo "Total de recursos consultables vía datastore_search: " . count($recursosDatastore) . "\n\n";

if (count($recursosDatastore) > 0) {
    foreach ($recursosDatastore as $idx => $rec) {
        echo COLOR_GREEN . "[" . ($idx + 1) . "] " . COLOR_RESET . $rec['dataset_title'] . "\n";
        echo "    Recurso: " . $rec['resource_name'] . " (" . $rec['format'] . ")\n";
        echo "    ID: " . $rec['resource_id'] . "\n\n";
    }
} else {
    echo COLOR_YELLOW . "⚠ No se encontraron recursos con datastore activo" . COLOR_RESET . "\n\n";
}

$resultados['estadisticas']['recursos_datastore_activos'] = count($recursosDatastore);
$resultados['recursos_consultables'] = $recursosDatastore;

// ============================================================================
// GUARDAR RESULTADOS
// ============================================================================
$outputFile = __DIR__ . '/resultados/datasets_completos.json';
file_put_contents($outputFile, json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo COLOR_BLUE . "Resultados guardados en: " . $outputFile . COLOR_RESET . "\n";

// Archivo simplificado solo con organizaciones
$outputOrg = __DIR__ . '/resultados/organizaciones.json';
file_put_contents($outputOrg, json_encode($resultados['organizaciones'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo COLOR_BLUE . "Organizaciones guardadas en: " . $outputOrg . COLOR_RESET . "\n";

// Archivo simplificado solo con recursos consultables
$outputDatastore = __DIR__ . '/resultados/recursos_consultables.json';
file_put_contents($outputDatastore, json_encode($recursosDatastore, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo COLOR_BLUE . "Recursos consultables guardados en: " . $outputDatastore . COLOR_RESET . "\n\n";

// ============================================================================
// RESUMEN FINAL
// ============================================================================
echo COLOR_GREEN . "✓ Test completado exitosamente\n" . COLOR_RESET;
echo "\n";
echo COLOR_BLUE . "═══════════════════════════════════════════════════════════\n";
echo "  RESUMEN\n";
echo "═══════════════════════════════════════════════════════════\n" . COLOR_RESET;
echo "✓ " . $totalDatasets . " datasets documentados\n";
echo "✓ " . count($resultados['organizaciones']) . " organizaciones identificadas\n";
echo "✓ " . $totalRecursos . " recursos catalogados\n";
echo "✓ " . count($recursosDatastore) . " recursos consultables vía API\n";
echo "✓ " . count($recursosPorFormato) . " formatos diferentes encontrados\n";
echo "\n";

<?php
/**
 * TEST 01: CONECTIVIDAD BÁSICA - API CKAN
 *
 * Objetivo: Verificar que el servidor CKAN responde correctamente
 *
 * Verifica:
 * - Servidor accesible
 * - Tiempo de respuesta
 * - Códigos HTTP correctos
 * - Formato de respuesta JSON
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
define('COLOR_RESET', "\033[0m");

echo COLOR_BLUE . "╔════════════════════════════════════════════════════════════╗\n";
echo "║  TEST 01: CONECTIVIDAD BÁSICA - API CKAN                  ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n" . COLOR_RESET;
echo "\n";

// Resultados del test
$resultados = [
    'fecha_test' => date('Y-m-d H:i:s'),
    'servidor' => CKAN_BASE_URL,
    'tests' => []
];

// ============================================================================
// TEST 1.1: Ping básico al servidor
// ============================================================================
echo COLOR_YELLOW . "Test 1.1: Verificando accesibilidad del servidor..." . COLOR_RESET . "\n";

$url = CKAN_BASE_URL;
$inicio = microtime(true);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$tiempo = microtime(true) - $inicio;
curl_close($ch);

$test1 = [
    'nombre' => 'Ping al servidor',
    'url' => $url,
    'http_code' => $httpCode,
    'tiempo_respuesta_ms' => round($tiempo * 1000, 2),
    'exito' => ($httpCode >= 200 && $httpCode < 400)
];

$resultados['tests']['ping_servidor'] = $test1;

if ($test1['exito']) {
    echo COLOR_GREEN . "  ✓ Servidor accesible" . COLOR_RESET . "\n";
    echo "    HTTP Code: " . $httpCode . "\n";
    echo "    Tiempo: " . $test1['tiempo_respuesta_ms'] . " ms\n";
} else {
    echo COLOR_RED . "  ✗ Servidor NO accesible" . COLOR_RESET . "\n";
    echo "    HTTP Code: " . $httpCode . "\n";
}
echo "\n";

// ============================================================================
// TEST 1.2: Endpoint help_show (sin autenticación)
// ============================================================================
echo COLOR_YELLOW . "Test 1.2: Probando endpoint básico (help_show)..." . COLOR_RESET . "\n";

$url = CKAN_BASE_URL . '/api/3/action/help_show?name=help_show';
$inicio = microtime(true);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$tiempo = microtime(true) - $inicio;
curl_close($ch);

$jsonData = json_decode($response, true);

$test2 = [
    'nombre' => 'Endpoint help_show',
    'url' => $url,
    'http_code' => $httpCode,
    'tiempo_respuesta_ms' => round($tiempo * 1000, 2),
    'es_json_valido' => ($jsonData !== null),
    'tiene_campo_success' => isset($jsonData['success']),
    'success_value' => $jsonData['success'] ?? null,
    'exito' => ($httpCode === 200 && $jsonData !== null && $jsonData['success'] === true)
];

$resultados['tests']['help_show'] = $test2;

if ($test2['exito']) {
    echo COLOR_GREEN . "  ✓ Endpoint help_show funcional" . COLOR_RESET . "\n";
    echo "    HTTP Code: " . $httpCode . "\n";
    echo "    Tiempo: " . $test2['tiempo_respuesta_ms'] . " ms\n";
    echo "    Respuesta JSON válida: Sí\n";
    echo "    Campo 'success': " . ($jsonData['success'] ? 'true' : 'false') . "\n";
} else {
    echo COLOR_RED . "  ✗ Endpoint help_show falló" . COLOR_RESET . "\n";
    echo "    HTTP Code: " . $httpCode . "\n";
    echo "    Respuesta JSON válida: " . ($jsonData !== null ? 'Sí' : 'No') . "\n";
}
echo "\n";

// ============================================================================
// TEST 1.3: Endpoint con autenticación (package_search simple)
// ============================================================================
echo COLOR_YELLOW . "Test 1.3: Probando autenticación con API Token..." . COLOR_RESET . "\n";

$url = CKAN_BASE_URL . '/api/3/action/package_search?q=*:*&rows=1';
$inicio = microtime(true);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: ' . CKAN_API_TOKEN,
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$tiempo = microtime(true) - $inicio;
curl_close($ch);

$jsonData = json_decode($response, true);

$test3 = [
    'nombre' => 'Autenticación con API Token',
    'url' => $url,
    'http_code' => $httpCode,
    'tiempo_respuesta_ms' => round($tiempo * 1000, 2),
    'es_json_valido' => ($jsonData !== null),
    'tiene_campo_success' => isset($jsonData['success']),
    'success_value' => $jsonData['success'] ?? null,
    'num_datasets_encontrados' => $jsonData['result']['count'] ?? 0,
    'exito' => ($httpCode === 200 && $jsonData !== null && $jsonData['success'] === true)
];

$resultados['tests']['autenticacion'] = $test3;

if ($test3['exito']) {
    echo COLOR_GREEN . "  ✓ Autenticación exitosa" . COLOR_RESET . "\n";
    echo "    HTTP Code: " . $httpCode . "\n";
    echo "    Tiempo: " . $test3['tiempo_respuesta_ms'] . " ms\n";
    echo "    Datasets encontrados: " . $test3['num_datasets_encontrados'] . "\n";
} else {
    echo COLOR_RED . "  ✗ Autenticación falló" . COLOR_RESET . "\n";
    echo "    HTTP Code: " . $httpCode . "\n";
    if ($httpCode === 401 || $httpCode === 403) {
        echo COLOR_RED . "    ⚠ Token JWT inválido o expirado" . COLOR_RESET . "\n";
    }
}
echo "\n";

// ============================================================================
// TEST 1.4: Verificar acceso a datasets privados
// ============================================================================
echo COLOR_YELLOW . "Test 1.4: Verificando acceso a datasets privados..." . COLOR_RESET . "\n";

$url = CKAN_BASE_URL . '/api/3/action/package_search?q=*:*&rows=1000&include_private=true';
$inicio = microtime(true);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: ' . CKAN_API_TOKEN,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$tiempo = microtime(true) - $inicio;
curl_close($ch);

$jsonData = json_decode($response, true);

$datasetsPrivados = 0;
$datasetsPublicos = 0;

if (isset($jsonData['result']['results'])) {
    foreach ($jsonData['result']['results'] as $dataset) {
        if ($dataset['private'] === true) {
            $datasetsPrivados++;
        } else {
            $datasetsPublicos++;
        }
    }
}

$test4 = [
    'nombre' => 'Acceso a datasets privados',
    'url' => $url,
    'http_code' => $httpCode,
    'tiempo_respuesta_ms' => round($tiempo * 1000, 2),
    'total_datasets' => $jsonData['result']['count'] ?? 0,
    'datasets_privados' => $datasetsPrivados,
    'datasets_publicos' => $datasetsPublicos,
    'exito' => ($httpCode === 200 && $datasetsPrivados > 0)
];

$resultados['tests']['datasets_privados'] = $test4;

if ($test4['exito']) {
    echo COLOR_GREEN . "  ✓ Acceso a datasets privados habilitado" . COLOR_RESET . "\n";
    echo "    Total datasets: " . $test4['total_datasets'] . "\n";
    echo "    Privados: " . $datasetsPrivados . "\n";
    echo "    Públicos: " . $datasetsPublicos . "\n";
    echo "    Tiempo: " . $test4['tiempo_respuesta_ms'] . " ms\n";
} else {
    echo COLOR_RED . "  ✗ No se puede acceder a datasets privados" . COLOR_RESET . "\n";
    echo "    HTTP Code: " . $httpCode . "\n";
}
echo "\n";

// ============================================================================
// RESUMEN FINAL
// ============================================================================
echo COLOR_BLUE . "═══════════════════════════════════════════════════════════\n";
echo "  RESUMEN DE RESULTADOS\n";
echo "═══════════════════════════════════════════════════════════\n" . COLOR_RESET;

$totalTests = count($resultados['tests']);
$testsExitosos = 0;
$testsFallidos = 0;

foreach ($resultados['tests'] as $test) {
    if ($test['exito']) {
        $testsExitosos++;
    } else {
        $testsFallidos++;
    }
}

$porcentajeExito = round(($testsExitosos / $totalTests) * 100, 1);

$resultados['resumen'] = [
    'total_tests' => $totalTests,
    'exitosos' => $testsExitosos,
    'fallidos' => $testsFallidos,
    'porcentaje_exito' => $porcentajeExito
];

echo "Total de tests ejecutados: " . $totalTests . "\n";
echo COLOR_GREEN . "Tests exitosos: " . $testsExitosos . COLOR_RESET . "\n";
if ($testsFallidos > 0) {
    echo COLOR_RED . "Tests fallidos: " . $testsFallidos . COLOR_RESET . "\n";
} else {
    echo "Tests fallidos: 0\n";
}
echo "Porcentaje de éxito: " . $porcentajeExito . "%\n\n";

// Estado general
if ($porcentajeExito === 100.0) {
    echo COLOR_GREEN . "✓ CONECTIVIDAD: EXCELENTE" . COLOR_RESET . "\n";
    echo "  El servidor CKAN está completamente funcional.\n";
} elseif ($porcentajeExito >= 75.0) {
    echo COLOR_YELLOW . "⚠ CONECTIVIDAD: BUENA CON ADVERTENCIAS" . COLOR_RESET . "\n";
    echo "  El servidor responde pero algunos tests fallaron.\n";
} else {
    echo COLOR_RED . "✗ CONECTIVIDAD: PROBLEMAS DETECTADOS" . COLOR_RESET . "\n";
    echo "  Revisar errores antes de continuar.\n";
}

echo "\n";

// ============================================================================
// GUARDAR RESULTADOS
// ============================================================================
$outputFile = __DIR__ . '/resultados/conectividad.json';
file_put_contents($outputFile, json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo COLOR_BLUE . "Resultados guardados en: " . $outputFile . COLOR_RESET . "\n\n";

// ============================================================================
// RECOMENDACIONES
// ============================================================================
if ($testsFallidos > 0) {
    echo COLOR_YELLOW . "═══════════════════════════════════════════════════════════\n";
    echo "  RECOMENDACIONES\n";
    echo "═══════════════════════════════════════════════════════════\n" . COLOR_RESET;

    foreach ($resultados['tests'] as $nombre => $test) {
        if (!$test['exito']) {
            echo "• " . $test['nombre'] . ": ";

            if ($test['http_code'] === 401 || $test['http_code'] === 403) {
                echo "Verificar API Token\n";
            } elseif ($test['http_code'] === 404) {
                echo "Endpoint no encontrado\n";
            } elseif ($test['http_code'] === 0) {
                echo "Sin conexión al servidor\n";
            } else {
                echo "Error HTTP " . $test['http_code'] . "\n";
            }
        }
    }

    echo "\nContacto de soporte: support@cesefor.com\n\n";
}

echo COLOR_GREEN . "Test completado.\n" . COLOR_RESET;

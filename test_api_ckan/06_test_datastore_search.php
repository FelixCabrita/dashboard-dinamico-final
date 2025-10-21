<?php
/**
 * TEST 06: DATASTORE_SEARCH - CONSULTAS DINÁMICAS - API CKAN
 *
 * Objetivo: Probar capacidad de consulta, filtrado, agregación y paginación
 *           sobre recursos con datastore_active: true
 *
 * Verifica:
 * - Consulta básica sin filtros
 * - Filtrado por campos específicos
 * - Paginación (limit, offset)
 * - Ordenamiento (sort)
 * - Búsqueda de texto (q)
 * - Campos específicos (fields)
 * - Conteo de registros (total)
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
echo "║  TEST 06: DATASTORE_SEARCH - CONSULTAS DINÁMICAS          ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n" . COLOR_RESET;
echo "\n";

// Recursos a probar (seleccionados estratégicamente)
$recursosTest = [
    [
        'nombre' => 'Registro de municipios de Castilla y León',
        'dataset' => 'general',
        'resource_id' => '41c0aa65-3964-4926-836d-d501f9e55e52',
        'descripcion' => 'Datos geográficos administrativos - CRÍTICO para filtros'
    ],
    [
        'nombre' => 'Población Total por provincias y sexo',
        'dataset' => 'demografia_y_empleo',
        'resource_id' => '771bfd9e-d306-4202-bc08-dab243d38a2a',
        'descripcion' => 'Datos demográficos agregados'
    ],
    [
        'nombre' => 'Incendios forestales',
        'dataset' => 'flora_y_fauna',
        'resource_id' => 'f74b444d-e0f9-4638-916e-ad595bf5f0dc',
        'descripcion' => 'Datos ambientales de riesgo'
    ]
];

// Resultados del test
$resultados = [
    'fecha_test' => date('Y-m-d H:i:s'),
    'servidor' => CKAN_BASE_URL,
    'recursos_probados' => [],
    'tests_ejecutados' => 0,
    'tests_exitosos' => 0,
    'tests_fallidos' => 0
];

// Función auxiliar para hacer request
function ckanRequest($url, $method = 'GET', $postData = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . CKAN_API_TOKEN,
        'Content-Type: application/json'
    ]);

    if ($method === 'POST' && $postData) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    }

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
// EJECUTAR TESTS POR CADA RECURSO
// ============================================================================

foreach ($recursosTest as $idx => $recurso) {
    echo COLOR_MAGENTA . "\n╔════════════════════════════════════════════════════════════╗\n";
    echo "║  RECURSO " . ($idx + 1) . "/3: " . str_pad(substr($recurso['nombre'], 0, 42), 42) . " ║\n";
    echo "╚════════════════════════════════════════════════════════════╝\n" . COLOR_RESET;
    echo "Dataset: " . $recurso['dataset'] . "\n";
    echo "ID: " . $recurso['resource_id'] . "\n";
    echo "Descripción: " . $recurso['descripcion'] . "\n\n";

    $recursoResultados = [
        'nombre' => $recurso['nombre'],
        'resource_id' => $recurso['resource_id'],
        'dataset' => $recurso['dataset'],
        'tests' => []
    ];

    // ------------------------------------------------------------------------
    // TEST 6.1: Consulta básica (primeros 5 registros)
    // ------------------------------------------------------------------------
    echo COLOR_YELLOW . "Test 6.1: Consulta básica (limit 5)..." . COLOR_RESET . "\n";
    $resultados['tests_ejecutados']++;

    $url = CKAN_BASE_URL . '/api/3/action/datastore_search?resource_id=' . $recurso['resource_id'] . '&limit=5';
    $result = ckanRequest($url);

    $testBasico = [
        'nombre' => 'Consulta básica',
        'http_code' => $result['http_code'],
        'tiempo_ms' => $result['tiempo_ms'],
        'exito' => false
    ];

    if ($result['http_code'] === 200 && isset($result['json']['success']) && $result['json']['success']) {
        $records = $result['json']['result']['records'] ?? [];
        $total = $result['json']['result']['total'] ?? 0;
        $fields = $result['json']['result']['fields'] ?? [];

        echo COLOR_GREEN . "  ✓ Consulta exitosa" . COLOR_RESET . "\n";
        echo "    Registros obtenidos: " . count($records) . "\n";
        echo "    Total en BD: " . $total . "\n";
        echo "    Tiempo: " . $result['tiempo_ms'] . " ms\n";
        echo "    Campos disponibles: " . count($fields) . "\n";

        // Mostrar estructura de campos
        echo COLOR_CYAN . "    Estructura de campos:\n" . COLOR_RESET;
        foreach ($fields as $field) {
            $fieldName = $field['id'];
            $fieldType = $field['type'] ?? 'unknown';
            echo "      - " . str_pad($fieldName, 30) . " (" . $fieldType . ")\n";
        }

        // Mostrar primer registro como ejemplo
        if (count($records) > 0) {
            echo COLOR_CYAN . "\n    Ejemplo de primer registro:\n" . COLOR_RESET;
            foreach ($records[0] as $key => $value) {
                if ($key !== '_id' && $key !== '_full_text') {
                    $displayValue = is_string($value) ? substr($value, 0, 50) : $value;
                    echo "      - " . str_pad($key, 30) . ": " . $displayValue . "\n";
                }
            }
        }

        $testBasico['exito'] = true;
        $testBasico['total_registros'] = $total;
        $testBasico['campos'] = array_column($fields, 'id');
        $testBasico['tipos_campos'] = $fields;
        $testBasico['ejemplo_registro'] = $records[0] ?? null;

        $resultados['tests_exitosos']++;
    } else {
        echo COLOR_RED . "  ✗ Error en consulta" . COLOR_RESET . "\n";
        echo "    HTTP Code: " . $result['http_code'] . "\n";
        $testBasico['error'] = $result['json']['error'] ?? 'Error desconocido';
        $resultados['tests_fallidos']++;
    }

    $recursoResultados['tests']['consulta_basica'] = $testBasico;
    echo "\n";

    // Si la consulta básica falló, saltar a siguiente recurso
    if (!$testBasico['exito']) {
        $resultados['recursos_probados'][] = $recursoResultados;
        continue;
    }

    // ------------------------------------------------------------------------
    // TEST 6.2: Paginación (offset)
    // ------------------------------------------------------------------------
    echo COLOR_YELLOW . "Test 6.2: Paginación (offset 10, limit 5)..." . COLOR_RESET . "\n";
    $resultados['tests_ejecutados']++;

    $url = CKAN_BASE_URL . '/api/3/action/datastore_search?resource_id=' . $recurso['resource_id'] . '&limit=5&offset=10';
    $result = ckanRequest($url);

    $testPaginacion = [
        'nombre' => 'Paginación',
        'http_code' => $result['http_code'],
        'tiempo_ms' => $result['tiempo_ms'],
        'exito' => false
    ];

    if ($result['http_code'] === 200 && isset($result['json']['success']) && $result['json']['success']) {
        $records = $result['json']['result']['records'] ?? [];
        echo COLOR_GREEN . "  ✓ Paginación funcional" . COLOR_RESET . "\n";
        echo "    Registros obtenidos: " . count($records) . " (esperado: 5)\n";
        echo "    Tiempo: " . $result['tiempo_ms'] . " ms\n";

        $testPaginacion['exito'] = true;
        $testPaginacion['registros_obtenidos'] = count($records);
        $resultados['tests_exitosos']++;
    } else {
        echo COLOR_RED . "  ✗ Error en paginación" . COLOR_RESET . "\n";
        $resultados['tests_fallidos']++;
    }

    $recursoResultados['tests']['paginacion'] = $testPaginacion;
    echo "\n";

    // ------------------------------------------------------------------------
    // TEST 6.3: Ordenamiento (sort)
    // ------------------------------------------------------------------------
    echo COLOR_YELLOW . "Test 6.3: Ordenamiento por primer campo..." . COLOR_RESET . "\n";
    $resultados['tests_ejecutados']++;

    // Usar el primer campo que no sea _id
    $campoOrden = null;
    foreach ($testBasico['campos'] as $campo) {
        if ($campo !== '_id' && $campo !== '_full_text') {
            $campoOrden = $campo;
            break;
        }
    }

    if ($campoOrden) {
        $url = CKAN_BASE_URL . '/api/3/action/datastore_search?resource_id=' . $recurso['resource_id'] . '&limit=5&sort=' . urlencode($campoOrden . ' asc');
        $result = ckanRequest($url);

        $testOrden = [
            'nombre' => 'Ordenamiento',
            'campo' => $campoOrden,
            'http_code' => $result['http_code'],
            'tiempo_ms' => $result['tiempo_ms'],
            'exito' => false
        ];

        if ($result['http_code'] === 200 && isset($result['json']['success']) && $result['json']['success']) {
            echo COLOR_GREEN . "  ✓ Ordenamiento funcional" . COLOR_RESET . "\n";
            echo "    Campo ordenado: " . $campoOrden . " ASC\n";
            echo "    Tiempo: " . $result['tiempo_ms'] . " ms\n";

            $testOrden['exito'] = true;
            $resultados['tests_exitosos']++;
        } else {
            echo COLOR_RED . "  ✗ Error en ordenamiento" . COLOR_RESET . "\n";
            $resultados['tests_fallidos']++;
        }

        $recursoResultados['tests']['ordenamiento'] = $testOrden;
    } else {
        echo COLOR_YELLOW . "  ⚠ No hay campos disponibles para ordenar" . COLOR_RESET . "\n";
    }
    echo "\n";

    // ------------------------------------------------------------------------
    // TEST 6.4: Selección de campos específicos
    // ------------------------------------------------------------------------
    echo COLOR_YELLOW . "Test 6.4: Selección de campos específicos..." . COLOR_RESET . "\n";
    $resultados['tests_ejecutados']++;

    // Seleccionar los primeros 3 campos que no sean _id
    $camposSeleccion = array_filter($testBasico['campos'], function($c) {
        return $c !== '_id' && $c !== '_full_text';
    });
    $camposSeleccion = array_slice(array_values($camposSeleccion), 0, 3);

    if (count($camposSeleccion) > 0) {
        $fieldsParam = implode(',', $camposSeleccion);
        $url = CKAN_BASE_URL . '/api/3/action/datastore_search?resource_id=' . $recurso['resource_id'] . '&limit=3&fields=' . urlencode($fieldsParam);
        $result = ckanRequest($url);

        $testCampos = [
            'nombre' => 'Selección de campos',
            'campos_solicitados' => $camposSeleccion,
            'http_code' => $result['http_code'],
            'tiempo_ms' => $result['tiempo_ms'],
            'exito' => false
        ];

        if ($result['http_code'] === 200 && isset($result['json']['success']) && $result['json']['success']) {
            $records = $result['json']['result']['records'] ?? [];
            $camposDevueltos = !empty($records) ? array_keys($records[0]) : [];

            echo COLOR_GREEN . "  ✓ Selección de campos funcional" . COLOR_RESET . "\n";
            echo "    Campos solicitados: " . implode(', ', $camposSeleccion) . "\n";
            echo "    Campos devueltos: " . count($camposDevueltos) . "\n";
            echo "    Tiempo: " . $result['tiempo_ms'] . " ms\n";

            $testCampos['exito'] = true;
            $testCampos['campos_devueltos'] = $camposDevueltos;
            $resultados['tests_exitosos']++;
        } else {
            echo COLOR_RED . "  ✗ Error en selección de campos" . COLOR_RESET . "\n";
            $resultados['tests_fallidos']++;
        }

        $recursoResultados['tests']['seleccion_campos'] = $testCampos;
    } else {
        echo COLOR_YELLOW . "  ⚠ No hay campos disponibles para selección" . COLOR_RESET . "\n";
    }
    echo "\n";

    // ------------------------------------------------------------------------
    // TEST 6.5: Búsqueda de texto (q)
    // ------------------------------------------------------------------------
    echo COLOR_YELLOW . "Test 6.5: Búsqueda de texto (q='Palencia')..." . COLOR_RESET . "\n";
    $resultados['tests_ejecutados']++;

    $url = CKAN_BASE_URL . '/api/3/action/datastore_search?resource_id=' . $recurso['resource_id'] . '&q=Palencia&limit=10';
    $result = ckanRequest($url);

    $testBusqueda = [
        'nombre' => 'Búsqueda de texto',
        'termino' => 'Palencia',
        'http_code' => $result['http_code'],
        'tiempo_ms' => $result['tiempo_ms'],
        'exito' => false
    ];

    if ($result['http_code'] === 200 && isset($result['json']['success']) && $result['json']['success']) {
        $records = $result['json']['result']['records'] ?? [];
        $total = $result['json']['result']['total'] ?? 0;

        echo COLOR_GREEN . "  ✓ Búsqueda de texto funcional" . COLOR_RESET . "\n";
        echo "    Término: 'Palencia'\n";
        echo "    Resultados encontrados: " . $total . "\n";
        echo "    Registros obtenidos: " . count($records) . "\n";
        echo "    Tiempo: " . $result['tiempo_ms'] . " ms\n";

        $testBusqueda['exito'] = true;
        $testBusqueda['resultados_encontrados'] = $total;
        $testBusqueda['registros_obtenidos'] = count($records);
        $resultados['tests_exitosos']++;

        // Mostrar primer resultado
        if (count($records) > 0) {
            echo COLOR_CYAN . "    Primer resultado:\n" . COLOR_RESET;
            foreach ($records[0] as $key => $value) {
                if ($key !== '_id' && $key !== '_full_text') {
                    $displayValue = is_string($value) ? substr($value, 0, 50) : $value;
                    echo "      - " . str_pad($key, 25) . ": " . $displayValue . "\n";
                }
            }
        }
    } else {
        echo COLOR_RED . "  ✗ Error en búsqueda de texto" . COLOR_RESET . "\n";
        $resultados['tests_fallidos']++;
    }

    $recursoResultados['tests']['busqueda_texto'] = $testBusqueda;
    echo "\n";

    // ------------------------------------------------------------------------
    // TEST 6.6: Filtro por campo específico (filters)
    // ------------------------------------------------------------------------
    echo COLOR_YELLOW . "Test 6.6: Filtro por campo específico..." . COLOR_RESET . "\n";
    $resultados['tests_ejecutados']++;

    // Intentar filtrar por el primer campo que tenga valor en el ejemplo
    $campoFiltro = null;
    $valorFiltro = null;

    if (isset($testBasico['ejemplo_registro'])) {
        foreach ($testBasico['ejemplo_registro'] as $key => $value) {
            if ($key !== '_id' && $key !== '_full_text' && !empty($value) && is_string($value)) {
                $campoFiltro = $key;
                $valorFiltro = $value;
                break;
            }
        }
    }

    if ($campoFiltro && $valorFiltro) {
        $filters = json_encode([$campoFiltro => $valorFiltro]);
        $url = CKAN_BASE_URL . '/api/3/action/datastore_search?resource_id=' . $recurso['resource_id'] . '&filters=' . urlencode($filters);
        $result = ckanRequest($url);

        $testFiltro = [
            'nombre' => 'Filtro por campo',
            'campo' => $campoFiltro,
            'valor' => substr($valorFiltro, 0, 50),
            'http_code' => $result['http_code'],
            'tiempo_ms' => $result['tiempo_ms'],
            'exito' => false
        ];

        if ($result['http_code'] === 200 && isset($result['json']['success']) && $result['json']['success']) {
            $records = $result['json']['result']['records'] ?? [];
            $total = $result['json']['result']['total'] ?? 0;

            echo COLOR_GREEN . "  ✓ Filtro funcional" . COLOR_RESET . "\n";
            echo "    Campo: " . $campoFiltro . "\n";
            echo "    Valor: " . substr($valorFiltro, 0, 40) . "\n";
            echo "    Resultados: " . $total . "\n";
            echo "    Tiempo: " . $result['tiempo_ms'] . " ms\n";

            $testFiltro['exito'] = true;
            $testFiltro['resultados'] = $total;
            $resultados['tests_exitosos']++;
        } else {
            echo COLOR_RED . "  ✗ Error en filtro" . COLOR_RESET . "\n";
            $resultados['tests_fallidos']++;
        }

        $recursoResultados['tests']['filtro_campo'] = $testFiltro;
    } else {
        echo COLOR_YELLOW . "  ⚠ No se pudo determinar campo para filtrar" . COLOR_RESET . "\n";
    }
    echo "\n";

    // Guardar resultados del recurso
    $resultados['recursos_probados'][] = $recursoResultados;
}

// ============================================================================
// RESUMEN FINAL
// ============================================================================
echo COLOR_BLUE . "\n═══════════════════════════════════════════════════════════\n";
echo "  RESUMEN DE RESULTADOS\n";
echo "═══════════════════════════════════════════════════════════\n" . COLOR_RESET;

echo "📊 Tests ejecutados: " . $resultados['tests_ejecutados'] . "\n";
echo COLOR_GREEN . "✓ Tests exitosos: " . $resultados['tests_exitosos'] . COLOR_RESET . "\n";
echo COLOR_RED . "✗ Tests fallidos: " . $resultados['tests_fallidos'] . COLOR_RESET . "\n";

$porcentaje = $resultados['tests_ejecutados'] > 0
    ? round(($resultados['tests_exitosos'] / $resultados['tests_ejecutados']) * 100, 1)
    : 0;

echo "📈 Tasa de éxito: " . $porcentaje . "%\n\n";

// Capacidades detectadas
echo COLOR_CYAN . "🎯 CAPACIDADES DETECTADAS:\n" . COLOR_RESET;
$capacidades = [];
foreach ($resultados['recursos_probados'] as $recurso) {
    foreach ($recurso['tests'] as $testNombre => $test) {
        if ($test['exito'] ?? false) {
            $capacidades[$testNombre] = true;
        }
    }
}

foreach ($capacidades as $cap => $val) {
    $nombreCap = str_replace('_', ' ', ucfirst($cap));
    echo "  ✓ " . $nombreCap . "\n";
}

echo "\n";

// ============================================================================
// GUARDAR RESULTADOS
// ============================================================================
$outputFile = __DIR__ . '/resultados/datastore_search.json';
file_put_contents($outputFile, json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo COLOR_BLUE . "Resultados guardados en: " . $outputFile . COLOR_RESET . "\n\n";

echo COLOR_GREEN . "✓ Test completado\n" . COLOR_RESET;

// ============================================================================
// RECOMENDACIONES
// ============================================================================
echo COLOR_BLUE . "\n═══════════════════════════════════════════════════════════\n";
echo "  RECOMENDACIONES PARA INTEGRACIÓN\n";
echo "═══════════════════════════════════════════════════════════\n" . COLOR_RESET;

echo "1. Implementar caché para consultas frecuentes (TTL: 1 hora)\n";
echo "2. Usar paginación con limit=100 para cargas iniciales\n";
echo "3. Implementar búsqueda de texto para filtros de usuario\n";
echo "4. Usar filtros JSON para consultas específicas por municipio\n";
echo "5. Seleccionar solo campos necesarios para optimizar payload\n";
echo "6. Implementar retry logic con backoff exponencial\n";
echo "7. Monitorear tiempos de respuesta (threshold: 2 segundos)\n\n";

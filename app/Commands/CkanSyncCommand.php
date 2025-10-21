<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\CkanSyncManager;
use App\Libraries\ShpProcessor;

/**
 * Comando para sincronizar recursos SHP desde CKAN
 *
 * Uso:
 *   php spark ckan:sync                        - Sincronizar todos los recursos
 *   php spark ckan:sync --resource=miradores   - Sincronizar recurso específico
 *   php spark ckan:sync --force                - Forzar sincronización completa
 *   php spark ckan:sync --check                - Verificar ogr2ogr y config
 *
 * @package App\Commands
 * @version 1.0.0
 * @date 2025-10-21
 */
class CkanSyncCommand extends BaseCommand
{
    /**
     * Grupo de comandos (para php spark list)
     */
    protected $group       = 'CKAN';

    /**
     * Nombre del comando
     */
    protected $name        = 'ckan:sync';

    /**
     * Descripción del comando
     */
    protected $description = 'Sincroniza recursos SHP desde CKAN a SQLite/GeoJSON local';

    /**
     * Uso del comando
     */
    protected $usage       = 'ckan:sync [options]';

    /**
     * Argumentos del comando
     */
    protected $arguments   = [];

    /**
     * Opciones del comando
     */
    protected $options     = [
        '--resource' => 'Sincronizar solo un recurso específico (ej: miradores)',
        '--force'    => 'Forzar sincronización aunque esté actualizado',
        '--check'    => 'Verificar configuración y dependencias sin sincronizar',
    ];

    /**
     * Ejecutar comando
     *
     * @param array $params Parámetros del comando
     * @return void
     */
    public function run(array $params)
    {
        CLI::write('════════════════════════════════════════════════', 'blue');
        CLI::write('   CKAN Sync Manager - Bosque Modelo Palencia  ', 'blue');
        CLI::write('════════════════════════════════════════════════', 'blue');
        CLI::newLine();

        // Verificar configuración
        if (array_key_exists('check', $params)) {
            $this->checkConfiguration();
            return;
        }

        $syncManager = new CkanSyncManager();
        $resource = $params['resource'] ?? null;
        $force = array_key_exists('force', $params);

        if ($force) {
            CLI::write('⚠️  Modo FORCE activado - Forzando resincronización', 'yellow');
            CLI::newLine();
        }

        // Sincronizar recurso específico
        if ($resource) {
            CLI::write("📦 Sincronizando recurso: {$resource}", 'yellow');
            CLI::newLine();

            $config = config('CkanSync');
            $resourceId = $config->priorityResources[$resource] ?? null;

            if (!$resourceId) {
                CLI::error("❌ Recurso '{$resource}' no encontrado en configuración");
                CLI::newLine();
                CLI::write('Recursos disponibles:', 'yellow');

                foreach ($config->priorityResources as $name => $id) {
                    CLI::write("  - {$name}", 'white');
                }

                return;
            }

            try {
                $result = $syncManager->syncResource($resource, $resourceId, $force);

                if ($result) {
                    CLI::write("✅ Recurso '{$resource}' sincronizado exitosamente", 'green');
                } else {
                    CLI::write("ℹ️  Recurso '{$resource}' ya estaba actualizado", 'yellow');
                }

            } catch (\Exception $e) {
                CLI::error("❌ Error: " . $e->getMessage());
            }

        } else {
            // Sincronizar todos los recursos
            CLI::write('🔄 Sincronizando todos los recursos prioritarios...', 'yellow');
            CLI::newLine();

            $stats = $syncManager->syncAll();

            CLI::newLine();
            CLI::write('════════════════════════════════════════════════', 'blue');
            CLI::write('   RESUMEN DE SINCRONIZACIÓN                  ', 'blue');
            CLI::write('════════════════════════════════════════════════', 'blue');
            CLI::newLine();

            CLI::write("📊 Procesados:   {$stats['processed']}", 'white');
            CLI::write("✅ Actualizados: {$stats['updated']}", 'green');
            CLI::write("⏭️  Omitidos:     {$stats['skipped']}", 'yellow');
            CLI::write("❌ Errores:      {$stats['errors']}", $stats['errors'] > 0 ? 'red' : 'white');
            CLI::write("⏱️  Duración:     " . round($stats['duration'], 2) . "s", 'white');

            CLI::newLine();

            if ($stats['errors'] > 0) {
                CLI::write('⚠️  Se encontraron errores. Revisar logs en:', 'yellow');
                CLI::write('   ' . config('CkanSync')->logDir, 'white');
            } else {
                CLI::write('🎉 Sincronización completada sin errores', 'green');
            }
        }

        CLI::newLine();
    }

    /**
     * Verificar configuración y dependencias
     *
     * @return void
     */
    private function checkConfiguration(): void
    {
        CLI::write('🔍 Verificando configuración y dependencias...', 'yellow');
        CLI::newLine();

        $allOk = true;

        // 1. Verificar ogr2ogr
        CLI::write('1. Verificando ogr2ogr (GDAL)...', 'white');

        $shpProcessor = new ShpProcessor();
        $ogrCheck = $shpProcessor->checkOgr2ogr();

        if ($ogrCheck['available']) {
            CLI::write("   ✅ ogr2ogr encontrado: {$ogrCheck['version']}", 'green');
            CLI::write("   📍 Ruta: {$ogrCheck['path']}", 'white');
        } else {
            CLI::write("   ❌ ogr2ogr NO encontrado", 'red');
            CLI::write("   ℹ️  Instalar GDAL/OGR para procesar archivos SHP", 'yellow');
            $allOk = false;
        }

        CLI::newLine();

        // 2. Verificar directorios
        CLI::write('2. Verificando estructura de directorios...', 'white');

        $config = config('CkanSync');
        $dirs = [
            'Base' => $config->baseDir,
            'SQLite' => $config->sqliteDir,
            'GeoJSON' => $config->geojsonDir,
            'Temp' => $config->tempDir,
            'Logs' => $config->logDir,
        ];

        foreach ($dirs as $name => $path) {
            if (is_dir($path) && is_writable($path)) {
                CLI::write("   ✅ {$name}: {$path}", 'green');
            } else {
                CLI::write("   ❌ {$name}: {$path} (no existe o no es escribible)", 'red');
                $allOk = false;
            }
        }

        CLI::newLine();

        // 3. Verificar extensiones PHP
        CLI::write('3. Verificando extensiones PHP...', 'white');

        $extensions = ['zip', 'sqlite3', 'curl'];

        foreach ($extensions as $ext) {
            if (extension_loaded($ext)) {
                CLI::write("   ✅ {$ext}", 'green');
            } else {
                CLI::write("   ❌ {$ext} (NO cargada)", 'red');
                $allOk = false;
            }
        }

        CLI::newLine();

        // 4. Verificar conectividad CKAN
        CLI::write('4. Verificando conectividad con CKAN...', 'white');

        try {
            $ckan = new \App\Libraries\CkanApiClient();
            $result = $ckan->listDatasets(false);

            if ($result) {
                CLI::write("   ✅ Conectado exitosamente", 'green');
                CLI::write("   📊 Datasets encontrados: {$result['count']}", 'white');
            } else {
                CLI::write("   ❌ Error al conectar con CKAN API", 'red');
                $allOk = false;
            }

        } catch (\Exception $e) {
            CLI::write("   ❌ Excepción: " . $e->getMessage(), 'red');
            $allOk = false;
        }

        CLI::newLine();

        // 5. Verificar tabla de BD
        CLI::write('5. Verificando tabla de base de datos...', 'white');

        try {
            $db = \Config\Database::connect();

            if ($db->tableExists('ckan_resource_sync')) {
                $count = $db->table('ckan_resource_sync')->countAllResults();
                CLI::write("   ✅ Tabla 'ckan_resource_sync' existe", 'green');
                CLI::write("   📊 Recursos sincronizados: {$count}", 'white');
            } else {
                CLI::write("   ❌ Tabla 'ckan_resource_sync' NO existe", 'red');
                CLI::write("   ℹ️  Ejecutar: php spark migrate", 'yellow');
                $allOk = false;
            }

        } catch (\Exception $e) {
            CLI::write("   ❌ Error: " . $e->getMessage(), 'red');
            $allOk = false;
        }

        CLI::newLine();

        // 6. Recursos prioritarios configurados
        CLI::write('6. Recursos prioritarios configurados:', 'white');

        foreach ($config->priorityResources as $name => $id) {
            CLI::write("   • {$name}: {$id}", 'white');
        }

        CLI::newLine();
        CLI::write('════════════════════════════════════════════════', 'blue');

        if ($allOk) {
            CLI::write('✅ Todas las verificaciones pasaron exitosamente', 'green');
            CLI::write('   El sistema está listo para sincronizar.', 'green');
        } else {
            CLI::write('❌ Algunas verificaciones fallaron', 'red');
            CLI::write('   Resolver los problemas antes de sincronizar.', 'yellow');
        }

        CLI::write('════════════════════════════════════════════════', 'blue');
        CLI::newLine();
    }
}

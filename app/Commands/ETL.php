<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\ETL\CSVProcessor;
use App\Libraries\ETL\DataNormalizer;
use App\Libraries\ETL\SQLiteBuilder;

/**
 * Comando ETL para procesar archivos CSV y generar base de datos SQLite
 *
 * Uso:
 *   php spark etl:run
 *   php spark etl:run --clean
 *   php spark etl:run --verbose
 */
class ETL extends BaseCommand
{
    /**
     * Grupo del comando
     *
     * @var string
     */
    protected $group = 'ETL';

    /**
     * Nombre del comando
     *
     * @var string
     */
    protected $name = 'etl:run';

    /**
     * Descripción del comando
     *
     * @var string
     */
    protected $description = 'Ejecuta el proceso ETL para cargar datos CSV en SQLite';

    /**
     * Uso del comando
     *
     * @var string
     */
    protected $usage = 'etl:run [options]';

    /**
     * Argumentos del comando
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * Opciones del comando
     *
     * @var array
     */
    protected $options = [
        '--clean'   => 'Eliminar base de datos existente antes de ejecutar',
        '--verbose' => 'Mostrar logs detallados del proceso',
    ];

    /**
     * Archivos CSV a procesar
     *
     * @var array
     */
    protected $csvFiles = [
        'Existencias_Muni_BMP_4.csv',
        'BMP_ExsMuniIFN2.csv',
        'BMP_ExsMuniIFN3.csv',
        'MFE25_FormArb.csv',
        'MFE25_FormHerb.csv',
        'MFE25_FormMato.csv',
        'MFE25_LULUCF.csv',
        'MFE25_ModCom.csv',
        'MFE50_FormacionesArboladas_Mun.csv',
        'Prop_BMP_Mun.csv',
        'ValorMUNI_BMP.csv',
        'CalidadesMUNI.csv',
    ];

    /**
     * Logger para el proceso ETL
     *
     * @var resource
     */
    protected $logFile;

    /**
     * Modo verbose
     *
     * @var bool
     */
    protected $verbose = false;

    /**
     * Contador de registros procesados
     *
     * @var int
     */
    protected $totalRecords = 0;

    /**
     * Ejecutar el comando
     *
     * @param array $params
     * @return int
     */
    public function run(array $params)
    {
        $this->verbose = array_key_exists('verbose', $params);
        $clean = array_key_exists('clean', $params);

        // Iniciar logging
        $this->initializeLog();

        try {
            CLI::write('═══════════════════════════════════════════════════', 'green');
            CLI::write('  ETL - BOSQUE MODELO PALENCIA', 'green');
            CLI::write('═══════════════════════════════════════════════════', 'green');
            CLI::newLine();

            $startTime = microtime(true);

            // Fase 1: Validación de archivos
            $this->log('Fase 1: Validación de archivos CSV', 'INFO');
            $this->validateCSVFiles();

            // Fase 2: Inicializar base de datos
            $this->log('Fase 2: Inicialización de base de datos', 'INFO');
            $sqliteBuilder = new SQLiteBuilder($clean);
            $dbPath = $sqliteBuilder->getDbPath();
            CLI::write("Base de datos: {$dbPath}", 'yellow');

            // Fase 3: Crear esquema
            $this->log('Fase 3: Creación de esquema dimensional', 'INFO');
            $sqliteBuilder->createSchema();
            CLI::write('✓ Esquema creado exitosamente', 'green');

            // Fase 4: Extract & Transform
            $this->log('Fase 4: Extracción y transformación de datos', 'INFO');
            $csvProcessor = new CSVProcessor();
            $normalizer = new DataNormalizer();

            $processedData = [];
            $totalFiles = count($this->csvFiles);
            $currentFile = 0;

            foreach ($this->csvFiles as $csvFile) {
                $currentFile++;
                CLI::newLine();
                CLI::write("[{$currentFile}/{$totalFiles}] Procesando: {$csvFile}", 'cyan');

                $inputPath = ROOTPATH . 'data/input/' . $csvFile;

                // Leer CSV
                $data = $csvProcessor->readCSV($inputPath);
                $this->log("  - Leídos " . count($data) . " registros de {$csvFile}", 'INFO');

                // Normalizar datos
                $normalized = $normalizer->normalize($csvFile, $data);
                $this->log("  - Normalizados " . count($normalized) . " registros", 'INFO');

                $processedData[$csvFile] = $normalized;
                $this->totalRecords += count($normalized);

                CLI::write("  ✓ {$csvFile}: " . count($normalized) . " registros", 'green');
            }

            CLI::newLine();
            CLI::write("Total de registros procesados: {$this->totalRecords}", 'yellow');

            // Fase 5: Load
            $this->log('Fase 5: Carga de datos en SQLite', 'INFO');
            CLI::write('Insertando datos en la base de datos...', 'cyan');

            $sqliteBuilder->loadData($processedData);
            CLI::write('✓ Datos insertados exitosamente', 'green');

            // Fase 6: Crear índices y vistas
            $this->log('Fase 6: Creación de índices y vistas', 'INFO');
            $sqliteBuilder->createIndexes();
            $sqliteBuilder->createViews();
            CLI::write('✓ Índices y vistas creados', 'green');

            // Fase 7: Optimizar base de datos
            $this->log('Fase 7: Optimización de base de datos', 'INFO');
            $sqliteBuilder->optimize();
            CLI::write('✓ Base de datos optimizada', 'green');

            // Fase 8: Generar reporte final
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            CLI::newLine();
            CLI::write('═══════════════════════════════════════════════════', 'green');
            CLI::write('  PROCESO ETL COMPLETADO', 'green');
            CLI::write('═══════════════════════════════════════════════════', 'green');
            CLI::newLine();

            $stats = $sqliteBuilder->getStatistics();
            CLI::write("Registros totales: " . number_format($this->totalRecords), 'yellow');
            CLI::write("Tamaño de base de datos: " . $this->formatBytes($stats['size']), 'yellow');
            CLI::write("Tablas creadas: {$stats['tables']}", 'yellow');
            CLI::write("Índices creados: {$stats['indexes']}", 'yellow');
            CLI::write("Duración: {$duration} segundos", 'yellow');
            CLI::newLine();

            $this->log("ETL completado exitosamente en {$duration}s", 'SUCCESS');
            $this->log("Total de registros: {$this->totalRecords}", 'SUCCESS');

            return EXIT_SUCCESS;

        } catch (\Exception $e) {
            CLI::newLine();
            CLI::error('ERROR: ' . $e->getMessage());
            CLI::write($e->getTraceAsString(), 'red');

            $this->log('ERROR: ' . $e->getMessage(), 'ERROR');
            $this->log($e->getTraceAsString(), 'ERROR');

            return EXIT_ERROR;

        } finally {
            $this->closeLog();
        }
    }

    /**
     * Validar que existan todos los archivos CSV
     *
     * @return void
     * @throws \RuntimeException
     */
    protected function validateCSVFiles()
    {
        $inputDir = ROOTPATH . 'data/input/';
        $missing = [];

        CLI::write('Validando archivos CSV...', 'cyan');

        foreach ($this->csvFiles as $file) {
            $path = $inputDir . $file;
            if (!file_exists($path)) {
                $missing[] = $file;
                CLI::write("  ✗ {$file} - NO ENCONTRADO", 'red');
            } else {
                $size = filesize($path);
                if ($this->verbose) {
                    CLI::write("  ✓ {$file} - " . $this->formatBytes($size), 'green');
                }
            }
        }

        if (!empty($missing)) {
            throw new \RuntimeException(
                'Faltan archivos CSV: ' . implode(', ', $missing)
            );
        }

        CLI::write('✓ Todos los archivos CSV están disponibles', 'green');
        CLI::newLine();
    }

    /**
     * Inicializar archivo de log
     *
     * @return void
     */
    protected function initializeLog()
    {
        $logDir = WRITEPATH . 'logs/';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logFileName = 'etl_' . date('Y md_His') . '.log';
        $logPath = $logDir . $logFileName;

        $this->logFile = fopen($logPath, 'w');

        if ($this->logFile === false) {
            CLI::error("No se pudo crear el archivo de log: {$logPath}");
        } else {
            $this->log('Inicio del proceso ETL', 'INFO');
            $this->log('Fecha/Hora: ' . date('Y-m-d H:i:s'), 'INFO');
            CLI::write("Log: {$logPath}", 'yellow');
        }
    }

    /**
     * Escribir en el archivo de log
     *
     * @param string $message
     * @param string $level
     * @return void
     */
    protected function log(string $message, string $level = 'INFO')
    {
        if ($this->logFile) {
            $timestamp = date('Y-m-d H:i:s');
            $logLine = "[{$timestamp}] [{$level}] {$message}\n";
            fwrite($this->logFile, $logLine);

            if ($this->verbose) {
                CLI::write("[{$level}] {$message}", 'light_gray');
            }
        }
    }

    /**
     * Cerrar archivo de log
     *
     * @return void
     */
    protected function closeLog()
    {
        if ($this->logFile) {
            $this->log('Fin del proceso ETL', 'INFO');
            fclose($this->logFile);
        }
    }

    /**
     * Formatear bytes a formato legible
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

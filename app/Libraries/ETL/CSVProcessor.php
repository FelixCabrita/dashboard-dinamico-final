<?php

namespace App\Libraries\ETL;

/**
 * CSVProcessor
 * Librería para leer y procesar archivos CSV
 */
class CSVProcessor
{
    /**
     * Tamaño de chunk para procesamiento por lotes
     *
     * @var int
     */
    protected $chunkSize = 1000;

    /**
     * Encoding por defecto
     *
     * @var string
     */
    protected $encoding = 'UTF-8';

    /**
     * Delimitador CSV
     *
     * @var string
     */
    protected $delimiter = ',';

    /**
     * Leer archivo CSV completo
     *
     * @param string $filePath
     * @return array
     * @throws \RuntimeException
     */
    public function readCSV(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Archivo no encontrado: {$filePath}");
        }

        // Detectar encoding
        $this->detectEncoding($filePath);

        $data = [];
        $headers = [];
        $rowNumber = 0;

        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, $this->delimiter)) !== false) {
                $rowNumber++;

                // Primera fila = headers
                if ($rowNumber === 1) {
                    $headers = $this->cleanHeaders($row);
                    continue;
                }

                // Convertir encoding si es necesario
                if ($this->encoding !== 'UTF-8') {
                    $row = array_map(function($value) {
                        return mb_convert_encoding($value, 'UTF-8', $this->encoding);
                    }, $row);
                }

                // Combinar headers con valores
                if (count($row) === count($headers)) {
                    $data[] = array_combine($headers, $row);
                } else {
                    // Registrar warning pero continuar
                    log_message('warning', "Fila {$rowNumber} tiene " . count($row) . " columnas, se esperaban " . count($headers));
                }
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * Leer CSV por chunks (para archivos grandes)
     *
     * @param string $filePath
     * @param callable $callback
     * @return int Total de registros procesados
     * @throws \RuntimeException
     */
    public function readCSVInChunks(string $filePath, callable $callback): int
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Archivo no encontrado: {$filePath}");
        }

        $this->detectEncoding($filePath);

        $totalProcessed = 0;
        $headers = [];
        $chunk = [];
        $rowNumber = 0;

        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, $this->delimiter)) !== false) {
                $rowNumber++;

                // Primera fila = headers
                if ($rowNumber === 1) {
                    $headers = $this->cleanHeaders($row);
                    continue;
                }

                // Convertir encoding si es necesario
                if ($this->encoding !== 'UTF-8') {
                    $row = array_map(function($value) {
                        return mb_convert_encoding($value, 'UTF-8', $this->encoding);
                    }, $row);
                }

                // Agregar a chunk
                if (count($row) === count($headers)) {
                    $chunk[] = array_combine($headers, $row);
                }

                // Procesar chunk cuando llegue al tamaño límite
                if (count($chunk) >= $this->chunkSize) {
                    $callback($chunk);
                    $totalProcessed += count($chunk);
                    $chunk = [];
                }
            }

            // Procesar último chunk
            if (!empty($chunk)) {
                $callback($chunk);
                $totalProcessed += count($chunk);
            }

            fclose($handle);
        }

        return $totalProcessed;
    }

    /**
     * Detectar encoding del archivo
     *
     * @param string $filePath
     * @return void
     */
    protected function detectEncoding(string $filePath)
    {
        // Leer primeras líneas para detectar encoding
        $handle = fopen($filePath, 'r');
        $sample = fread($handle, 8192); // Leer primeros 8KB
        fclose($handle);

        // Intentar detectar encoding
        $detected = mb_detect_encoding($sample, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);

        if ($detected) {
            $this->encoding = $detected;
        } else {
            // Por defecto asumir UTF-8
            $this->encoding = 'UTF-8';
        }
    }

    /**
     * Limpiar nombres de headers
     *
     * @param array $headers
     * @return array
     */
    protected function cleanHeaders(array $headers): array
    {
        return array_map(function($header) {
            // Eliminar BOM si existe
            $header = str_replace("\xEF\xBB\xBF", '', $header);

            // Eliminar comillas
            $header = trim($header, '"\'');

            // Eliminar espacios en blanco
            $header = trim($header);

            return $header;
        }, $headers);
    }

    /**
     * Validar estructura del CSV (verificar columnas esperadas)
     *
     * @param string $filePath
     * @param array $expectedColumns
     * @return bool
     * @throws \RuntimeException
     */
    public function validateStructure(string $filePath, array $expectedColumns): bool
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Archivo no encontrado: {$filePath}");
        }

        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle, 0, $this->delimiter);
            fclose($handle);

            $headers = $this->cleanHeaders($headers);

            // Verificar que todas las columnas esperadas existan
            $missing = array_diff($expectedColumns, $headers);

            if (!empty($missing)) {
                throw new \RuntimeException(
                    "Columnas faltantes en {$filePath}: " . implode(', ', $missing)
                );
            }

            return true;
        }

        return false;
    }

    /**
     * Obtener headers del CSV
     *
     * @param string $filePath
     * @return array
     */
    public function getHeaders(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException("Archivo no encontrado: {$filePath}");
        }

        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle, 0, $this->delimiter);
            fclose($handle);

            return $this->cleanHeaders($headers);
        }

        return [];
    }

    /**
     * Contar registros en el CSV (sin incluir header)
     *
     * @param string $filePath
     * @return int
     */
    public function countRecords(string $filePath): int
    {
        if (!file_exists($filePath)) {
            return 0;
        }

        $count = 0;

        if (($handle = fopen($filePath, 'r')) !== false) {
            // Saltar header
            fgetcsv($handle);

            while (fgetcsv($handle, 0, $this->delimiter) !== false) {
                $count++;
            }

            fclose($handle);
        }

        return $count;
    }

    /**
     * Configurar tamaño de chunk
     *
     * @param int $size
     * @return self
     */
    public function setChunkSize(int $size): self
    {
        $this->chunkSize = $size;
        return $this;
    }

    /**
     * Configurar delimitador
     *
     * @param string $delimiter
     * @return self
     */
    public function setDelimiter(string $delimiter): self
    {
        $this->delimiter = $delimiter;
        return $this;
    }
}

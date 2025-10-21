<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

/**
 * DataManager Controller
 * Gestión de datos ETL desde el panel administrativo
 */
class DataManager extends BaseController
{
    /**
     * Directorio de bases de datos
     *
     * @var string
     */
    protected $dbDir;

    /**
     * Directorio de logs
     *
     * @var string
     */
    protected $logDir;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dbDir = ROOTPATH . 'data/db/';
        $this->logDir = WRITEPATH . 'logs/';
    }

    /**
     * Vista principal - Dashboard de gestión de datos
     *
     * @return string
     */
    public function index()
    {
        // Verificar autenticación
        if (!session()->has('user_id')) {
            return redirect()->to(admin_url('login'));
        }

        $data = [
            'title' => 'Gestión de Datos',
            'currentDb' => $this->getCurrentDatabaseInfo(),
            'versions' => $this->getDatabaseVersions(),
            'lastLog' => $this->getLastLogInfo(),
            'statistics' => $this->getDatabaseStatistics(),
        ];

        return view('admin/data/index', $data);
    }

    /**
     * Ejecutar proceso ETL
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function runETL()
    {
        // Verificar autenticación
        if (!session()->has('user_id')) {
            return redirect()->to(admin_url('login'));
        }

        try {
            // Ejecutar comando ETL en background
            $command = 'php ' . ROOTPATH . 'spark etl:run';

            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                // Windows: ejecutar en background
                pclose(popen('start /B ' . $command, 'r'));
            } else {
                // Linux/Mac: ejecutar en background
                exec($command . ' > /dev/null 2>&1 &');
            }

            return redirect()
                ->to(admin_url('data-manager'))
                ->with('success', 'Proceso ETL iniciado correctamente. Revisa los logs para ver el progreso.');

        } catch (\Exception $e) {
            return redirect()
                ->to(admin_url('data-manager'))
                ->with('error', 'Error al iniciar ETL: ' . $e->getMessage());
        }
    }

    /**
     * Ver log de ETL
     *
     * @param string|null $logFile
     * @return string
     */
    public function viewLog(string $logFile = null)
    {
        // Verificar autenticación
        if (!session()->has('user_id')) {
            return redirect()->to(admin_url('login'));
        }

        // Si no se especifica log, obtener el más reciente
        if ($logFile === null) {
            $logs = $this->getLogFiles();
            $logFile = !empty($logs) ? $logs[0]['name'] : null;
        }

        $logContent = '';
        $logPath = '';

        if ($logFile) {
            $logPath = $this->logDir . $logFile;

            if (file_exists($logPath)) {
                $logContent = file_get_contents($logPath);
            }
        }

        $data = [
            'title' => 'Log de ETL',
            'logFile' => $logFile,
            'logContent' => $logContent,
            'logFiles' => $this->getLogFiles(),
        ];

        return view('admin/data/logs', $data);
    }

    /**
     * Descargar log
     *
     * @param string $logFile
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function downloadLog(string $logFile)
    {
        // Verificar autenticación
        if (!session()->has('user_id')) {
            return redirect()->to(admin_url('login'));
        }

        $logPath = $this->logDir . $logFile;

        if (!file_exists($logPath)) {
            return redirect()
                ->to(admin_url('data-manager/logs'))
                ->with('error', 'Log no encontrado');
        }

        return $this->response->download($logPath, null);
    }

    /**
     * Descargar versión de base de datos
     *
     * @param string $dbFile
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function downloadDatabase(string $dbFile)
    {
        // Verificar autenticación
        if (!session()->has('user_id')) {
            return redirect()->to(admin_url('login'));
        }

        $dbPath = $this->dbDir . $dbFile;

        if (!file_exists($dbPath)) {
            return redirect()
                ->to(admin_url('data-manager'))
                ->with('error', 'Base de datos no encontrada');
        }

        return $this->response->download($dbPath, null);
    }

    /**
     * Restaurar versión anterior de base de datos
     *
     * @param string $dbFile
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function restoreVersion(string $dbFile)
    {
        // Verificar autenticación
        if (!session()->has('user_id')) {
            return redirect()->to(admin_url('login'));
        }

        try {
            $dbPath = $this->dbDir . $dbFile;

            if (!file_exists($dbPath)) {
                throw new \RuntimeException('Base de datos no encontrada');
            }

            // Actualizar current.db
            $currentPath = $this->dbDir . 'current.db';

            if (file_exists($currentPath)) {
                unlink($currentPath);
            }

            copy($dbPath, $currentPath);

            return redirect()
                ->to(admin_url('data-manager'))
                ->with('success', "Versión {$dbFile} restaurada correctamente");

        } catch (\Exception $e) {
            return redirect()
                ->to(admin_url('data-manager'))
                ->with('error', 'Error al restaurar versión: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar versión antigua de base de datos
     *
     * @param string $dbFile
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function deleteVersion(string $dbFile)
    {
        // Verificar autenticación
        if (!session()->has('user_id')) {
            return redirect()->to(admin_url('login'));
        }

        try {
            // No permitir eliminar current.db
            if ($dbFile === 'current.db') {
                throw new \RuntimeException('No se puede eliminar la versión actual');
            }

            $dbPath = $this->dbDir . $dbFile;

            if (!file_exists($dbPath)) {
                throw new \RuntimeException('Base de datos no encontrada');
            }

            unlink($dbPath);

            return redirect()
                ->to(admin_url('data-manager'))
                ->with('success', "Versión {$dbFile} eliminada correctamente");

        } catch (\Exception $e) {
            return redirect()
                ->to(admin_url('data-manager'))
                ->with('error', 'Error al eliminar versión: ' . $e->getMessage());
        }
    }

    /**
     * Obtener información de la base de datos actual
     *
     * @return array|null
     */
    protected function getCurrentDatabaseInfo(): ?array
    {
        $currentPath = $this->dbDir . 'current.db';

        if (!file_exists($currentPath)) {
            return null;
        }

        return [
            'name' => 'current.db',
            'path' => $currentPath,
            'size' => filesize($currentPath),
            'modified' => filemtime($currentPath),
            'exists' => true,
        ];
    }

    /**
     * Obtener lista de versiones de base de datos
     *
     * @return array
     */
    protected function getDatabaseVersions(): array
    {
        if (!is_dir($this->dbDir)) {
            return [];
        }

        $files = scandir($this->dbDir, SCANDIR_SORT_DESCENDING);
        $versions = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.gitkeep') {
                continue;
            }

            if (preg_match('/^version_\d{8}_\d{6}\.db$/', $file)) {
                $filePath = $this->dbDir . $file;

                $versions[] = [
                    'name' => $file,
                    'size' => filesize($filePath),
                    'created' => filemtime($filePath),
                    'is_current' => false,
                ];
            }
        }

        return $versions;
    }

    /**
     * Obtener información del último log
     *
     * @return array|null
     */
    protected function getLastLogInfo(): ?array
    {
        $logs = $this->getLogFiles();

        if (empty($logs)) {
            return null;
        }

        return $logs[0];
    }

    /**
     * Obtener lista de archivos de log
     *
     * @return array
     */
    protected function getLogFiles(): array
    {
        if (!is_dir($this->logDir)) {
            return [];
        }

        $files = scandir($this->logDir, SCANDIR_SORT_DESCENDING);
        $logs = [];

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.gitkeep') {
                continue;
            }

            if (preg_match('/^etl_\d{4}-\d{2}-\d{2}_\d{6}\.log$/', $file)) {
                $filePath = $this->logDir . $file;

                // Analizar log para obtener estado
                $content = file_get_contents($filePath);
                $status = 'unknown';

                if (strpos($content, '[SUCCESS]') !== false) {
                    $status = 'success';
                } elseif (strpos($content, '[ERROR]') !== false) {
                    $status = 'error';
                } elseif (strpos($content, 'Inicio del proceso ETL') !== false) {
                    $status = 'running';
                }

                $logs[] = [
                    'name' => $file,
                    'size' => filesize($filePath),
                    'created' => filemtime($filePath),
                    'status' => $status,
                ];
            }
        }

        return $logs;
    }

    /**
     * Obtener estadísticas de la base de datos
     *
     * @return array
     */
    protected function getDatabaseStatistics(): array
    {
        $currentPath = $this->dbDir . 'current.db';

        if (!file_exists($currentPath)) {
            return [
                'total_records' => 0,
                'tables' => [],
                'size' => 0,
            ];
        }

        try {
            $pdo = new \PDO('sqlite:' . $currentPath);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Obtener tablas
            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'fact_%'");
            $tableStats = [];
            $totalRecords = 0;

            foreach ($tables as $table) {
                $tableName = $table['name'];
                $result = $pdo->query("SELECT COUNT(*) FROM {$tableName}");
                $count = (int) $result->fetchColumn();

                $tableStats[] = [
                    'name' => $tableName,
                    'records' => $count,
                ];

                $totalRecords += $count;
            }

            return [
                'total_records' => $totalRecords,
                'tables' => $tableStats,
                'size' => filesize($currentPath),
            ];

        } catch (\Exception $e) {
            log_message('error', 'Error al obtener estadísticas de BD: ' . $e->getMessage());

            return [
                'total_records' => 0,
                'tables' => [],
                'size' => 0,
                'error' => $e->getMessage(),
            ];
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

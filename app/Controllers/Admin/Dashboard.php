<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\ComponentModel;

/**
 * Admin Dashboard Controller
 * Panel administrativo principal
 */
class Dashboard extends BaseController
{
    /**
     * Dashboard principal del panel admin
     */
    public function index()
    {
        $userModel = new UserModel();
        $userStats = $userModel->getStatistics();

        // Obtener estadísticas de componentes
        $componentModel = new ComponentModel();
        $componentStats = $componentModel->getStatistics();

        // Obtener estadísticas de la base de datos SQLite
        $dbStats = $this->getDatabaseStats();

        $data = [
            'title' => 'Panel Administrativo - Bosque Modelo Palencia',
            'user_name' => session()->get('user_name'),
            'user_email' => session()->get('user_email'),
            'stats' => [
                'total_users' => $userStats['total'],
                'users_today' => $userStats['created_today'],
                'users_this_week' => $userStats['created_this_week'],
                'last_user' => $userStats['last_user'],
                'total_components' => $componentStats['total'],
                'components_by_type' => $componentStats['by_type'],
                'total_dashboards' => 0, // Placeholder para Hito 6
                'db_exists' => $dbStats['exists'],
                'db_records' => $dbStats['records'],
                'db_size' => $dbStats['size'],
                'db_last_update' => $dbStats['last_update'],
            ]
        ];

        return view('admin/dashboard', $data);
    }

    /**
     * Obtener estadísticas de la base de datos SQLite
     *
     * @return array
     */
    protected function getDatabaseStats(): array
    {
        $dbPath = ROOTPATH . 'data/db/current.db';

        if (!file_exists($dbPath)) {
            return [
                'exists' => false,
                'records' => 0,
                'size' => 0,
                'last_update' => 'N/A',
            ];
        }

        try {
            $pdo = new \PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Contar registros en tablas de hechos
            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'fact_%'");
            $totalRecords = 0;

            foreach ($tables as $table) {
                $tableName = $table['name'];
                $result = $pdo->query("SELECT COUNT(*) FROM {$tableName}");
                $totalRecords += (int) $result->fetchColumn();
            }

            return [
                'exists' => true,
                'records' => $totalRecords,
                'size' => filesize($dbPath),
                'last_update' => date('d/m/Y H:i', filemtime($dbPath)),
            ];

        } catch (\Exception $e) {
            log_message('error', 'Error al obtener estadísticas de BD: ' . $e->getMessage());

            return [
                'exists' => false,
                'records' => 0,
                'size' => 0,
                'last_update' => 'Error',
            ];
        }
    }
}

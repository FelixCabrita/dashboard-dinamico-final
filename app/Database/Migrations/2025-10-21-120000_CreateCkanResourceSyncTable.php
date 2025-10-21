<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migración: Crear tabla de sincronización de recursos CKAN
 *
 * Esta tabla almacena el estado de sincronización de cada recurso SHP
 * desde CKAN, permitiendo tracking de última actualización y detección
 * de cambios.
 *
 * @package App\Database\Migrations
 * @version 1.0.0
 * @date 2025-10-21
 */
class CreateCkanResourceSyncTable extends Migration
{
    /**
     * Ejecutar migración (crear tabla)
     *
     * @return void
     */
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'resource_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => 'Nombre interno del recurso (ej: miradores, aparcamientos)',
            ],
            'resource_id' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => 'UUID del recurso en CKAN',
            ],
            'last_modified' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Fecha de última modificación en CKAN',
            ],
            'last_synced' => [
                'type' => 'DATETIME',
                'comment' => 'Fecha de última sincronización local',
            ],
            'file_size' => [
                'type' => 'BIGINT',
                'null' => true,
                'comment' => 'Tamaño del archivo en bytes',
            ],
            'format' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'default' => 'SHP',
                'comment' => 'Formato del archivo (SHP, CSV, etc)',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['pending', 'success', 'error'],
                'default' => 'pending',
                'comment' => 'Estado de la última sincronización',
            ],
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Mensaje de error si status=error',
            ],
            'sqlite_path' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'comment' => 'Ruta al archivo SQLite generado',
            ],
            'geojson_path' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'comment' => 'Ruta al archivo GeoJSON generado',
            ],
            'feature_count' => [
                'type' => 'INT',
                'null' => true,
                'comment' => 'Número de features/registros procesados',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'comment' => 'Fecha de creación del registro',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'comment' => 'Fecha de última actualización del registro',
            ],
        ]);

        // Clave primaria
        $this->forge->addKey('id', true);

        // Índice único en resource_name (un solo registro por recurso)
        $this->forge->addUniqueKey('resource_name');

        // Índice en resource_id (para búsquedas por UUID CKAN)
        $this->forge->addKey('resource_id');

        // Índice en status (para filtrar errores)
        $this->forge->addKey('status');

        // Crear tabla
        $this->forge->createTable('ckan_resource_sync');

        echo "✅ Tabla 'ckan_resource_sync' creada exitosamente\n";
    }

    /**
     * Revertir migración (eliminar tabla)
     *
     * @return void
     */
    public function down()
    {
        $this->forge->dropTable('ckan_resource_sync');

        echo "❌ Tabla 'ckan_resource_sync' eliminada\n";
    }
}

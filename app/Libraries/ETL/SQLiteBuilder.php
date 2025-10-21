<?php

namespace App\Libraries\ETL;

/**
 * SQLiteBuilder
 * Librería para construir y gestionar la base de datos SQLite
 */
class SQLiteBuilder
{
    /**
     * Conexión PDO a SQLite
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * Ruta del directorio de bases de datos
     *
     * @var string
     */
    protected $dbDir;

    /**
     * Ruta de la base de datos actual
     *
     * @var string
     */
    protected $dbPath;

    /**
     * Nombre de la base de datos con versión
     *
     * @var string
     */
    protected $dbVersionName;

    /**
     * Estadísticas de la base de datos
     *
     * @var array
     */
    protected $stats = [
        'size' => 0,
        'tables' => 0,
        'indexes' => 0,
        'records' => 0,
    ];

    /**
     * Constructor
     *
     * @param bool $clean Si es true, elimina la base de datos existente
     */
    public function __construct(bool $clean = false)
    {
        $this->dbDir = ROOTPATH . 'data/db/';
        $this->dbVersionName = 'version_' . date('Ymd_His') . '.db';
        $this->dbPath = $this->dbDir . $this->dbVersionName;

        // Crear directorio si no existe
        if (!is_dir($this->dbDir)) {
            mkdir($this->dbDir, 0755, true);
        }

        // Limpiar base de datos existente si se solicita
        if ($clean && file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }

        // Conectar a SQLite
        $this->connect();
    }

    /**
     * Conectar a la base de datos SQLite
     *
     * @return void
     */
    protected function connect()
    {
        try {
            $this->pdo = new \PDO('sqlite:' . $this->dbPath);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Configurar SQLite para mejor performance
            $this->pdo->exec('PRAGMA journal_mode = WAL');
            $this->pdo->exec('PRAGMA synchronous = NORMAL');
            $this->pdo->exec('PRAGMA cache_size = 10000');
            $this->pdo->exec('PRAGMA temp_store = MEMORY');
            $this->pdo->exec('PRAGMA foreign_keys = ON');

        } catch (\PDOException $e) {
            throw new \RuntimeException("Error al conectar a SQLite: " . $e->getMessage());
        }
    }

    /**
     * Crear esquema completo de la base de datos
     *
     * @return void
     */
    public function createSchema()
    {
        // Iniciar transacción
        $this->pdo->beginTransaction();

        try {
            // Crear tablas dimensionales
            $this->createDimensionTables();

            // Crear tablas de hechos
            $this->createFactTables();

            // Confirmar transacción
            $this->pdo->commit();

            $this->stats['tables'] = $this->countTables();

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Crear tablas dimensionales
     *
     * @return void
     */
    protected function createDimensionTables()
    {
        // Dimensión: Municipios
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS dim_municipios (
                id INTEGER PRIMARY KEY,
                codigo_ine TEXT UNIQUE NOT NULL,
                nombre TEXT,
                provincia TEXT
            )
        ");

        // Dimensión: Especies
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS dim_especies (
                id INTEGER PRIMARY KEY,
                codigo TEXT UNIQUE NOT NULL,
                nombre_cientifico TEXT,
                nombre_comun TEXT
            )
        ");

        // Dimensión: Formaciones Arboladas
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS dim_formaciones_arboladas (
                id INTEGER PRIMARY KEY,
                codigo TEXT UNIQUE NOT NULL,
                nombre TEXT NOT NULL
            )
        ");
    }

    /**
     * Crear tablas de hechos
     *
     * @return void
     */
    protected function createFactTables()
    {
        // Tabla: Existencias
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS fact_existencias (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                municipio_id INTEGER NOT NULL,
                especie_id INTEGER NOT NULL,
                clase_diametro INTEGER,
                npies REAL,
                abas REAL,
                vcc REAL,
                vsc REAL,
                iavc REAL,
                vle REAL,
                ba REAL,
                br REAL,
                ca REAL,
                cr REAL,
                FOREIGN KEY (municipio_id) REFERENCES dim_municipios(id),
                FOREIGN KEY (especie_id) REFERENCES dim_especies(id)
            )
        ");

        // Tabla: IFN (consolidada para IFN2 e IFN3)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS fact_ifn (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ifn_version INTEGER NOT NULL,
                municipio_id INTEGER NOT NULL,
                especie_id INTEGER NOT NULL,
                clase_diametro INTEGER,
                npies REAL,
                abas REAL,
                vcc REAL,
                FOREIGN KEY (municipio_id) REFERENCES dim_municipios(id),
                FOREIGN KEY (especie_id) REFERENCES dim_especies(id)
            )
        ");

        // Tabla: Formaciones Arboladas (MFE25 + MFE50)
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS fact_formaciones_arboladas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                municipio_id INTEGER NOT NULL,
                formacion_id INTEGER NOT NULL,
                estrato_ifn TEXT,
                area_ha REAL,
                porcentaje REAL,
                fuente TEXT,
                FOREIGN KEY (municipio_id) REFERENCES dim_municipios(id),
                FOREIGN KEY (formacion_id) REFERENCES dim_formaciones_arboladas(id)
            )
        ");

        // Tabla: Formaciones Herbáceas
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS fact_formaciones_herbaceas (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                municipio_id INTEGER NOT NULL,
                formacion_codigo TEXT,
                formacion_nombre TEXT,
                area_ha REAL,
                porcentaje REAL,
                FOREIGN KEY (municipio_id) REFERENCES dim_municipios(id)
            )
        ");

        // Tabla: Formaciones de Matorral
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS fact_formaciones_matorral (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                municipio_id INTEGER NOT NULL,
                formacion_codigo TEXT,
                formacion_nombre TEXT,
                area_ha REAL,
                porcentaje REAL,
                FOREIGN KEY (municipio_id) REFERENCES dim_municipios(id)
            )
        ");

        // Tabla: LULUCF
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS fact_lulucf (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                municipio_id INTEGER NOT NULL,
                lulucf_codigo TEXT,
                lulucf_nombre TEXT,
                area_ha REAL,
                porcentaje REAL,
                FOREIGN KEY (municipio_id) REFERENCES dim_municipios(id)
            )
        ");

        // Tabla: Modelos de Combustible
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS fact_modelos_combustible (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                municipio_id INTEGER NOT NULL,
                modelo_combustible TEXT,
                modelo_nombre TEXT,
                area_ha REAL,
                porcentaje REAL,
                FOREIGN KEY (municipio_id) REFERENCES dim_municipios(id)
            )
        ");

        // Tabla: Propiedades
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS fact_propiedades (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                municipio_id INTEGER NOT NULL,
                propiedad_codigo TEXT,
                propiedad_nombre TEXT,
                area_ha REAL,
                porcentaje REAL,
                FOREIGN KEY (municipio_id) REFERENCES dim_municipios(id)
            )
        ");

        // Tabla: Valores
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS fact_valores (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                municipio_id INTEGER NOT NULL,
                valor_total REAL,
                valor_medio REAL,
                FOREIGN KEY (municipio_id) REFERENCES dim_municipios(id)
            )
        ");

        // Tabla: Calidades
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS fact_calidades (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                municipio_id INTEGER NOT NULL,
                calidad_estacion TEXT,
                calidad_valor REAL,
                FOREIGN KEY (municipio_id) REFERENCES dim_municipios(id)
            )
        ");
    }

    /**
     * Cargar datos normalizados en la base de datos
     *
     * @param array $processedData
     * @return void
     */
    public function loadData(array $processedData)
    {
        $this->pdo->beginTransaction();

        try {
            // Cargar dimensiones primero (municipios, especies, formaciones)
            $this->loadDimensions($processedData);

            // Cargar hechos
            foreach ($processedData as $fileName => $data) {
                $this->loadFactData($fileName, $data);
            }

            $this->pdo->commit();

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Cargar tablas dimensionales
     *
     * @param array $processedData
     * @return void
     */
    protected function loadDimensions(array $processedData)
    {
        // Extraer municipios únicos de todos los datasets
        $municipios = [];
        $especies = [];
        $formaciones = [];

        foreach ($processedData as $data) {
            foreach ($data as $row) {
                if (isset($row['municipio_id'])) {
                    $municipios[$row['municipio_id']] = true;
                }
                if (isset($row['especie_id'])) {
                    $especies[$row['especie_id']] = true;
                }
                if (isset($row['formacion_id'])) {
                    $formaciones[$row['formacion_id']] = true;
                }
            }
        }

        // Insertar municipios
        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO dim_municipios (id, codigo_ine) VALUES (?, ?)");
        foreach (array_keys($municipios) as $id) {
            $stmt->execute([$id, (string)$id]);
        }

        // Insertar especies
        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO dim_especies (id, codigo) VALUES (?, ?)");
        foreach (array_keys($especies) as $id) {
            $stmt->execute([$id, (string)$id]);
        }

        // Insertar formaciones arboladas
        $stmt = $this->pdo->prepare("INSERT OR IGNORE INTO dim_formaciones_arboladas (id, codigo, nombre) VALUES (?, ?, ?)");
        foreach (array_keys($formaciones) as $id) {
            $stmt->execute([$id, (string)$id, 'Formación ' . $id]);
        }
    }

    /**
     * Cargar datos de hechos según el archivo
     *
     * @param string $fileName
     * @param array $data
     * @return void
     */
    protected function loadFactData(string $fileName, array $data)
    {
        $mapping = [
            'Existencias_Muni_BMP_4.csv' => 'loadExistencias',
            'BMP_ExsMuniIFN2.csv' => 'loadIFN',
            'BMP_ExsMuniIFN3.csv' => 'loadIFN',
            'MFE25_FormArb.csv' => 'loadFormacionesArboladas',
            'MFE25_FormHerb.csv' => 'loadFormacionesHerbaceas',
            'MFE25_FormMato.csv' => 'loadFormacionesMatorral',
            'MFE25_LULUCF.csv' => 'loadLULUCF',
            'MFE25_ModCom.csv' => 'loadModelosCombustible',
            'MFE50_FormacionesArboladas_Mun.csv' => 'loadFormacionesArboladas',
            'Prop_BMP_Mun.csv' => 'loadPropiedades',
            'ValorMUNI_BMP.csv' => 'loadValores',
            'CalidadesMUNI.csv' => 'loadCalidades',
        ];

        $method = $mapping[$fileName] ?? null;

        if ($method && method_exists($this, $method)) {
            $this->$method($data);
        }
    }

    /**
     * Cargar datos de existencias
     *
     * @param array $data
     * @return void
     */
    protected function loadExistencias(array $data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO fact_existencias (
                municipio_id, especie_id, clase_diametro,
                npies, abas, vcc, vsc, iavc, vle, ba, br, ca, cr
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($data as $row) {
            $stmt->execute([
                $row['municipio_id'],
                $row['especie_id'],
                $row['clase_diametro'],
                $row['npies'],
                $row['abas'],
                $row['vcc'],
                $row['vsc'],
                $row['iavc'],
                $row['vle'],
                $row['ba'],
                $row['br'],
                $row['ca'],
                $row['cr'],
            ]);
        }
    }

    /**
     * Cargar datos IFN
     *
     * @param array $data
     * @return void
     */
    protected function loadIFN(array $data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO fact_ifn (
                ifn_version, municipio_id, especie_id, clase_diametro,
                npies, abas, vcc
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($data as $row) {
            $stmt->execute([
                $row['ifn_version'],
                $row['municipio_id'],
                $row['especie_id'],
                $row['clase_diametro'],
                $row['npies'],
                $row['abas'],
                $row['vcc'],
            ]);
        }
    }

    /**
     * Cargar formaciones arboladas
     *
     * @param array $data
     * @return void
     */
    protected function loadFormacionesArboladas(array $data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO fact_formaciones_arboladas (
                municipio_id, formacion_id, estrato_ifn, area_ha, porcentaje, fuente
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($data as $row) {
            $stmt->execute([
                $row['municipio_id'],
                $row['formacion_id'],
                $row['estrato_ifn'] ?? '',
                $row['area_ha'],
                $row['porcentaje'],
                'MFE25', // Se puede ajustar según origen
            ]);
        }
    }

    /**
     * Cargar formaciones herbáceas
     *
     * @param array $data
     * @return void
     */
    protected function loadFormacionesHerbaceas(array $data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO fact_formaciones_herbaceas (
                municipio_id, formacion_codigo, formacion_nombre, area_ha, porcentaje
            ) VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($data as $row) {
            $stmt->execute([
                $row['municipio_id'],
                $row['formacion_codigo'],
                $row['formacion_nombre'],
                $row['area_ha'],
                $row['porcentaje'],
            ]);
        }
    }

    /**
     * Cargar formaciones de matorral
     *
     * @param array $data
     * @return void
     */
    protected function loadFormacionesMatorral(array $data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO fact_formaciones_matorral (
                municipio_id, formacion_codigo, formacion_nombre, area_ha, porcentaje
            ) VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($data as $row) {
            $stmt->execute([
                $row['municipio_id'],
                $row['formacion_codigo'],
                $row['formacion_nombre'],
                $row['area_ha'],
                $row['porcentaje'],
            ]);
        }
    }

    /**
     * Cargar LULUCF
     *
     * @param array $data
     * @return void
     */
    protected function loadLULUCF(array $data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO fact_lulucf (
                municipio_id, lulucf_codigo, lulucf_nombre, area_ha, porcentaje
            ) VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($data as $row) {
            $stmt->execute([
                $row['municipio_id'],
                $row['lulucf_codigo'],
                $row['lulucf_nombre'],
                $row['area_ha'],
                $row['porcentaje'],
            ]);
        }
    }

    /**
     * Cargar modelos de combustible
     *
     * @param array $data
     * @return void
     */
    protected function loadModelosCombustible(array $data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO fact_modelos_combustible (
                municipio_id, modelo_combustible, modelo_nombre, area_ha, porcentaje
            ) VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($data as $row) {
            $stmt->execute([
                $row['municipio_id'],
                $row['modelo_combustible'],
                $row['modelo_nombre'],
                $row['area_ha'],
                $row['porcentaje'],
            ]);
        }
    }

    /**
     * Cargar propiedades
     *
     * @param array $data
     * @return void
     */
    protected function loadPropiedades(array $data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO fact_propiedades (
                municipio_id, propiedad_codigo, propiedad_nombre, area_ha, porcentaje
            ) VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($data as $row) {
            $stmt->execute([
                $row['municipio_id'],
                $row['propiedad_codigo'],
                $row['propiedad_nombre'],
                $row['area_ha'],
                $row['porcentaje'],
            ]);
        }
    }

    /**
     * Cargar valores
     *
     * @param array $data
     * @return void
     */
    protected function loadValores(array $data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO fact_valores (
                municipio_id, valor_total, valor_medio
            ) VALUES (?, ?, ?)
        ");

        foreach ($data as $row) {
            $stmt->execute([
                $row['municipio_id'],
                $row['valor_total'],
                $row['valor_medio'],
            ]);
        }
    }

    /**
     * Cargar calidades
     *
     * @param array $data
     * @return void
     */
    protected function loadCalidades(array $data)
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO fact_calidades (
                municipio_id, calidad_estacion, calidad_valor
            ) VALUES (?, ?, ?)
        ");

        foreach ($data as $row) {
            $stmt->execute([
                $row['municipio_id'],
                $row['calidad_estacion'],
                $row['calidad_valor'],
            ]);
        }
    }

    /**
     * Crear índices para optimizar consultas
     *
     * @return void
     */
    public function createIndexes()
    {
        $indexes = [
            "CREATE INDEX IF NOT EXISTS idx_existencias_municipio ON fact_existencias(municipio_id)",
            "CREATE INDEX IF NOT EXISTS idx_existencias_especie ON fact_existencias(especie_id)",
            "CREATE INDEX IF NOT EXISTS idx_existencias_cd ON fact_existencias(clase_diametro)",
            "CREATE INDEX IF NOT EXISTS idx_ifn_municipio ON fact_ifn(municipio_id)",
            "CREATE INDEX IF NOT EXISTS idx_ifn_especie ON fact_ifn(especie_id)",
            "CREATE INDEX IF NOT EXISTS idx_ifn_version ON fact_ifn(ifn_version)",
            "CREATE INDEX IF NOT EXISTS idx_formarb_municipio ON fact_formaciones_arboladas(municipio_id)",
            "CREATE INDEX IF NOT EXISTS idx_formarb_formacion ON fact_formaciones_arboladas(formacion_id)",
            "CREATE INDEX IF NOT EXISTS idx_formherb_municipio ON fact_formaciones_herbaceas(municipio_id)",
            "CREATE INDEX IF NOT EXISTS idx_formmato_municipio ON fact_formaciones_matorral(municipio_id)",
            "CREATE INDEX IF NOT EXISTS idx_lulucf_municipio ON fact_lulucf(municipio_id)",
            "CREATE INDEX IF NOT EXISTS idx_modcomb_municipio ON fact_modelos_combustible(municipio_id)",
            "CREATE INDEX IF NOT EXISTS idx_prop_municipio ON fact_propiedades(municipio_id)",
            "CREATE INDEX IF NOT EXISTS idx_valores_municipio ON fact_valores(municipio_id)",
            "CREATE INDEX IF NOT EXISTS idx_calidades_municipio ON fact_calidades(municipio_id)",
        ];

        foreach ($indexes as $index) {
            $this->pdo->exec($index);
        }

        $this->stats['indexes'] = count($indexes);
    }

    /**
     * Crear vistas SQL útiles
     *
     * @return void
     */
    public function createViews()
    {
        // Vista: Existencias por municipio
        $this->pdo->exec("
            CREATE VIEW IF NOT EXISTS vw_existencias_por_municipio AS
            SELECT
                m.codigo_ine,
                m.nombre AS municipio,
                e.codigo AS especie_codigo,
                SUM(f.vcc) AS volumen_total,
                SUM(f.npies) AS numero_pies
            FROM fact_existencias f
            JOIN dim_municipios m ON f.municipio_id = m.id
            JOIN dim_especies e ON f.especie_id = e.id
            GROUP BY m.id, e.id
        ");

        // Vista: Formaciones arboladas por municipio
        $this->pdo->exec("
            CREATE VIEW IF NOT EXISTS vw_formaciones_por_municipio AS
            SELECT
                m.codigo_ine,
                m.nombre AS municipio,
                fa.nombre AS formacion,
                SUM(f.area_ha) AS area_total_ha,
                AVG(f.porcentaje) AS porcentaje_promedio
            FROM fact_formaciones_arboladas f
            JOIN dim_municipios m ON f.municipio_id = m.id
            JOIN dim_formaciones_arboladas fa ON f.formacion_id = fa.id
            GROUP BY m.id, fa.id
        ");
    }

    /**
     * Optimizar base de datos
     *
     * @return void
     */
    public function optimize()
    {
        $this->pdo->exec('ANALYZE');
        $this->pdo->exec('VACUUM');
    }

    /**
     * Obtener estadísticas de la base de datos
     *
     * @return array
     */
    public function getStatistics(): array
    {
        $this->stats['size'] = file_exists($this->dbPath) ? filesize($this->dbPath) : 0;
        $this->stats['tables'] = $this->countTables();
        $this->stats['records'] = $this->countRecords();

        return $this->stats;
    }

    /**
     * Contar número de tablas
     *
     * @return int
     */
    protected function countTables(): int
    {
        $result = $this->pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table'");
        return (int) $result->fetchColumn();
    }

    /**
     * Contar total de registros
     *
     * @return int
     */
    protected function countRecords(): int
    {
        $tables = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'fact_%'");
        $total = 0;

        foreach ($tables as $table) {
            $result = $this->pdo->query("SELECT COUNT(*) FROM {$table['name']}");
            $total += (int) $result->fetchColumn();
        }

        return $total;
    }

    /**
     * Obtener ruta de la base de datos
     *
     * @return string
     */
    public function getDbPath(): string
    {
        return $this->dbPath;
    }

    /**
     * Obtener nombre de versión de la base de datos
     *
     * @return string
     */
    public function getDbVersionName(): string
    {
        return $this->dbVersionName;
    }

    /**
     * Crear enlace simbólico a la versión actual
     *
     * @return void
     */
    public function createSymlink()
    {
        $symlinkPath = $this->dbDir . 'current.db';

        // En Windows, copiar el archivo en lugar de symlink
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (file_exists($symlinkPath)) {
                unlink($symlinkPath);
            }
            copy($this->dbPath, $symlinkPath);
        } else {
            // En Unix/Linux, crear symlink real
            if (file_exists($symlinkPath) || is_link($symlinkPath)) {
                unlink($symlinkPath);
            }
            symlink($this->dbVersionName, $symlinkPath);
        }
    }

    /**
     * Destructor - cerrar conexión
     */
    public function __destruct()
    {
        $this->pdo = null;
    }
}

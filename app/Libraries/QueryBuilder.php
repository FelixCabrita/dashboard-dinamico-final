<?php

namespace App\Libraries;

/**
 * QueryBuilder
 * Constructor dinámico de consultas SQL para SQLite
 */
class QueryBuilder
{
    /**
     * Conexión PDO a la base de datos
     *
     * @var \PDO
     */
    protected $pdo;

    /**
     * Tabla actual
     *
     * @var string
     */
    protected $table;

    /**
     * Campos SELECT
     *
     * @var array
     */
    protected $select = ['*'];

    /**
     * Condiciones WHERE
     *
     * @var array
     */
    protected $where = [];

    /**
     * Parámetros para binding
     *
     * @var array
     */
    protected $bindings = [];

    /**
     * Campos GROUP BY
     *
     * @var array
     */
    protected $groupBy = [];

    /**
     * Condiciones HAVING
     *
     * @var array
     */
    protected $having = [];

    /**
     * Campos ORDER BY
     *
     * @var array
     */
    protected $orderBy = [];

    /**
     * Límite de resultados
     *
     * @var int|null
     */
    protected $limit;

    /**
     * Offset para paginación
     *
     * @var int|null
     */
    protected $offset;

    /**
     * Operadores permitidos
     *
     * @var array
     */
    protected $allowedOperators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN'];

    /**
     * Constructor
     *
     * @param string $dbPath Ruta a la base de datos SQLite
     */
    public function __construct(string $dbPath = null)
    {
        if ($dbPath === null) {
            $dbPath = ROOTPATH . 'data/db/current.db';
        }

        if (!file_exists($dbPath)) {
            throw new \RuntimeException("Base de datos no encontrada: {$dbPath}");
        }

        $this->pdo = new \PDO('sqlite:' . $dbPath);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Establecer tabla
     *
     * @param string $table
     * @return self
     */
    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Establecer campos SELECT
     *
     * @param array|string $fields
     * @return self
     */
    public function select($fields): self
    {
        if (is_string($fields)) {
            $fields = array_map('trim', explode(',', $fields));
        }

        $this->select = $fields;
        return $this;
    }

    /**
     * Agregar condición WHERE
     *
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @param string $boolean AND|OR
     * @return self
     */
    public function where(string $field, string $operator, $value, string $boolean = 'AND'): self
    {
        if (!in_array(strtoupper($operator), $this->allowedOperators)) {
            throw new \InvalidArgumentException("Operador no permitido: {$operator}");
        }

        $this->where[] = [
            'field' => $field,
            'operator' => strtoupper($operator),
            'value' => $value,
            'boolean' => strtoupper($boolean)
        ];

        return $this;
    }

    /**
     * Agregar condición WHERE con OR
     *
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @return self
     */
    public function orWhere(string $field, string $operator, $value): self
    {
        return $this->where($field, $operator, $value, 'OR');
    }

    /**
     * Aplicar filtros desde array
     *
     * @param array $filters Formato: ['field' => 'value'] o ['field' => ['operator' => '>', 'value' => 10]]
     * @return self
     */
    public function applyFilters(array $filters): self
    {
        foreach ($filters as $field => $filterData) {
            if (is_array($filterData)) {
                $operator = $filterData['operator'] ?? '=';
                $value = $filterData['value'] ?? null;
            } else {
                $operator = '=';
                $value = $filterData;
            }

            $this->where($field, $operator, $value);
        }

        return $this;
    }

    /**
     * GROUP BY
     *
     * @param array|string $fields
     * @return self
     */
    public function groupBy($fields): self
    {
        if (is_string($fields)) {
            $fields = array_map('trim', explode(',', $fields));
        }

        $this->groupBy = $fields;
        return $this;
    }

    /**
     * HAVING
     *
     * @param string $field
     * @param string $operator
     * @param mixed $value
     * @return self
     */
    public function having(string $field, string $operator, $value): self
    {
        if (!in_array(strtoupper($operator), $this->allowedOperators)) {
            throw new \InvalidArgumentException("Operador no permitido: {$operator}");
        }

        $this->having[] = [
            'field' => $field,
            'operator' => strtoupper($operator),
            'value' => $value
        ];

        return $this;
    }

    /**
     * ORDER BY
     *
     * @param string $field
     * @param string $direction ASC|DESC
     * @return self
     */
    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $direction = strtoupper($direction);

        if (!in_array($direction, ['ASC', 'DESC'])) {
            throw new \InvalidArgumentException("Dirección debe ser ASC o DESC");
        }

        $this->orderBy[] = "{$field} {$direction}";
        return $this;
    }

    /**
     * LIMIT
     *
     * @param int $limit
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * OFFSET
     *
     * @param int $offset
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Paginación
     *
     * @param int $page Número de página (1-based)
     * @param int $perPage Registros por página
     * @return self
     */
    public function paginate(int $page = 1, int $perPage = 50): self
    {
        $this->limit = $perPage;
        $this->offset = ($page - 1) * $perPage;
        return $this;
    }

    /**
     * Construir query SQL
     *
     * @return string
     */
    protected function buildQuery(): string
    {
        if (!$this->table) {
            throw new \RuntimeException("Tabla no especificada");
        }

        $sql = "SELECT " . implode(', ', $this->select) . " FROM {$this->table}";

        // WHERE
        if (!empty($this->where)) {
            $whereClauses = [];
            foreach ($this->where as $index => $condition) {
                $placeholder = ":{$condition['field']}_{$index}";

                if ($condition['operator'] === 'IN' || $condition['operator'] === 'NOT IN') {
                    $inValues = is_array($condition['value']) ? $condition['value'] : [$condition['value']];
                    $inPlaceholders = [];

                    foreach ($inValues as $inIndex => $inValue) {
                        $inPlaceholder = ":{$condition['field']}_{$index}_{$inIndex}";
                        $inPlaceholders[] = $inPlaceholder;
                        $this->bindings[$inPlaceholder] = $inValue;
                    }

                    $clause = "{$condition['field']} {$condition['operator']} (" . implode(', ', $inPlaceholders) . ")";
                } else {
                    $clause = "{$condition['field']} {$condition['operator']} {$placeholder}";
                    $this->bindings[$placeholder] = $condition['value'];
                }

                if ($index > 0) {
                    $whereClauses[] = "{$condition['boolean']} {$clause}";
                } else {
                    $whereClauses[] = $clause;
                }
            }

            $sql .= " WHERE " . implode(' ', $whereClauses);
        }

        // GROUP BY
        if (!empty($this->groupBy)) {
            $sql .= " GROUP BY " . implode(', ', $this->groupBy);
        }

        // HAVING
        if (!empty($this->having)) {
            $havingClauses = [];
            foreach ($this->having as $index => $condition) {
                $placeholder = ":having_{$condition['field']}_{$index}";
                $havingClauses[] = "{$condition['field']} {$condition['operator']} {$placeholder}";
                $this->bindings[$placeholder] = $condition['value'];
            }

            $sql .= " HAVING " . implode(' AND ', $havingClauses);
        }

        // ORDER BY
        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY " . implode(', ', $this->orderBy);
        }

        // LIMIT
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        // OFFSET
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return $sql;
    }

    /**
     * Ejecutar query y obtener resultados
     *
     * @return array
     */
    public function get(): array
    {
        $sql = $this->buildQuery();
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->bindings);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Obtener primer resultado
     *
     * @return array|null
     */
    public function first(): ?array
    {
        $this->limit(1);
        $results = $this->get();

        return $results[0] ?? null;
    }

    /**
     * Contar registros
     *
     * @return int
     */
    public function count(): int
    {
        $originalSelect = $this->select;
        $this->select = ['COUNT(*) as count'];

        // Remover ORDER BY para COUNT
        $originalOrderBy = $this->orderBy;
        $this->orderBy = [];

        // Remover LIMIT y OFFSET para COUNT total
        $originalLimit = $this->limit;
        $originalOffset = $this->offset;
        $this->limit = null;
        $this->offset = null;

        $result = $this->first();

        // Restaurar valores originales
        $this->select = $originalSelect;
        $this->orderBy = $originalOrderBy;
        $this->limit = $originalLimit;
        $this->offset = $originalOffset;

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Agregación personalizada
     *
     * @param string $function SUM, AVG, MIN, MAX, COUNT
     * @param string $field Campo a agregar
     * @param string $alias Alias del resultado
     * @return self
     */
    public function aggregate(string $function, string $field, string $alias = null): self
    {
        $function = strtoupper($function);
        $allowedFunctions = ['SUM', 'AVG', 'MIN', 'MAX', 'COUNT'];

        if (!in_array($function, $allowedFunctions)) {
            throw new \InvalidArgumentException("Función de agregación no permitida: {$function}");
        }

        $alias = $alias ?? strtolower($function) . '_' . $field;
        $this->select[] = "{$function}({$field}) AS {$alias}";

        return $this;
    }

    /**
     * Obtener tablas disponibles en la base de datos
     *
     * @return array
     */
    public function getTables(): array
    {
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        $tables = [];

        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $tableName = $row['name'];

            // Contar registros
            $countStmt = $this->pdo->query("SELECT COUNT(*) as count FROM {$tableName}");
            $count = $countStmt->fetch(\PDO::FETCH_ASSOC)['count'];

            $tables[] = [
                'name' => $tableName,
                'records' => (int) $count
            ];
        }

        return $tables;
    }

    /**
     * Obtener columnas de una tabla
     *
     * @param string $table
     * @return array
     */
    public function getColumns(string $table): array
    {
        $stmt = $this->pdo->query("PRAGMA table_info({$table})");
        $columns = [];

        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $column) {
            $columns[] = [
                'name' => $column['name'],
                'type' => $column['type'],
                'nullable' => !$column['notnull'],
                'primary_key' => (bool) $column['pk']
            ];
        }

        return $columns;
    }

    /**
     * Resetear el builder
     *
     * @return self
     */
    public function reset(): self
    {
        $this->select = ['*'];
        $this->where = [];
        $this->bindings = [];
        $this->groupBy = [];
        $this->having = [];
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = null;

        return $this;
    }

    /**
     * Obtener SQL generado (para debugging)
     *
     * @return string
     */
    public function toSQL(): string
    {
        return $this->buildQuery();
    }

    /**
     * Obtener bindings (para debugging)
     *
     * @return array
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }
}

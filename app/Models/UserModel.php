<?php

namespace App\Models;

/**
 * UserModel
 * Modelo para gestión de usuarios almacenados en JSON
 * Todos los usuarios son de tipo "admin"
 *
 * NOTA: No extiende de CodeIgniter\Model para evitar conflictos
 * de nombres de métodos (find, findAll, update, delete, etc.)
 */
class UserModel
{
    protected $jsonFile = WRITEPATH . 'users.json';

    /**
     * Obtener todos los usuarios
     */
    public function findAll()
    {
        $users = $this->readJSON();
        return $users;
    }

    /**
     * Buscar usuario por ID
     */
    public function findUser($id)
    {
        $users = $this->readJSON();

        foreach ($users as $user) {
            if ($user['id'] == $id) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Buscar usuario por email
     */
    public function findByEmail($email)
    {
        $users = $this->readJSON();

        foreach ($users as $user) {
            if ($user['email'] === $email) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Crear nuevo usuario
     */
    public function create($data)
    {
        $users = $this->readJSON();

        // Verificar que el email no exista
        if ($this->findByEmail($data['email'])) {
            return false;
        }

        // Generar ID autoincrementable
        $newId = empty($users) ? 1 : max(array_column($users, 'id')) + 1;

        // Crear usuario
        $user = [
            'id' => $newId,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => 'admin', // Todos son admin
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        $users[] = $user;
        $this->writeJSON($users);

        return $newId;
    }

    /**
     * Actualizar usuario
     */
    public function updateUser($id, $data)
    {
        $users = $this->readJSON();
        $updated = false;

        foreach ($users as $key => $user) {
            if ($user['id'] == $id) {
                // Actualizar campos
                $users[$key]['name'] = $data['name'] ?? $user['name'];
                $users[$key]['email'] = $data['email'] ?? $user['email'];

                // Solo actualizar contraseña si se proporciona
                if (!empty($data['password'])) {
                    $users[$key]['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                }

                $users[$key]['updated_at'] = date('Y-m-d H:i:s');
                $updated = true;
                break;
            }
        }

        if ($updated) {
            $this->writeJSON($users);
            return true;
        }

        return false;
    }

    /**
     * Eliminar usuario
     */
    public function deleteUser($id)
    {
        $users = $this->readJSON();

        // No permitir eliminar si es el último admin
        if (count($users) <= 1) {
            return false;
        }

        $newUsers = array_filter($users, function($user) use ($id) {
            return $user['id'] != $id;
        });

        // Reindexar array
        $newUsers = array_values($newUsers);

        if (count($newUsers) < count($users)) {
            $this->writeJSON($newUsers);
            return true;
        }

        return false;
    }

    /**
     * Autenticar usuario
     */
    public function authenticate($email, $password)
    {
        $user = $this->findByEmail($email);

        if (!$user) {
            return false;
        }

        if (password_verify($password, $user['password'])) {
            // No devolver la contraseña
            unset($user['password']);
            return $user;
        }

        return false;
    }

    /**
     * Contar usuarios
     */
    public function countAll()
    {
        $users = $this->readJSON();
        return count($users);
    }

    /**
     * Leer archivo JSON
     */
    private function readJSON()
    {
        if (!file_exists($this->jsonFile)) {
            return [];
        }

        $content = file_get_contents($this->jsonFile);
        $users = json_decode($content, true);

        return $users ?: [];
    }

    /**
     * Escribir archivo JSON
     */
    private function writeJSON($users)
    {
        $content = json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($this->jsonFile, $content);
    }

    /**
     * Verificar si existe al menos un usuario
     */
    public function hasUsers()
    {
        $users = $this->readJSON();
        return count($users) > 0;
    }

    /**
     * Buscar usuarios con filtros y paginación
     *
     * @param int $perPage Usuarios por página
     * @param int $page Página actual
     * @param string $search Término de búsqueda (nombre o email)
     * @param string $orderBy Campo para ordenar (name, email, created_at)
     * @param string $orderDir Dirección (asc, desc)
     * @return array ['data' => usuarios, 'total' => total, 'page' => página actual, 'perPage' => por página]
     */
    public function findAllPaginated($perPage = 10, $page = 1, $search = '', $orderBy = 'created_at', $orderDir = 'desc')
    {
        $users = $this->readJSON();

        // Aplicar búsqueda si existe
        if (!empty($search)) {
            $users = $this->search($users, $search);
        }

        // Ordenar
        $users = $this->orderUsers($users, $orderBy, $orderDir);

        // Contar total después de filtros
        $total = count($users);

        // Calcular offset
        $offset = ($page - 1) * $perPage;

        // Aplicar paginación
        $paginatedUsers = array_slice($users, $offset, $perPage);

        return [
            'data' => $paginatedUsers,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }

    /**
     * Buscar usuarios por término (nombre o email)
     *
     * @param array $users Lista de usuarios
     * @param string $term Término de búsqueda
     * @return array Usuarios filtrados
     */
    private function search($users, $term)
    {
        $term = strtolower(trim($term));

        return array_filter($users, function($user) use ($term) {
            $name = strtolower($user['name'] ?? '');
            $email = strtolower($user['email'] ?? '');

            return strpos($name, $term) !== false || strpos($email, $term) !== false;
        });
    }

    /**
     * Ordenar usuarios por campo
     *
     * @param array $users Lista de usuarios
     * @param string $orderBy Campo (name, email, created_at)
     * @param string $orderDir Dirección (asc, desc)
     * @return array Usuarios ordenados
     */
    private function orderUsers($users, $orderBy, $orderDir)
    {
        usort($users, function($a, $b) use ($orderBy, $orderDir) {
            $valueA = $a[$orderBy] ?? '';
            $valueB = $b[$orderBy] ?? '';

            if ($orderDir === 'asc') {
                return strcmp($valueA, $valueB);
            } else {
                return strcmp($valueB, $valueA);
            }
        });

        return $users;
    }

    /**
     * Obtener estadísticas de usuarios
     *
     * @return array Estadísticas
     */
    public function getStatistics()
    {
        $users = $this->readJSON();
        $total = count($users);

        // Usuarios creados hoy
        $today = date('Y-m-d');
        $createdToday = array_filter($users, function($user) use ($today) {
            return strpos($user['created_at'] ?? '', $today) === 0;
        });

        // Usuarios creados esta semana
        $weekAgo = date('Y-m-d', strtotime('-7 days'));
        $createdThisWeek = array_filter($users, function($user) use ($weekAgo) {
            return ($user['created_at'] ?? '') >= $weekAgo;
        });

        // Último usuario registrado
        $lastUser = !empty($users) ? end($users) : null;

        return [
            'total' => $total,
            'created_today' => count($createdToday),
            'created_this_week' => count($createdThisWeek),
            'last_user' => $lastUser
        ];
    }

    /**
     * Verificar si un email está en uso (excluyendo un ID específico)
     * Útil para validar en edición
     *
     * @param string $email Email a verificar
     * @param int $excludeId ID a excluir de la búsqueda
     * @return bool True si el email está en uso por otro usuario
     */
    public function isEmailTaken($email, $excludeId = null)
    {
        $user = $this->findByEmail($email);

        if (!$user) {
            return false;
        }

        // Si hay un ID a excluir y es el mismo usuario, el email no está "tomado"
        if ($excludeId !== null && $user['id'] == $excludeId) {
            return false;
        }

        return true;
    }
}

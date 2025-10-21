<?php

namespace App\Libraries;

/**
 * CacheManager
 * Sistema de caché basado en archivos para consultas API
 */
class CacheManager
{
    /**
     * Directorio de caché
     *
     * @var string
     */
    protected $cacheDir;

    /**
     * TTL por defecto (en segundos)
     *
     * @var int
     */
    protected $defaultTTL = 3600; // 1 hora

    /**
     * Prefijo para claves de caché
     *
     * @var string
     */
    protected $prefix = 'query_';

    /**
     * Constructor
     *
     * @param int|null $ttl TTL personalizado en segundos
     */
    public function __construct(int $ttl = null)
    {
        $this->cacheDir = WRITEPATH . 'cache/queries/';

        if ($ttl !== null) {
            $this->defaultTTL = $ttl;
        }

        // Crear directorio si no existe
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Generar clave de caché única
     *
     * @param string $key
     * @param array $params Parámetros adicionales para la clave
     * @return string
     */
    public function generateKey(string $key, array $params = []): string
    {
        $keyData = $key . serialize($params);
        return $this->prefix . md5($keyData);
    }

    /**
     * Obtener valor del caché
     *
     * @param string $key
     * @return mixed|null null si no existe o está expirado
     */
    public function get(string $key)
    {
        $filePath = $this->getFilePath($key);

        if (!file_exists($filePath)) {
            return null;
        }

        $data = unserialize(file_get_contents($filePath));

        // Verificar si expiró
        if ($data['expires_at'] < time()) {
            $this->delete($key);
            return null;
        }

        return $data['value'];
    }

    /**
     * Guardar valor en caché
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl TTL personalizado en segundos
     * @return bool
     */
    public function set(string $key, $value, int $ttl = null): bool
    {
        $ttl = $ttl ?? $this->defaultTTL;
        $filePath = $this->getFilePath($key);

        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time()
        ];

        return file_put_contents($filePath, serialize($data)) !== false;
    }

    /**
     * Verificar si existe en caché y no está expirado
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Eliminar del caché
     *
     * @param string $key
     * @return bool
     */
    public function delete(string $key): bool
    {
        $filePath = $this->getFilePath($key);

        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * Limpiar todo el caché
     *
     * @return bool
     */
    public function flush(): bool
    {
        $files = glob($this->cacheDir . $this->prefix . '*');

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return true;
    }

    /**
     * Limpiar caché expirado
     *
     * @return int Número de archivos eliminados
     */
    public function cleanExpired(): int
    {
        $files = glob($this->cacheDir . $this->prefix . '*');
        $deleted = 0;

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $data = unserialize(file_get_contents($file));

            if ($data['expires_at'] < time()) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Recordar: Obtener del caché o ejecutar callback y cachear
     *
     * @param string $key
     * @param callable $callback
     * @param int|null $ttl
     * @return mixed
     */
    public function remember(string $key, callable $callback, int $ttl = null)
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * Obtener ruta del archivo de caché
     *
     * @param string $key
     * @return string
     */
    protected function getFilePath(string $key): string
    {
        return $this->cacheDir . $key;
    }

    /**
     * Obtener estadísticas del caché
     *
     * @return array
     */
    public function getStats(): array
    {
        $files = glob($this->cacheDir . $this->prefix . '*');
        $totalSize = 0;
        $expired = 0;
        $active = 0;

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            $totalSize += filesize($file);
            $data = unserialize(file_get_contents($file));

            if ($data['expires_at'] < time()) {
                $expired++;
            } else {
                $active++;
            }
        }

        return [
            'total_entries' => count($files),
            'active_entries' => $active,
            'expired_entries' => $expired,
            'total_size' => $totalSize,
            'total_size_mb' => round($totalSize / 1024 / 1024, 2)
        ];
    }

    /**
     * Invalidar caché por patrón
     *
     * @param string $pattern Patrón para matching (ej: 'table_existencias_*')
     * @return int Número de archivos eliminados
     */
    public function invalidatePattern(string $pattern): int
    {
        $pattern = $this->prefix . $pattern;
        $files = glob($this->cacheDir . $pattern);
        $deleted = 0;

        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Configurar TTL por defecto
     *
     * @param int $seconds
     * @return self
     */
    public function setDefaultTTL(int $seconds): self
    {
        $this->defaultTTL = $seconds;
        return $this;
    }

    /**
     * Obtener TTL por defecto
     *
     * @return int
     */
    public function getDefaultTTL(): int
    {
        return $this->defaultTTL;
    }

    /**
     * Configurar prefijo de claves
     *
     * @param string $prefix
     * @return self
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;
        return $this;
    }
}

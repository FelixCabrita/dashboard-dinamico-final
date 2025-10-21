<?php

/**
 * Helper personalizado para URLs
 * Usa base_url() de CodeIgniter para generar URLs correctas
 * IMPORTANTE: Las rutas son RELATIVAS (sin "/" al inicio) para que
 * CodeIgniter agregue automáticamente el baseURL configurado
 */

if (!function_exists('admin_url')) {
    /**
     * Genera URL para el panel admin usando base_url() de CodeIgniter
     *
     * @param string $path Ruta relativa (ej: 'users', 'dashboard')
     * @return string URL completa
     */
    function admin_url($path = '')
    {
        // Ruta relativa SIN "/" al inicio para que CodeIgniter agregue el baseURL
        $relativePath = 'admin-panel-bmp';

        if (!empty($path)) {
            $path = ltrim($path, '/');
            $relativePath .= '/' . $path;
        }

        // Usar base_url() nativo de CodeIgniter
        return base_url($relativePath);
    }
}

if (!function_exists('public_url')) {
    /**
     * Genera URL para recursos públicos (CSS, JS, imágenes)
     * Usa base_url() de CodeIgniter
     *
     * @param string $path Ruta relativa (ej: 'css/admin.css')
     * @return string URL completa
     */
    function public_url($path = '')
    {
        if (!empty($path)) {
            $path = ltrim($path, '/');
            return base_url($path);
        }

        return base_url();
    }
}

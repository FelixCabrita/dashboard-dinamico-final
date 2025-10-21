<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AuthFilter
 * Protege rutas administrativas verificando sesión activa
 */
class AuthFilter implements FilterInterface
{
    /**
     * Ejecutar antes de la petición
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Verificar si el usuario está autenticado
        if (!session()->has('logged_in') || !session()->get('logged_in')) {
            // Guardar URL intentada para redireccionar después del login
            session()->set('redirect_url', current_url());

            return redirect()
                ->to(admin_url('login'))
                ->with('error', 'Debe iniciar sesión para acceder al panel administrativo');
        }
    }

    /**
     * Ejecutar después de la petición
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No hacer nada después
    }
}


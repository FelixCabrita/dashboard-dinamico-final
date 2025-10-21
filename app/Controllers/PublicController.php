<?php

namespace App\Controllers;

/**
 * PublicController
 * Controlador para la landing page pública
 * SIN referencias al panel administrativo
 */
class PublicController extends BaseController
{
    /**
     * Landing page principal
     */
    public function index()
    {
        $data = [
            'title' => 'Bosque Modelo Palencia - Dashboard Ambiental',
            'contact_email' => getenv('contact.email') ?? 'secretariatecnica@palenciabosquemodelo.es',
            'primary_color' => getenv('branding.primaryColor') ?? '#6D0C6D',
            'secondary_color' => getenv('branding.secondaryColor') ?? '#010101'
        ];

        return view('landing/index', $data);
    }

    /**
     * Vista de dashboard público
     * (Hito 6: cargará dashboards dinámicos)
     */
    public function dashboard($slug = null)
    {
        // Por ahora, muestra un dashboard estático de ejemplo
        $data = [
            'title' => 'Dashboard - Bosque Modelo Palencia',
            'dashboard_slug' => $slug,
            'contact_email' => getenv('contact.email') ?? 'secretariatecnica@palenciabosquemodelo.es'
        ];

        return view('landing/dashboard', $data);
    }
}

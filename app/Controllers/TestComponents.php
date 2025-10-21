<?php

namespace App\Controllers;

use App\Libraries\ComponentRenderer;
use App\Models\ComponentModel;

/**
 * TestComponents Controller
 * Página de pruebas para visualizar y testear componentes
 */
class TestComponents extends BaseController
{
    /**
     * ComponentRenderer
     *
     * @var ComponentRenderer
     */
    protected $renderer;

    /**
     * ComponentModel
     *
     * @var ComponentModel
     */
    protected $componentModel;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->renderer = new ComponentRenderer();
        $this->componentModel = new ComponentModel();
    }

    /**
     * Vista principal de pruebas
     *
     * @return string
     */
    public function index()
    {
        // Obtener todos los componentes
        $components = $this->componentModel->findAll();

        // Agrupar por tipo
        $componentsByType = [
            'kpi' => [],
            'table' => [],
            'line_chart' => [],
            'bar_chart' => [],
            'pie_chart' => []
        ];

        foreach ($components as $component) {
            $type = $component['type'];
            if (isset($componentsByType[$type])) {
                $componentsByType[$type][] = $component;
            }
        }

        $data = [
            'title' => 'Pruebas de Componentes - Bosque Modelo Palencia',
            'components' => $components,
            'componentsByType' => $componentsByType,
            'totalComponents' => count($components)
        ];

        return view('test/components', $data);
    }

    /**
     * Renderizar un componente específico (AJAX)
     *
     * @param string $id ID del componente
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function render(string $id)
    {
        try {
            // Obtener filtros del request
            $filters = [];
            if ($this->request->getGet('year')) {
                $filters['year'] = $this->request->getGet('year');
            }
            if ($this->request->getGet('municipality')) {
                $filters['municipality'] = $this->request->getGet('municipality');
            }
            if ($this->request->getGet('province')) {
                $filters['province'] = $this->request->getGet('province');
            }

            // Obtener tema
            $theme = $this->request->getGet('theme') ?? 'corporativo';

            // Renderizar componente
            $html = $this->renderer->render($id, $filters, ['theme' => $theme]);

            return $this->response->setJSON([
                'success' => true,
                'html' => $html
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Renderizar todos los componentes (para testing)
     *
     * @return string
     */
    public function renderAll()
    {
        $components = $this->componentModel->findAll();
        $theme = $this->request->getGet('theme') ?? 'corporativo';

        $html = '';
        foreach ($components as $component) {
            try {
                $html .= '<div style="margin-bottom: 40px;">';
                $html .= '<h3>' . esc($component['name']) . ' (' . esc($component['type']) . ')</h3>';
                $html .= $this->renderer->render($component['id'], [], ['theme' => $theme]);
                $html .= '</div>';
            } catch (\Exception $e) {
                $html .= '<div class="alert alert-danger">Error: ' . esc($e->getMessage()) . '</div>';
            }
        }

        $data = [
            'title' => 'Renderizado de Todos los Componentes',
            'html' => $html,
            'theme' => $theme
        ];

        return view('test/render_all', $data);
    }
}

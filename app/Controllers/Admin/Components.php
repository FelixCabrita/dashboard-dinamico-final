<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ComponentModel;

/**
 * Components Controller
 * Gestión de componentes visuales desde el panel administrativo
 */
class Components extends BaseController
{
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
        $this->componentModel = new ComponentModel();
    }

    /**
     * Vista principal - Listado de componentes
     *
     * @return string
     */
    public function index()
    {
        // Verificar autenticación
        if (!session()->has('user_id')) {
            return redirect()->to(admin_url('login'));
        }

        // Obtener filtros
        $type = $this->request->getGet('type');
        $search = $this->request->getGet('search');
        $page = (int) ($this->request->getGet('page') ?? 1);

        $filters = [];
        if ($type) {
            $filters['type'] = $type;
        }
        if ($search) {
            $filters['search'] = $search;
        }

        // Obtener componentes paginados
        $result = $this->componentModel->paginate($page, 12, $filters);
        $stats = $this->componentModel->getStatistics();

        $data = [
            'title' => 'Gestión de Componentes',
            'components' => $result['data'],
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'perPage' => $result['perPage'],
                'totalPages' => $result['totalPages']
            ],
            'stats' => $stats,
            'filters' => $filters,
            'allowedTypes' => $this->componentModel->getAllowedTypes()
        ];

        return view('admin/components/index', $data);
    }

    /**
     * Wizard de creación de componente
     *
     * @return string
     */
    public function create()
    {
        // Verificar autenticación
        if (!session()->has('user_id')) {
            return redirect()->to(admin_url('login'));
        }

        $data = [
            'title' => 'Crear Componente',
            'allowedTypes' => $this->componentModel->getAllowedTypes()
        ];

        return view('admin/components/wizard', $data);
    }

    /**
     * Guardar nuevo componente
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function store()
    {
        // Verificar autenticación
        if (!session()->has('user_id')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No autenticado'
            ])->setStatusCode(401);
        }

        try {
            $data = $this->request->getJSON(true);

            // Validar
            $errors = $this->componentModel->validate($data);
            if (!empty($errors)) {
                return $this->response->setJSON([
                    'success' => false,
                    'errors' => $errors
                ])->setStatusCode(400);
            }

            // Crear componente
            $id = $this->componentModel->create($data);

            if ($id) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Componente creado exitosamente',
                    'id' => $id
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Error al crear componente'
                ])->setStatusCode(500);
            }

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Editar componente existente
     *
     * @param string $id
     * @return string
     */
    public function edit(string $id)
    {
        // Verificar autenticación
        if (!session()->has('user_id')) {
            return redirect()->to(admin_url('login'));
        }

        $component = $this->componentModel->find($id);

        if (!$component) {
            return redirect()
                ->to(admin_url('components'))
                ->with('error', 'Componente no encontrado');
        }

        $data = [
            'title' => 'Editar Componente',
            'component' => $component,
            'allowedTypes' => $this->componentModel->getAllowedTypes()
        ];

        return view('admin/components/wizard', $data);
    }

    /**
     * Actualizar componente
     *
     * @param string $id
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function update(string $id)
    {
        // Verificar autenticación
        if (!session()->has('user_id')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No autenticado'
            ])->setStatusCode(401);
        }

        try {
            $data = $this->request->getJSON(true);

            // Validar
            $errors = $this->componentModel->validate($data);
            if (!empty($errors)) {
                return $this->response->setJSON([
                    'success' => false,
                    'errors' => $errors
                ])->setStatusCode(400);
            }

            // Actualizar componente
            $updated = $this->componentModel->update($id, $data);

            if ($updated) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Componente actualizado exitosamente'
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Componente no encontrado'
                ])->setStatusCode(404);
            }

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Eliminar componente
     *
     * @param string $id
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function delete(string $id)
    {
        // Verificar autenticación
        if (!session()->has('user_id')) {
            return redirect()->to(admin_url('login'));
        }

        try {
            $deleted = $this->componentModel->delete($id);

            if ($deleted) {
                return redirect()
                    ->to(admin_url('components'))
                    ->with('success', 'Componente eliminado exitosamente');
            } else {
                return redirect()
                    ->to(admin_url('components'))
                    ->with('error', 'Componente no encontrado');
            }

        } catch (\Exception $e) {
            return redirect()
                ->to(admin_url('components'))
                ->with('error', 'Error al eliminar componente: ' . $e->getMessage());
        }
    }

    /**
     * Duplicar componente
     *
     * @param string $id
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function duplicate(string $id)
    {
        // Verificar autenticación
        if (!session()->has('user_id')) {
            return redirect()->to(admin_url('login'));
        }

        try {
            $newId = $this->componentModel->duplicate($id);

            if ($newId) {
                return redirect()
                    ->to(admin_url('components'))
                    ->with('success', 'Componente duplicado exitosamente');
            } else {
                return redirect()
                    ->to(admin_url('components'))
                    ->with('error', 'Componente no encontrado');
            }

        } catch (\Exception $e) {
            return redirect()
                ->to(admin_url('components'))
                ->with('error', 'Error al duplicar componente: ' . $e->getMessage());
        }
    }

    /**
     * Vista previa de componente (AJAX)
     *
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function preview()
    {
        try {
            $data = $this->request->getJSON(true);

            // Aquí se generaría la vista previa del componente
            // Por ahora retornamos los datos para que el frontend los renderice

            return $this->response->setJSON([
                'success' => true,
                'data' => $data,
                'message' => 'Vista previa generada'
            ]);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    /**
     * Obtener componente por ID (AJAX)
     *
     * @param string $id
     * @return \CodeIgniter\HTTP\ResponseInterface
     */
    public function show(string $id)
    {
        try {
            $component = $this->componentModel->find($id);

            if ($component) {
                return $this->response->setJSON([
                    'success' => true,
                    'data' => $component
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Componente no encontrado'
                ])->setStatusCode(404);
            }

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }
}

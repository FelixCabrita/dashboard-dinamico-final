<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

/**
 * Users Controller
 * CRUD completo de usuarios (Hito 2)
 */
class Users extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Listar usuarios con paginación y búsqueda
     */
    public function index()
    {
        // Obtener parámetros de búsqueda y paginación
        $perPage = 10;
        $page = $this->request->getGet('page') ?? 1;
        $search = $this->request->getGet('search') ?? '';
        $orderBy = $this->request->getGet('order_by') ?? 'created_at';
        $orderDir = $this->request->getGet('order_dir') ?? 'desc';

        // Obtener usuarios paginados
        $result = $this->userModel->findAllPaginated($perPage, $page, $search, $orderBy, $orderDir);

        $data = [
            'title' => 'Gestión de Usuarios',
            'users' => $result['data'],
            'pagination' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'perPage' => $result['perPage'],
                'totalPages' => $result['totalPages']
            ],
            'search' => $search,
            'orderBy' => $orderBy,
            'orderDir' => $orderDir
        ];

        return view('admin/users/index', $data);
    }

    /**
     * Formulario crear usuario
     */
    public function create()
    {
        $data = [
            'title' => 'Crear Usuario'
        ];

        return view('admin/users/create', $data);
    }

    /**
     * Guardar nuevo usuario
     */
    public function store()
    {
        $validation = \Config\Services::validation();

        // Reglas de validación avanzadas
        $validation->setRules([
            'name' => [
                'label' => 'Nombre',
                'rules' => 'required|min_length[3]|max_length[100]|alpha_numeric_space',
                'errors' => [
                    'required' => 'El {field} es obligatorio',
                    'min_length' => 'El {field} debe tener al menos {param} caracteres',
                    'max_length' => 'El {field} no puede exceder {param} caracteres',
                    'alpha_numeric_space' => 'El {field} solo puede contener letras, números y espacios'
                ]
            ],
            'email' => [
                'label' => 'Email',
                'rules' => 'required|valid_email|max_length[255]',
                'errors' => [
                    'required' => 'El {field} es obligatorio',
                    'valid_email' => 'Debe ingresar un {field} válido',
                    'max_length' => 'El {field} no puede exceder {param} caracteres'
                ]
            ],
            'password' => [
                'label' => 'Contraseña',
                'rules' => 'required|min_length[8]|max_length[255]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/]',
                'errors' => [
                    'required' => 'La {field} es obligatoria',
                    'min_length' => 'La {field} debe tener al menos {param} caracteres',
                    'max_length' => 'La {field} no puede exceder {param} caracteres',
                    'regex_match' => 'La {field} debe contener al menos una mayúscula, una minúscula y un número'
                ]
            ],
            'password_confirm' => [
                'label' => 'Confirmar Contraseña',
                'rules' => 'required|matches[password]',
                'errors' => [
                    'required' => 'Debe confirmar la contraseña',
                    'matches' => 'Las contraseñas no coinciden'
                ]
            ]
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->with('errors', $validation->getErrors())->withInput();
        }

        // Verificar que el email no exista
        $email = $this->request->getPost('email');
        if ($this->userModel->isEmailTaken($email)) {
            return redirect()->back()->with('error', 'El email ya está registrado')->withInput();
        }

        $data = [
            'name' => trim($this->request->getPost('name')),
            'email' => trim(strtolower($this->request->getPost('email'))),
            'password' => $this->request->getPost('password')
        ];

        if ($this->userModel->create($data)) {
            return redirect()->to(admin_url('users'))->with('success', 'Usuario creado correctamente');
        } else {
            return redirect()->back()->with('error', 'Error al crear el usuario')->withInput();
        }
    }

    /**
     * Formulario editar usuario
     */
    public function edit($id)
    {
        $user = $this->userModel->findUser($id);

        if (!$user) {
            return redirect()->to(admin_url('users'))->with('error', 'Usuario no encontrado');
        }

        $data = [
            'title' => 'Editar Usuario',
            'user' => $user
        ];

        return view('admin/users/edit', $data);
    }

    /**
     * Actualizar usuario
     */
    public function update($id)
    {
        // Verificar que el usuario exista
        $user = $this->userModel->findUser($id);
        if (!$user) {
            return redirect()->to(admin_url('users'))->with('error', 'Usuario no encontrado');
        }

        $validation = \Config\Services::validation();

        // Reglas de validación avanzadas
        $rules = [
            'name' => [
                'label' => 'Nombre',
                'rules' => 'required|min_length[3]|max_length[100]|alpha_numeric_space',
                'errors' => [
                    'required' => 'El {field} es obligatorio',
                    'min_length' => 'El {field} debe tener al menos {param} caracteres',
                    'max_length' => 'El {field} no puede exceder {param} caracteres',
                    'alpha_numeric_space' => 'El {field} solo puede contener letras, números y espacios'
                ]
            ],
            'email' => [
                'label' => 'Email',
                'rules' => 'required|valid_email|max_length[255]',
                'errors' => [
                    'required' => 'El {field} es obligatorio',
                    'valid_email' => 'Debe ingresar un {field} válido',
                    'max_length' => 'El {field} no puede exceder {param} caracteres'
                ]
            ]
        ];

        // Solo validar contraseña si se proporciona
        if ($this->request->getPost('password')) {
            $rules['password'] = [
                'label' => 'Contraseña',
                'rules' => 'min_length[8]|max_length[255]|regex_match[/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/]',
                'errors' => [
                    'min_length' => 'La {field} debe tener al menos {param} caracteres',
                    'max_length' => 'La {field} no puede exceder {param} caracteres',
                    'regex_match' => 'La {field} debe contener al menos una mayúscula, una minúscula y un número'
                ]
            ];
            $rules['password_confirm'] = [
                'label' => 'Confirmar Contraseña',
                'rules' => 'matches[password]',
                'errors' => [
                    'matches' => 'Las contraseñas no coinciden'
                ]
            ];
        }

        $validation->setRules($rules);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->with('errors', $validation->getErrors())->withInput();
        }

        // Verificar que el email no esté en uso por otro usuario
        $email = $this->request->getPost('email');
        if ($this->userModel->isEmailTaken($email, $id)) {
            return redirect()->back()->with('error', 'El email ya está registrado por otro usuario')->withInput();
        }

        $data = [
            'name' => trim($this->request->getPost('name')),
            'email' => trim(strtolower($this->request->getPost('email')))
        ];

        if ($this->request->getPost('password')) {
            $data['password'] = $this->request->getPost('password');
        }

        if ($this->userModel->updateUser($id, $data)) {
            return redirect()->to(admin_url('users'))->with('success', 'Usuario actualizado correctamente');
        } else {
            return redirect()->back()->with('error', 'Error al actualizar el usuario')->withInput();
        }
    }

    /**
     * Eliminar usuario
     */
    public function delete($id)
    {
        if ($this->userModel->deleteUser($id)) {
            return redirect()->to(admin_url('users'))->with('success', 'Usuario eliminado correctamente');
        } else {
            return redirect()->to(admin_url('users'))->with('error', 'No se puede eliminar el último administrador');
        }
    }
}

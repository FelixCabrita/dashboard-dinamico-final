<?php

namespace App\Controllers;

use App\Models\UserModel;

/**
 * Auth Controller
 * Maneja autenticación de usuarios admin
 */
class Auth extends BaseController
{
    /**
     * Mostrar formulario de login
     */
    public function login()
    {
        // Si ya está autenticado, redirigir al panel
        if (session()->has('user_id')) {
            return redirect()->to(admin_url());
        }

        return view('auth/login');
    }

    /**
     * Procesar autenticación con rate limiting
     */
    public function authenticate()
    {
        $userModel = new UserModel();

        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $remember = $this->request->getPost('remember');

        // Rate limiting: verificar intentos fallidos
        $loginAttempts = session()->get('login_attempts') ?? 0;
        $lastAttemptTime = session()->get('last_attempt_time') ?? 0;
        $currentTime = time();

        // Bloquear si hay más de 5 intentos fallidos en los últimos 15 minutos
        if ($loginAttempts >= 5) {
            $timeSinceLastAttempt = $currentTime - $lastAttemptTime;
            $lockoutTime = 15 * 60; // 15 minutos en segundos

            if ($timeSinceLastAttempt < $lockoutTime) {
                $minutesRemaining = ceil(($lockoutTime - $timeSinceLastAttempt) / 60);
                return redirect()
                    ->to(admin_url('login'))
                    ->with('error', "Demasiados intentos fallidos. Intente nuevamente en {$minutesRemaining} minuto(s)")
                    ->withInput();
            } else {
                // Reiniciar contador después del tiempo de bloqueo
                session()->remove('login_attempts');
                session()->remove('last_attempt_time');
                $loginAttempts = 0;
            }
        }

        // Validar campos
        $validation = \Config\Services::validation();
        $validation->setRules([
            'email' => 'required|valid_email',
            'password' => 'required|min_length[6]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()
                ->to(admin_url('login'))
                ->with('error', 'Por favor, ingrese un email y contraseña válidos')
                ->withInput();
        }

        // Intentar autenticar
        $user = $userModel->authenticate($email, $password);

        if (!$user) {
            // Incrementar contador de intentos fallidos
            $loginAttempts++;
            session()->set('login_attempts', $loginAttempts);
            session()->set('last_attempt_time', $currentTime);

            // Mensaje diferente según intentos
            $remainingAttempts = 5 - $loginAttempts;
            if ($remainingAttempts > 0) {
                $errorMsg = "Credenciales incorrectas. Tiene {$remainingAttempts} intento(s) restante(s)";
            } else {
                $errorMsg = "Cuenta bloqueada temporalmente por seguridad. Intente nuevamente en 15 minutos";
            }

            return redirect()
                ->to(admin_url('login'))
                ->with('error', $errorMsg)
                ->withInput();
        }

        // Login exitoso: limpiar intentos fallidos
        session()->remove('login_attempts');
        session()->remove('last_attempt_time');

        // Crear sesión
        session()->set([
            'user_id' => $user['id'],
            'user_email' => $user['email'],
            'user_name' => $user['name'],
            'user_role' => $user['role'],
            'logged_in' => true
        ]);

        // Si marcó "recordarme", extender duración de sesión
        if ($remember) {
            session()->setTempdata('remember', true, 60 * 60 * 24 * 30); // 30 días
        }

        return redirect()
            ->to(admin_url())
            ->with('success', 'Bienvenido, ' . $user['name']);
    }

    /**
     * Cerrar sesión
     */
    public function logout()
    {
        session()->destroy();
        return redirect()
            ->to(admin_url('login'))
            ->with('success', 'Sesión cerrada correctamente');
    }
}

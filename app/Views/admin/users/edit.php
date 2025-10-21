<?= view('layouts/header_admin') ?>

<div class="page-header">
    <h2>Editar Usuario</h2>
    <a href="<?= base_url('/admin-panel-bmp/users') ?>" class="btn btn-secondary">
        ← Volver
    </a>
</div>

<div class="form-container">
    <form action="<?= base_url('/admin-panel-bmp/users/update/' . $user['id']) ?>" method="POST" class="user-form" id="editUserForm">
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="name">Nombre Completo *</label>
            <input
                type="text"
                id="name"
                name="name"
                class="form-control"
                placeholder="Juan Pérez"
                required
                value="<?= old('name', $user['name']) ?>"
                maxlength="100"
            >
            <small class="validation-error" id="name-error"></small>
            <small class="field-hint">Mínimo 3 caracteres, solo letras, números y espacios</small>
        </div>

        <div class="form-group">
            <label for="email">Email *</label>
            <input
                type="email"
                id="email"
                name="email"
                class="form-control"
                placeholder="juan@example.com"
                required
                value="<?= old('email', $user['email']) ?>"
                maxlength="255"
            >
            <small class="validation-error" id="email-error"></small>
        </div>

        <div class="form-group">
            <label for="password">Nueva Contraseña (opcional)</label>
            <input
                type="password"
                id="password"
                name="password"
                class="form-control"
                placeholder="Dejar en blanco para no cambiar"
                minlength="8"
                maxlength="255"
            >
            <small class="validation-error" id="password-error"></small>
            <small class="field-hint">Si desea cambiarla, debe contener mayúsculas, minúsculas y números</small>
        </div>

        <div class="form-group">
            <label for="password_confirm">Confirmar Nueva Contraseña</label>
            <input
                type="password"
                id="password_confirm"
                name="password_confirm"
                class="form-control"
                placeholder="Solo si se cambia la contraseña"
                minlength="8"
                maxlength="255"
            >
            <small class="validation-error" id="password_confirm-error"></small>
        </div>

        <div class="form-group">
            <label>Rol</label>
            <div class="form-control-static">
                <span class="badge badge-admin"><?= esc($user['role']) ?></span>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
            <a href="<?= base_url('/admin-panel-bmp/users') ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
// Validación en tiempo real para edición
(function() {
    const form = document.getElementById('editUserForm');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const passwordConfirmInput = document.getElementById('password_confirm');

    // Función para mostrar error
    function showError(input, message) {
        const errorElement = document.getElementById(input.id + '-error');
        errorElement.textContent = message;
        errorElement.style.display = 'block';
        input.classList.add('input-error');
    }

    // Función para limpiar error
    function clearError(input) {
        const errorElement = document.getElementById(input.id + '-error');
        errorElement.textContent = '';
        errorElement.style.display = 'none';
        input.classList.remove('input-error');
    }

    // Validar nombre
    nameInput.addEventListener('blur', function() {
        const value = this.value.trim();
        clearError(this);

        if (value.length === 0) {
            showError(this, 'El nombre es obligatorio');
        } else if (value.length < 3) {
            showError(this, 'El nombre debe tener al menos 3 caracteres');
        } else if (value.length > 100) {
            showError(this, 'El nombre no puede exceder 100 caracteres');
        } else if (!/^[a-zA-Z0-9\s\u00C0-\u017F]+$/.test(value)) {
            showError(this, 'El nombre solo puede contener letras, números y espacios');
        }
    });

    // Validar email
    emailInput.addEventListener('blur', function() {
        const value = this.value.trim();
        clearError(this);

        if (value.length === 0) {
            showError(this, 'El email es obligatorio');
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            showError(this, 'Debe ingresar un email válido');
        } else if (value.length > 255) {
            showError(this, 'El email no puede exceder 255 caracteres');
        }
    });

    // Validar contraseña (solo si se proporciona)
    passwordInput.addEventListener('blur', function() {
        const value = this.value;
        clearError(this);

        // La contraseña es opcional en edición
        if (value.length > 0) {
            if (value.length < 8) {
                showError(this, 'La contraseña debe tener al menos 8 caracteres');
            } else if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/.test(value)) {
                showError(this, 'La contraseña debe contener al menos una mayúscula, una minúscula y un número');
            }
        }
    });

    // Validar confirmación de contraseña (solo si se proporciona contraseña)
    passwordConfirmInput.addEventListener('blur', function() {
        const value = this.value;
        const passwordValue = passwordInput.value;
        clearError(this);

        // Solo validar si se ingresó una contraseña
        if (passwordValue.length > 0) {
            if (value.length === 0) {
                showError(this, 'Debe confirmar la contraseña');
            } else if (value !== passwordValue) {
                showError(this, 'Las contraseñas no coinciden');
            }
        }
    });

    // También validar cuando se escribe en la contraseña principal
    passwordInput.addEventListener('input', function() {
        if (passwordConfirmInput.value.length > 0) {
            passwordConfirmInput.dispatchEvent(new Event('blur'));
        }
    });

    // Validar antes de enviar
    form.addEventListener('submit', function(e) {
        // Disparar validación en todos los campos
        nameInput.dispatchEvent(new Event('blur'));
        emailInput.dispatchEvent(new Event('blur'));

        // Solo validar contraseña si se proporcionó
        if (passwordInput.value.length > 0) {
            passwordInput.dispatchEvent(new Event('blur'));
            passwordConfirmInput.dispatchEvent(new Event('blur'));
        }

        // Verificar si hay errores
        const errors = form.querySelectorAll('.validation-error');
        let hasErrors = false;
        errors.forEach(error => {
            if (error.textContent.trim() !== '') {
                hasErrors = true;
            }
        });

        if (hasErrors) {
            e.preventDefault();
            alert('Por favor, corrija los errores en el formulario antes de enviar');
        }
    });
})();
</script>

<?= view('layouts/footer_admin') ?>

/**
 * Dashboard Ambiental - Admin Panel JavaScript
 * Bosque Modelo Palencia
 */

document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts después de 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // Confirmar eliminación de usuarios
    const deleteForms = document.querySelectorAll('form[onsubmit*="confirm"]');
    deleteForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('¿Estás seguro de eliminar este usuario? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        });
    });

    // Validación de contraseñas en formularios
    const passwordInputs = document.querySelectorAll('input[name="password"]');
    const confirmInputs = document.querySelectorAll('input[name="password_confirm"]');

    if (passwordInputs.length > 0 && confirmInputs.length > 0) {
        confirmInputs[0].addEventListener('blur', function() {
            if (this.value && passwordInputs[0].value !== this.value) {
                this.setCustomValidity('Las contraseñas no coinciden');
                this.reportValidity();
            } else {
                this.setCustomValidity('');
            }
        });

        passwordInputs[0].addEventListener('input', function() {
            if (confirmInputs[0].value) {
                if (this.value !== confirmInputs[0].value) {
                    confirmInputs[0].setCustomValidity('Las contraseñas no coinciden');
                } else {
                    confirmInputs[0].setCustomValidity('');
                }
            }
        });
    }
});

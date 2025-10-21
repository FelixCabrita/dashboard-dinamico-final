<?= view('layouts/header_admin') ?>

<div class="page-header">
    <h2>Gestión de Usuarios</h2>
    <a href="<?= base_url('/admin-panel-bmp/users/create') ?>" class="btn btn-primary">
        + Crear Usuario
    </a>
</div>

<!-- Formulario de búsqueda y filtros -->
<div class="filters-container">
    <form action="<?= base_url('/admin-panel-bmp/users') ?>" method="GET" class="filters-form">
        <div class="filter-group">
            <input
                type="text"
                name="search"
                class="form-control"
                placeholder="Buscar por nombre o email..."
                value="<?= esc($search) ?>"
            >
        </div>

        <div class="filter-group">
            <select name="order_by" class="form-control">
                <option value="created_at" <?= $orderBy === 'created_at' ? 'selected' : '' ?>>Fecha de creación</option>
                <option value="name" <?= $orderBy === 'name' ? 'selected' : '' ?>>Nombre</option>
                <option value="email" <?= $orderBy === 'email' ? 'selected' : '' ?>>Email</option>
            </select>
        </div>

        <div class="filter-group">
            <select name="order_dir" class="form-control">
                <option value="desc" <?= $orderDir === 'desc' ? 'selected' : '' ?>>Descendente</option>
                <option value="asc" <?= $orderDir === 'asc' ? 'selected' : '' ?>>Ascendente</option>
            </select>
        </div>

        <div class="filter-actions">
            <button type="submit" class="btn btn-secondary">Filtrar</button>
            <a href="<?= base_url('/admin-panel-bmp/users') ?>" class="btn btn-text">Limpiar</a>
        </div>
    </form>
</div>

<!-- Información de resultados -->
<?php if (!empty($search)): ?>
    <div class="results-info">
        Se encontraron <strong><?= $pagination['total'] ?></strong> resultados para "<strong><?= esc($search) ?></strong>"
    </div>
<?php endif; ?>

<!-- Tabla de usuarios -->
<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th>Rol</th>
                <th>Fecha Creación</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="6" class="text-center">
                        <?php if (!empty($search)): ?>
                            No se encontraron usuarios que coincidan con la búsqueda
                        <?php else: ?>
                            No hay usuarios registrados
                        <?php endif; ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= esc($user['id']) ?></td>
                        <td><?= esc($user['name']) ?></td>
                        <td><?= esc($user['email']) ?></td>
                        <td><span class="badge badge-admin"><?= esc($user['role']) ?></span></td>
                        <td><?= esc($user['created_at']) ?></td>
                        <td class="actions">
                            <a href="<?= base_url('/admin-panel-bmp/users/edit/' . $user['id']) ?>" class="btn btn-sm btn-edit">
                                Editar
                            </a>
                            <button
                                type="button"
                                class="btn btn-sm btn-delete"
                                onclick="confirmDelete(<?= $user['id'] ?>, '<?= esc($user['name']) ?>')"
                            >
                                Eliminar
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Paginación -->
<?php if ($pagination['totalPages'] > 1): ?>
    <div class="pagination-container">
        <div class="pagination-info">
            Mostrando <?= count($users) ?> de <?= $pagination['total'] ?> usuarios
        </div>

        <div class="pagination">
            <?php
            $currentPage = $pagination['page'];
            $totalPages = $pagination['totalPages'];

            // Construir query string manteniendo búsqueda y filtros
            $queryParams = [];
            if (!empty($search)) $queryParams['search'] = $search;
            if ($orderBy !== 'created_at') $queryParams['order_by'] = $orderBy;
            if ($orderDir !== 'desc') $queryParams['order_dir'] = $orderDir;
            $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
            ?>

            <!-- Botón Anterior -->
            <?php if ($currentPage > 1): ?>
                <a href="<?= base_url('/admin-panel-bmp/users?page=' . ($currentPage - 1) . $queryString) ?>" class="pagination-btn">
                    « Anterior
                </a>
            <?php else: ?>
                <span class="pagination-btn pagination-disabled">« Anterior</span>
            <?php endif; ?>

            <!-- Números de página -->
            <?php
            $start = max(1, $currentPage - 2);
            $end = min($totalPages, $currentPage + 2);

            for ($i = $start; $i <= $end; $i++):
            ?>
                <?php if ($i == $currentPage): ?>
                    <span class="pagination-btn pagination-active"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= base_url('/admin-panel-bmp/users?page=' . $i . $queryString) ?>" class="pagination-btn">
                        <?= $i ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>

            <!-- Botón Siguiente -->
            <?php if ($currentPage < $totalPages): ?>
                <a href="<?= base_url('/admin-panel-bmp/users?page=' . ($currentPage + 1) . $queryString) ?>" class="pagination-btn">
                    Siguiente »
                </a>
            <?php else: ?>
                <span class="pagination-btn pagination-disabled">Siguiente »</span>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Modal de confirmación de eliminación -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirmar Eliminación</h3>
            <button type="button" class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>¿Estás seguro de que deseas eliminar al usuario <strong id="deleteUserName"></strong>?</p>
            <p class="warning-text">Esta acción no se puede deshacer.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancelar</button>
            <form id="deleteForm" method="POST" style="display: inline;">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-delete">Eliminar Usuario</button>
            </form>
        </div>
    </div>
</div>

<script>
// Función para abrir modal de confirmación
function confirmDelete(userId, userName) {
    document.getElementById('deleteUserName').textContent = userName;
    document.getElementById('deleteForm').action = '<?= base_url('/admin-panel-bmp/users/delete/') ?>' + userId;
    document.getElementById('deleteModal').style.display = 'flex';
}

// Función para cerrar modal
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Cerrar modal al hacer clic fuera de él
window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target == modal) {
        closeDeleteModal();
    }
}

// Cerrar modal con tecla ESC
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeDeleteModal();
    }
});
</script>

<?= view('layouts/footer_admin') ?>

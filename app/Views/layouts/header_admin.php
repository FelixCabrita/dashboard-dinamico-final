<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Panel Admin') ?> - Bosque Modelo Palencia</title>
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>">
</head>
<body class="admin-page">
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <img src="<?= base_url('logos/BMP.png') ?>" alt="BMP" class="sidebar-logo">
                <h2>Panel Admin</h2>
            </div>

            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="<?= base_url('/admin-panel-bmp') ?>" class="<?= uri_string() == 'admin-panel-bmp' ? 'active' : '' ?>">
                            <span class="icon">📊</span>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= base_url('/admin-panel-bmp/users') ?>" class="<?= strpos(uri_string(), 'users') !== false ? 'active' : '' ?>">
                            <span class="icon">👥</span>
                            <span>Usuarios</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= base_url('/admin-panel-bmp/data-manager') ?>" class="<?= strpos(uri_string(), 'data-manager') !== false ? 'active' : '' ?>">
                            <span class="icon">💾</span>
                            <span>Gestión de Datos</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= base_url('/admin-panel-bmp/components') ?>" class="<?= strpos(uri_string(), 'components') !== false ? 'active' : '' ?>">
                            <span class="icon">🧩</span>
                            <span>Componentes</span>
                        </a>
                    </li>
                    <li class="nav-section-title">Módulos Futuros</li>
                    <li class="disabled">
                        <span class="icon">📱</span>
                        <span>Dashboards</span>
                        <small>(Hito 6)</small>
                    </li>
                </ul>
            </nav>

            <div class="sidebar-footer">
                <a href="<?= base_url('/admin-panel-bmp/logout') ?>" class="btn-logout">
                    <span class="icon">🚪</span>
                    <span>Cerrar Sesión</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="admin-content">
            <!-- Top Bar -->
            <header class="admin-topbar">
                <div class="topbar-left">
                    <h1><?= esc($title ?? 'Panel Administrativo') ?></h1>
                </div>
                <div class="topbar-right">
                    <div class="user-info">
                        <span class="user-name"><?= esc(session()->get('user_name')) ?></span>
                        <span class="user-email"><?= esc(session()->get('user_email')) ?></span>
                    </div>
                </div>
            </header>

            <!-- Flash Messages -->
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success">
                    <?= session()->getFlashdata('success') ?>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-error">
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('errors')): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach (session()->getFlashdata('errors') as $error): ?>
                            <li><?= esc($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Page Content -->
            <main class="admin-main">

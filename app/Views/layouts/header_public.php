<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title ?? 'Bosque Modelo Palencia') ?></title>
    <meta name="description" content="Dashboard Ambiental - Bosque Modelo Palencia. Visualización de datos forestales y ambientales de Castilla y León.">
    <meta name="keywords" content="bosque, forestal, ambiental, palencia, castilla y león, datos ambientales">

    <!-- Estilos -->
    <link rel="stylesheet" href="<?= base_url('css/landing.css') ?>">
    <link rel="stylesheet" href="<?= base_url('css/responsive.css') ?>">

    <!-- Favicon (futuro) -->
    <link rel="icon" type="image/png" href="<?= base_url('favicon.png') ?>">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <img src="<?= base_url('logos/BMP.png') ?>" alt="Bosque Modelo Palencia" class="logo-img">
                </div>
                <nav class="main-nav">
                    <ul>
                        <li><a href="<?= base_url('/') ?>">Inicio</a></li>
                        <li><a href="<?= base_url('/dashboard') ?>">Dashboard</a></li>
                        <li><a href="#about">Acerca de</a></li>
                        <li><a href="#contact">Contacto</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

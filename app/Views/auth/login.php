<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Panel Administrativo</title>
    <link rel="stylesheet" href="<?= base_url('css/admin.css') ?>">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <img src="<?= base_url('logos/BMP.png') ?>" alt="Bosque Modelo Palencia" class="login-logo">
                <h1>Panel Administrativo</h1>
                <p>Bosque Modelo Palencia</p>
            </div>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-error">
                    <?= session()->getFlashdata('error') ?>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success">
                    <?= session()->getFlashdata('success') ?>
                </div>
            <?php endif; ?>

            <form action="<?= admin_url('authenticate') ?>" method="POST" class="login-form">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control"
                        placeholder="tu@email.com"
                        required
                        autofocus
                        value="<?= old('email') ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="••••••••"
                        required
                    >
                </div>

                <div class="form-group-checkbox">
                    <input type="checkbox" id="remember" name="remember" value="1">
                    <label for="remember">Recordarme</label>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Iniciar Sesión
                </button>
            </form>

            <div class="login-footer">
                <p>&copy; <?= date('Y') ?> Bosque Modelo Palencia</p>
            </div>
        </div>
    </div>
</body>
</html>

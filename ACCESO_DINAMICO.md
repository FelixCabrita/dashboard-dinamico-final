# ✅ URL DINÁMICA CONFIGURADA

**Dashboard Ambiental - Bosque Modelo Palencia**

---

## PROBLEMA RESUELTO

Ahora puedes acceder al proyecto desde la raíz sin necesidad de agregar `/public/` manualmente.

---

## CÓMO FUNCIONA

### Configuración implementada:

1. **`.htaccess` en la raíz** - Redirige automáticamente todo el tráfico a `public/`
2. **`index.php` en la raíz** - Fallback si mod_rewrite no está habilitado
3. **`baseURL` vacío** - CodeIgniter detecta automáticamente la URL base
4. **Sin hardcodear rutas** - El sistema se adapta a cualquier ubicación

---

## NUEVAS URLs DE ACCESO

### ✅ Landing Page Pública
```
http://localhost/wp/dashboard-dinamico-final/
```
(Ya NO necesitas agregar `/public/`)

### ✅ Panel Administrativo (URL Oculta)
```
http://localhost/wp/dashboard-dinamico-final/admin-panel-bmp/login
```

**Credenciales:**
- Email: `admin@bosquepalencia.es`
- Contraseña: `password`

---

## PRUEBA EL SISTEMA

1. Abre tu navegador
2. Ve a: `http://localhost/wp/dashboard-dinamico-final/`
3. Deberías ver la landing page directamente
4. Para el panel admin: `http://localhost/wp/dashboard-dinamico-final/admin-panel-bmp/login`

---

## VENTAJAS DE ESTA CONFIGURACIÓN

✅ **URLs limpias** - Sin `/public/` visible
✅ **Dinámico** - No importa dónde esté el proyecto
✅ **Sin hardcodear** - Funciona en cualquier servidor
✅ **Portátil** - Mueve el proyecto sin reconfigurar
✅ **Profesional** - URLs amigables para el usuario

---

## ARCHIVOS MODIFICADOS

- ✅ `/.htaccess` (nuevo) - Redirige a public/
- ✅ `/index.php` (nuevo) - Fallback de redirección
- ✅ `/app/Config/App.php` - baseURL vacío y indexPage vacío
- ✅ `/.env` - baseURL comentado (auto-detecta)

---

## SI TIENES PROBLEMAS

### Problema: "404 Not Found"
**Solución:** Verifica que `mod_rewrite` esté habilitado en Apache (XAMPP)

1. Abre `C:\xampp\apache\conf\httpd.conf`
2. Busca: `#LoadModule rewrite_module modules/mod_rewrite.so`
3. Quita el `#` al inicio de la línea
4. Reinicia Apache

### Problema: "Página en blanco"
**Solución:** Verifica permisos en `writable/`

```bash
# En Windows (desde CMD con permisos de administrador)
icacls writable /grant Everyone:(OI)(CI)F /T
```

### Problema: "CSS no carga"
**Solución:** Verifica que los archivos existan en `public/css/`

---

## NOTAS TÉCNICAS

### Detección automática de baseURL
CodeIgniter 4 detecta automáticamente la URL base usando:
- `$_SERVER['HTTP_HOST']`
- `$_SERVER['SCRIPT_NAME']`
- `$_SERVER['REQUEST_URI']`

Esto significa que el proyecto funcionará en:
- `http://localhost/wp/dashboard-dinamico-final/` ✅
- `http://127.0.0.1/wp/dashboard-dinamico-final/` ✅
- `http://mi-dominio.com/` ✅
- Cualquier ubicación sin reconfigurar ✅

---

## ¿CÓMO SE IMPLEMENTÓ?

### 1. `.htaccess` en raíz
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_URI} !^/public/
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

### 2. `index.php` en raíz (fallback)
```php
$uri = $_SERVER['REQUEST_URI'];
$newPath = rtrim($basePath, '/') . '/public' . str_replace($basePath, '', $uri);
header('Location: ' . $newPath);
```

### 3. `App.php`
```php
public string $baseURL = '';  // Auto-detecta
public string $indexPage = ''; // URLs limpias
```

---

**¡Listo! Ahora puedes acceder desde la raíz del proyecto.** 🎉

# 🔄 REFACTORIZACIÓN CKAN: GitHub Actions → Render

**Fecha:** 21 de Octubre, 2025
**Estado:** ✅ Backend completado (Fases 1-6)
**Pendiente:** Actualización de documentación (Fases 7-9)

---

## 📋 CAMBIOS IMPLEMENTADOS

### FASE 1: Limpieza de GitHub Actions ✅

**Archivos eliminados:**
- `.github/workflows/ckan-sync-weekly.yml`
- `.github/workflows/ckan-sync-manual.yml`
- `.github/workflows/test-ckan-sync.yml`
- `docs/GITHUB_ACTIONS_SETUP.md`

**Razón:** La sincronización SHP ahora se hará mediante microservicio Render, no GitHub Actions.

---

### FASE 2: ShpProcessor - Importación a current.db ✅

**Archivo modificado:** `app/Libraries/ShpProcessor.php`

**Nuevo método:**
```php
public function importToMainDatabase(string $shpPath, string $resourceName): array
```

**Funcionalidad:**
- Convierte SHP a SQLite temporal con ogr2ogr
- Lee datos del SQLite temporal
- Importa datos directamente a `data/db/current.db`
- Crea tabla con prefijo `ckan_` (ej: `ckan_miradores`)
- Crea índices automáticos
- Retorna estadísticas de importación

**Beneficio:** Las tablas CKAN aparecen automáticamente en `/api/data/tables` sin modificar QueryBuilder.

---

### FASE 3: Endpoint de Importación ✅

**Archivo modificado:** `app/Controllers/API/CkanController.php`

**Nuevo endpoint:**
```
POST /api/admin/ckan/import
```

**Función:**
- Recibe datos procesados desde microservicio Render
- Valida token de seguridad (`callbackToken`)
- Crea tabla `ckan_{nombre}` en `current.db`
- Inserta datos en bulk
- Actualiza `ckan_resource_sync` (tracking)
- Envía notificación al admin

**Seguridad:**
- Token secreto compartido con Render
- Validación de origen
- Logging de intentos inválidos

---

### FASE 4: Interfaz de Sincronización Admin ✅

**Archivos creados:**
- `app/Controllers/Admin/CkanSync.php` (280 líneas)
- `app/Views/admin/ckan/sync.php` (240 líneas)

**URL:** `/admin-panel-bmp/ckan/sync`

**Funcionalidad:**
1. Lista todos los recursos configurados
2. Muestra estado de última sincronización
3. Botón "Sincronizar" por recurso
4. Loading spinner durante procesamiento
5. Notificaciones de éxito/error
6. Polling cada 10s para verificar estado
7. Recarga automática cuando termina

**Endpoints del controlador:**
- `GET /admin-panel-bmp/ckan/sync` - Vista principal
- `POST /admin-panel-bmp/ckan/sync/trigger` - Iniciar sincronización
- `GET /admin-panel-bmp/ckan/sync/status/:resource` - Consultar estado

---

### FASE 5: Configuraciones Actualizadas ✅

#### `app/Config/Ckan.php`

**Nuevas propiedades:**
```php
public string $renderServiceUrl = '';  // URL del microservicio Render
public string $callbackToken = '...';  // Token de seguridad para callback
```

**Token generado:** `7f8a9b6c5d4e3f2a1b0c9d8e7f6a5b4c3d2e1f0a9b8c7d6e5f4a3b2c1d0e9f8a`

#### `app/Config/CkanSync.php`

**Cambio principal:**
```php
// ANTES: priorityResources (array simple)
public array $priorityResources = ['miradores' => 'id', ...];

// DESPUÉS: availableResources (array estructurado)
public array $availableResources = [
    'miradores' => [
        'id' => 'resource-id',
        'name' => 'Miradores',
        'description' => 'Puntos de observación'
    ],
    ...
];
```

**Razón:** Ahora el admin puede sincronizar cualquier recurso bajo demanda, no solo prioritarios.

---

### FASE 6: Rutas Actualizadas ✅

**Archivo modificado:** `app/Config/Routes.php`

**Rutas agregadas:**
```php
// Callback desde Render
$routes->post('api/admin/ckan/import', 'Api\CkanController::import');

// Admin CKAN Sync
$routes->get('ckan/sync', 'Admin\CkanSync::index');
$routes->post('ckan/sync/trigger', 'Admin\CkanSync::triggerSync');
$routes->get('ckan/sync/status/(:segment)', 'Admin\CkanSync::getStatus/$1');
```

---

## 🔄 FLUJO DE SINCRONIZACIÓN (Nueva Arquitectura)

```
1. Admin hace click "Sincronizar" en /admin-panel-bmp/ckan/sync
   ↓
2. Panel admin hace POST a Render:
   POST https://tu-servicio.onrender.com/api/sync-shp
   Body: {
     resource_id: "...",
     resource_name: "miradores",
     callback_url: "https://tu-panel.com/api/admin/ckan/import",
     callback_token: "secret-token"
   }
   ↓
3. Render responde inmediatamente:
   { success: true, message: "Procesamiento iniciado" }
   ↓
4. Admin muestra: "Sincronizando... (2-3 minutos)"
   ↓
5. Render (en background):
   - Descarga SHP desde CKAN
   - Convierte con ogr2ogr
   - Genera JSON
   ↓
6. Render hace POST al callback:
   POST https://tu-panel.com/api/admin/ckan/import
   Body: {
     token: "secret-token",
     table_name: "ckan_miradores",
     data: [...],
     metadata: {...}
   }
   ↓
7. Panel admin:
   - Valida token
   - Crea tabla ckan_miradores en current.db
   - Inserta datos
   - Actualiza ckan_resource_sync
   - Crea notificación
   ↓
8. Admin (polling cada 10s):
   - Detecta status = 'success'
   - Muestra: "✅ Sincronización completada"
   - Recarga página
```

---

## 📂 ARCHIVOS MODIFICADOS/CREADOS

### Eliminados (4)
- `.github/workflows/ckan-sync-weekly.yml`
- `.github/workflows/ckan-sync-manual.yml`
- `.github/workflows/test-ckan-sync.yml`
- `docs/GITHUB_ACTIONS_SETUP.md`

### Modificados (5)
- `app/Libraries/ShpProcessor.php` (+148 líneas)
- `app/Controllers/API/CkanController.php` (+276 líneas)
- `app/Config/Ckan.php` (+23 líneas)
- `app/Config/CkanSync.php` (~30 líneas modificadas)
- `app/Config/Routes.php` (+5 líneas)

### Creados (3)
- `app/Controllers/Admin/CkanSync.php` (280 líneas)
- `app/Views/admin/ckan/sync.php` (240 líneas)
- `REFACTORIZACION_CKAN_RENDER.md` (este documento)

**Total:**
- ~1,000 líneas de código nuevo
- ~500 líneas modificadas

---

## ⚠️ TAREAS PENDIENTES

### FASE 7: Actualizar Documentación Existente

Archivos a actualizar (reemplazar "GitHub Actions" por "Render"):
1. `docs/IMPLEMENTACION_CKAN_COMPLETA.md`
2. `docs/POST_IMPLEMENTACION_CKAN.md`
3. `docs/SISTEMA_SINCRONIZACION_SHP.md`
4. `docs/ESTADO_ACTUAL_PROYECTO.md`

### FASE 8: Nueva Documentación

Crear:
- `docs/ARQUITECTURA_CKAN_RENDER.md` - Documentación completa de la nueva arquitectura

### FASE 9: Actualizar Hitos

Actualizar:
- `docs/avances/HITO_4_COMPLETADO.md` - Sección CKAN
- `docs/avances/HITO_5_COMPLETADO.md` - Sección CKAN Sync

---

## 🚀 PRÓXIMOS PASOS

### 1. Completar Documentación (Fases 7-9)
Actualizar toda la documentación para reflejar la nueva arquitectura.

### 2. Crear Microservicio Render
Implementar el servicio PHP con GDAL que:
- Reciba requests de sincronización
- Descargue SHP desde CKAN
- Procese con ogr2ogr
- Envíe datos procesados al callback

### 3. Deploy en Render
- Crear cuenta en Render.com
- Conectar repositorio del microservicio
- Configurar variables de entorno
- Obtener URL del servicio

### 4. Configurar Panel Admin
Actualizar `app/Config/Ckan.php`:
```php
public string $renderServiceUrl = 'https://tu-servicio.onrender.com';
```

### 5. Probar Flujo Completo
1. Click en "Sincronizar" desde admin
2. Verificar request a Render
3. Verificar callback al panel
4. Verificar tabla creada en current.db
5. Verificar notificación

---

## 📊 ESTADO DEL PROYECTO

**Backend CKAN:** ✅ 100% Completado
- ShpProcessor con importación a current.db
- Endpoint de importación con validación
- Interfaz admin de sincronización
- Configuraciones actualizadas
- Rutas configuradas

**Microservicio Render:** ⏳ Pendiente de implementación

**Documentación:** ⏳ Pendiente de actualización (Fases 7-9)

---

## 🎯 COMMIT SUGERIDO

**Título:**
```
refactor: Migración de sincronización CKAN de GitHub Actions a arquitectura Render
```

**Descripción:**
```
Refactoriza el sistema de sincronización SHP para usar microservicio Render
en lugar de GitHub Actions. Los datos SHP ahora se importan directamente a
current.db con prefijo ckan_* en lugar de archivos SQLite separados.

CAMBIOS PRINCIPALES:
- Eliminados workflows de GitHub Actions (3 archivos)
- ShpProcessor: nuevo método importToMainDatabase()
- CkanController: endpoint /api/admin/ckan/import para callback
- Admin: nueva interfaz de sincronización en /admin-panel-bmp/ckan/sync
- Config: nuevas propiedades renderServiceUrl y callbackToken
- Routes: rutas para admin y callback

ARQUITECTURA:
Admin → Render (procesa SHP) → Callback al panel → Inserta en current.db

BENEFICIOS:
- Tablas CKAN aparecen automáticamente en /api/data/tables
- No requiere GDAL en servidor de producción (solo en Render)
- Sincronización bajo demanda desde admin
- Notificaciones en tiempo real

PENDIENTE:
- Implementar microservicio Render
- Actualizar documentación (Fases 7-9)
```

---

**Preparado por:** Claude Code
**Fecha:** 21 de Octubre, 2025

# ARQUITECTURA CKAN - RENDER + CALLBACK

**Proyecto:** Dashboard Dinámico Ambiental - Bosque Modelo Palencia
**Fecha:** 21 de Octubre de 2025
**Versión:** 1.0.0
**Arquitectura:** Microservicio Render con Callback Asíncrono

---

## 🎯 OBJETIVO

Procesar archivos SHP de CKAN (194 recursos geoespaciales) mediante un microservicio en Render.com con GDAL, importándolos directamente a `current.db` para integración automática con el sistema QueryBuilder y componentes del dashboard.

---

## 🏗️ ARQUITECTURA GENERAL

### Diagrama de Flujo Completo

```
┌────────────────────────────────────────────────────────────────┐
│                    PANEL ADMIN (CodeIgniter 4)                 │
│               http://panel-admin.bosquepalencia.es             │
└────────────────────────────────────────────────────────────────┘
                              │
                              │ 1. Admin hace click "Sincronizar"
                              ▼
┌────────────────────────────────────────────────────────────────┐
│     POST /admin-panel-bmp/ckan/sync/trigger                    │
│     Admin\CkanSync::triggerSync()                              │
│                                                                 │
│     Payload:                                                    │
│     - resource_id: "c20d3a73-..."                              │
│     - resource_name: "miradores"                               │
│     - callback_url: "https://panel.../api/admin/ckan/import"  │
│     - callback_token: "7f8a9b6c5d4e..."                        │
└────────────────────────────────────────────────────────────────┘
                              │
                              │ 2. HTTP POST Request
                              ▼
┌────────────────────────────────────────────────────────────────┐
│           MICROSERVICIO RENDER (PHP + GDAL)                    │
│              https://ckan-processor.onrender.com               │
│                                                                 │
│     POST /api/sync-shp                                          │
│     Respuesta: 202 Accepted                                     │
│     { "success": true, "message": "Procesamiento iniciado" }   │
└────────────────────────────────────────────────────────────────┘
                              │
                              │ 3. Admin UI muestra "Sincronizando..."
                              │    Polling cada 10s a GET /status/:resource
                              ▼
┌────────────────────────────────────────────────────────────────┐
│         PROCESAMIENTO EN RENDER (Background - 2-3 min)         │
│                                                                 │
│     4. Descarga SHP desde CKAN                                  │
│        GET http://ckan.dipupalencia.../resource/download/...   │
│                                                                 │
│     5. Descomprime ZIP                                          │
│        miradores.zip → [miradores.shp, .shx, .dbf, .prj]       │
│                                                                 │
│     6. Convierte con ogr2ogr                                    │
│        ogr2ogr -f SQLite temp.db miradores.shp                 │
│                                                                 │
│     7. Lee datos de SQLite temporal                             │
│        SELECT * FROM miradores → JSON array                     │
│                                                                 │
│     8. Genera payload para callback                             │
│        {                                                        │
│          token: "7f8a9b6c5d4e...",                             │
│          table_name: "ckan_miradores",                         │
│          data: [{lat: 42.1, lon: -4.5, ...}, ...],            │
│          metadata: {rows: 150, size: 1024}                     │
│        }                                                        │
└────────────────────────────────────────────────────────────────┘
                              │
                              │ 9. HTTP POST Callback
                              ▼
┌────────────────────────────────────────────────────────────────┐
│          PANEL ADMIN - CALLBACK ENDPOINT                        │
│          POST /api/admin/ckan/import                            │
│          Api\CkanController::import()                           │
│                                                                 │
│     10. Valida token de seguridad                               │
│         if (token !== callbackToken) → 401 Unauthorized         │
│                                                                 │
│     11. Conecta a current.db                                    │
│         $db = new \SQLite3('data/db/current.db')               │
│                                                                 │
│     12. DROP TABLE IF EXISTS ckan_miradores                     │
│                                                                 │
│     13. CREATE TABLE ckan_miradores (...)                       │
│         - Detecta columnas del primer registro                  │
│         - Crea tabla con tipos adecuados                        │
│                                                                 │
│     14. INSERT en bulk (todos los registros)                    │
│         - Transacción única                                     │
│         - Prepared statements                                   │
│                                                                 │
│     15. CREATE INDEX en campos comunes                          │
│         - nombre, tipo, municipio, provincia                    │
│                                                                 │
│     16. UPDATE ckan_resource_sync                               │
│         - status = 'success'                                    │
│         - last_synced = NOW()                                   │
│         - rows_imported = 150                                   │
│                                                                 │
│     17. Crea notificación admin                                 │
│         - "✅ Miradores sincronizados (150 registros)"          │
│                                                                 │
│     Respuesta: 200 OK                                           │
│     { "success": true, "table": "ckan_miradores", "rows": 150 } │
└────────────────────────────────────────────────────────────────┘
                              │
                              │ 18. Polling detecta status='success'
                              ▼
┌────────────────────────────────────────────────────────────────┐
│              ADMIN UI - ACTUALIZACIÓN AUTOMÁTICA                │
│                                                                 │
│     - Muestra: "✅ Sincronización completada"                   │
│     - Recarga página con datos actualizados                     │
│     - Tabla ckan_miradores ahora visible en:                    │
│       * GET /api/data/tables                                    │
│       * Wizard de componentes                                   │
│       * QueryBuilder                                            │
└────────────────────────────────────────────────────────────────┘
```

---

## 📦 COMPONENTES DEL SISTEMA

### 1. Panel Admin (CodeIgniter 4)

**Ubicación:** Servidor de producción (Shared hosting / VPS)
**Tecnología:** PHP 8.1+, SQLite3, CodeIgniter 4.6.3
**Base de datos:** `data/db/current.db` (SQLite)

#### Archivos Implementados:

**Configuración:**
- `app/Config/Ckan.php` - Configuración API CKAN + Render
  - `renderServiceUrl`: URL del microservicio
  - `callbackToken`: Token de seguridad compartido

- `app/Config/CkanSync.php` - Recursos disponibles
  - `availableResources`: Array de recursos sincronizables

**Controladores:**
- `app/Controllers/Admin/CkanSync.php` (~280 líneas)
  - `index()`: Vista de sincronización
  - `triggerSync()`: Iniciar sincronización en Render
  - `getStatus()`: Polling para verificar estado

- `app/Controllers/Api/CkanController.php` (~476 líneas)
  - `import()`: Callback endpoint (NUEVO)
  - Validación de token
  - Creación de tabla en current.db
  - Inserción de datos en bulk

**Vistas:**
- `app/Views/admin/ckan/sync.php` (~240 líneas)
  - Interfaz de usuario
  - Lista de recursos
  - Botones de sincronización
  - Polling cada 10s
  - Notificaciones en tiempo real

**Librerías:**
- `app/Libraries/ShpProcessor.php` (~495 líneas)
  - `importToMainDatabase()`: Importa SHP a current.db
  - Crea tabla con prefijo `ckan_*`
  - Crea índices automáticamente

**Rutas:**
```php
// Vista admin
$routes->get('admin-panel-bmp/ckan/sync', 'Admin\CkanSync::index');

// Trigger sincronización
$routes->post('admin-panel-bmp/ckan/sync/trigger', 'Admin\CkanSync::triggerSync');

// Polling estado
$routes->get('admin-panel-bmp/ckan/sync/status/(:segment)', 'Admin\CkanSync::getStatus/$1');

// Callback desde Render
$routes->post('api/admin/ckan/import', 'Api\CkanController::import');
```

---

### 2. Microservicio Render

**Ubicación:** https://ckan-processor.onrender.com (pendiente de deploy)
**Tecnología:** PHP 8.1+, GDAL 3.x, Linux (Ubuntu)
**Plan recomendado:** Render Starter ($7/mes) o superior

#### Estructura del Microservicio:

```
ckan-render-processor/
├── public/
│   └── index.php                      # Entry point
├── src/
│   ├── Config/
│   │   └── Config.php                 # Configuración CKAN
│   ├── Services/
│   │   ├── CkanClient.php             # Cliente HTTP CKAN
│   │   ├── ShpDownloader.php          # Descarga y extracción
│   │   ├── ShpConverter.php           # Conversión ogr2ogr
│   │   └── CallbackSender.php         # Envío callback
│   └── Controllers/
│       └── SyncController.php         # Endpoint principal
├── vendor/                            # Composer dependencies
├── composer.json
├── .gitignore
└── README.md
```

#### Endpoint Principal:

**POST /api/sync-shp**

```php
<?php
// src/Controllers/SyncController.php

class SyncController
{
    public function syncShp()
    {
        // 1. Validar request
        $json = $this->getRequestBody();

        // 2. Responder inmediatamente (202 Accepted)
        $this->sendResponse(202, [
            'success' => true,
            'message' => 'Procesamiento iniciado'
        ]);

        // 3. Procesar en background (fastcgi_finish_request)
        fastcgi_finish_request();

        try {
            // 4. Descargar SHP desde CKAN
            $shpPath = $this->downloadShp($json['resource_id']);

            // 5. Convertir con ogr2ogr
            $data = $this->convertShp($shpPath);

            // 6. Enviar callback al panel
            $this->sendCallback([
                'token' => $json['callback_token'],
                'table_name' => 'ckan_' . $json['resource_name'],
                'data' => $data,
                'metadata' => [
                    'resource_id' => $json['resource_id'],
                    'rows' => count($data),
                    'processed_at' => date('Y-m-d H:i:s')
                ]
            ], $json['callback_url']);

        } catch (\Exception $e) {
            // Log error y enviar callback con error
            $this->sendCallback([
                'token' => $json['callback_token'],
                'success' => false,
                'error' => $e->getMessage()
            ], $json['callback_url']);
        }
    }
}
```

#### Dependencias Render:

**composer.json:**
```json
{
    "require": {
        "php": ">=8.1",
        "ext-curl": "*",
        "ext-zip": "*",
        "ext-pdo": "*",
        "ext-sqlite3": "*",
        "guzzlehttp/guzzle": "^7.0"
    }
}
```

**Buildpack (heroku):**
```
heroku/php
https://github.com/heroku/heroku-buildpack-apt
```

**Aptfile (para GDAL):**
```
gdal-bin
libgdal-dev
```

**Variables de entorno:**
```
CKAN_BASE_URL=http://ckan.dipupalenciaindicadores.secmotic.com
CKAN_JWT_TOKEN=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

---

## 🔒 SEGURIDAD

### Token de Callback

**Generación (ya implementado):**
```php
// app/Config/Ckan.php
public string $callbackToken = '7f8a9b6c5d4e3f2a1b0c9d8e7f6a5b4c3d2e1f0a9b8c7d6e5f4a3b2c1d0e9f8a';
```

**Validación en callback:**
```php
// app/Controllers/Api/CkanController.php::import()
$token = $json['token'] ?? null;

if (!$token || $token !== $this->ckanConfig->callbackToken) {
    return $this->failUnauthorized('Token de autorización inválido');
}
```

### Protección contra Inyección SQL

**Uso de Prepared Statements:**
```php
$stmt = $db->prepare("INSERT INTO {$tableName} (...) VALUES (?, ?, ?)");
foreach ($data as $row) {
    $stmt->bindValue(1, $row['campo1']);
    $stmt->bindValue(2, $row['campo2']);
    $stmt->execute();
}
```

### CORS

**Configuración en Panel Admin:**
```php
// Solo aceptar callbacks desde Render
header('Access-Control-Allow-Origin: https://ckan-processor.onrender.com');
```

---

## 📊 FLUJO DE DATOS

### Request Panel → Render

```json
POST https://ckan-processor.onrender.com/api/sync-shp
Content-Type: application/json

{
  "resource_id": "c20d3a73-b678-4472-830a-b01037a00654",
  "resource_name": "miradores",
  "callback_url": "https://panel.bosquepalencia.es/api/admin/ckan/import",
  "callback_token": "7f8a9b6c5d4e3f2a1b0c9d8e7f6a5b4c3d2e1f0a9b8c7d6e5f4a3b2c1d0e9f8a"
}
```

### Response Inmediata de Render

```json
HTTP/1.1 202 Accepted
Content-Type: application/json

{
  "success": true,
  "message": "Procesamiento iniciado",
  "estimated_time": "2-3 minutos"
}
```

### Callback Render → Panel (Éxito)

```json
POST https://panel.bosquepalencia.es/api/admin/ckan/import
Content-Type: application/json

{
  "token": "7f8a9b6c5d4e3f2a1b0c9d8e7f6a5b4c3d2e1f0a9b8c7d6e5f4a3b2c1d0e9f8a",
  "table_name": "ckan_miradores",
  "data": [
    {
      "nombre": "Mirador del Cerrato",
      "latitud": 42.123456,
      "longitud": -4.567890,
      "descripcion": "Vista panorámica del valle",
      "municipio": "Palencia"
    },
    {
      "nombre": "Mirador de la Montaña Palentina",
      "latitud": 42.987654,
      "longitud": -4.321098,
      "descripcion": "Vistas de los picos de Europa",
      "municipio": "Cervera de Pisuerga"
    }
    // ... 148 registros más
  ],
  "metadata": {
    "resource_id": "c20d3a73-b678-4472-830a-b01037a00654",
    "rows": 150,
    "size_bytes": 15360,
    "processed_at": "2025-10-21 14:32:18"
  }
}
```

### Callback Render → Panel (Error)

```json
POST https://panel.bosquepalencia.es/api/admin/ckan/import
Content-Type: application/json

{
  "token": "7f8a9b6c5d4e3f2a1b0c9d8e7f6a5b4c3d2e1f0a9b8c7d6e5f4a3b2c1d0e9f8a",
  "success": false,
  "error": "No se pudo descargar el archivo SHP: HTTP 404",
  "resource_id": "c20d3a73-b678-4472-830a-b01037a00654"
}
```

---

## 💾 ESTRUCTURA DE BASE DE DATOS

### Tabla de Tracking

```sql
-- Tabla: ckan_resource_sync (ya migrada)
CREATE TABLE ckan_resource_sync (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    resource_name VARCHAR(255) UNIQUE NOT NULL,
    resource_id VARCHAR(255) NOT NULL,
    last_modified DATETIME,
    last_synced DATETIME NOT NULL,
    file_size BIGINT,
    format VARCHAR(50),
    status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'processing', 'success', 'error'
    error_message TEXT,
    sqlite_path VARCHAR(500),
    geojson_path VARCHAR(500),
    feature_count INTEGER,
    created_at DATETIME NOT NULL,
    updated_at DATETIME
);

CREATE INDEX idx_resource_name ON ckan_resource_sync(resource_name);
CREATE INDEX idx_status ON ckan_resource_sync(status);
CREATE INDEX idx_last_synced ON ckan_resource_sync(last_synced);
```

### Tabla CKAN Sincronizada (Ejemplo)

```sql
-- Tabla: ckan_miradores (creada dinámicamente por callback)
CREATE TABLE ckan_miradores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT,
    latitud REAL,
    longitud REAL,
    descripcion TEXT,
    municipio TEXT,
    provincia TEXT,
    altitud INTEGER,
    accesibilidad TEXT
);

-- Índices automáticos
CREATE INDEX idx_ckan_miradores_nombre ON ckan_miradores(nombre);
CREATE INDEX idx_ckan_miradores_municipio ON ckan_miradores(municipio);
CREATE INDEX idx_ckan_miradores_provincia ON ckan_miradores(provincia);
```

### Integración con QueryBuilder

Las tablas `ckan_*` aparecen automáticamente:

```
GET /api/data/tables
→ ["dim_municipios", "fact_existencias", "ckan_miradores", "ckan_aparcamientos"]

POST /api/data/query
{
  "table": "ckan_miradores",
  "fields": ["nombre", "latitud", "longitud"],
  "filters": [{"field": "provincia", "operator": "=", "value": "PALENCIA"}],
  "options": {"limit": 10}
}
```

---

## 🚀 DEPLOYMENT

### Panel Admin

**Requisitos:**
- PHP >= 8.1
- Extensiones: sqlite3, curl, json
- Permisos de escritura en `data/db/`

**No requiere:**
- ❌ GDAL/OGR
- ❌ PHP zip extension
- ❌ Librerías geoespaciales

### Microservicio Render

**Pasos de Deploy:**

1. **Crear repositorio en GitHub:**
   ```bash
   git init ckan-render-processor
   cd ckan-render-processor
   git add .
   git commit -m "Initial commit"
   git remote add origin https://github.com/usuario/ckan-render-processor
   git push -u origin main
   ```

2. **Crear servicio en Render.com:**
   - Web Service
   - Conectar repositorio GitHub
   - Branch: `main`
   - Build Command: `composer install`
   - Start Command: (dejar vacío, usa Apache)

3. **Configurar buildpacks:**
   ```
   heroku/php
   https://github.com/heroku/heroku-buildpack-apt
   ```

4. **Crear Aptfile:**
   ```
   gdal-bin
   libgdal-dev
   ```

5. **Configurar variables de entorno:**
   - `CKAN_BASE_URL`
   - `CKAN_JWT_TOKEN`

6. **Deploy:**
   - Render automáticamente detecta PHP
   - Instala GDAL vía Aptfile
   - URL final: `https://ckan-processor.onrender.com`

7. **Actualizar Panel Admin:**
   ```php
   // app/Config/Ckan.php
   public string $renderServiceUrl = 'https://ckan-processor.onrender.com';
   ```

---

## 🧪 TESTING

### Test End-to-End

1. **Acceder a interfaz admin:**
   ```
   http://localhost/wp/dashboard-dinamico-final/public/admin-panel-bmp/ckan/sync
   ```

2. **Click en "Sincronizar" para un recurso**

3. **Verificar request a Render:**
   - Check logs en Render dashboard
   - Verificar que recibió el POST

4. **Esperar procesamiento (2-3 min)**

5. **Verificar callback:**
   - Check logs del panel admin
   - Verificar POST a `/api/admin/ckan/import`

6. **Verificar tabla creada:**
   ```bash
   sqlite3 data/db/current.db
   .tables  # Debe incluir ckan_miradores
   SELECT COUNT(*) FROM ckan_miradores;
   SELECT * FROM ckan_miradores LIMIT 5;
   ```

7. **Verificar integración:**
   ```
   GET /api/data/tables
   # Debe incluir "ckan_miradores"

   POST /api/data/query
   {
     "table": "ckan_miradores",
     "fields": ["nombre", "latitud", "longitud"],
     "options": {"limit": 10}
   }
   ```

### Test de Callback Local

**Simular callback desde Render:**

```bash
curl -X POST http://localhost/wp/dashboard-dinamico-final/public/api/admin/ckan/import \
  -H "Content-Type: application/json" \
  -d '{
    "token": "7f8a9b6c5d4e3f2a1b0c9d8e7f6a5b4c3d2e1f0a9b8c7d6e5f4a3b2c1d0e9f8a",
    "table_name": "ckan_test",
    "data": [
      {"nombre": "Test 1", "valor": 100},
      {"nombre": "Test 2", "valor": 200}
    ],
    "metadata": {
      "resource_id": "test",
      "rows": 2,
      "processed_at": "2025-10-21 14:00:00"
    }
  }'
```

**Respuesta esperada:**
```json
{
  "success": true,
  "table": "ckan_test",
  "rows_inserted": 2
}
```

---

## 📈 MÉTRICAS Y MONITOREO

### Tiempos Estimados

| Etapa | Tiempo |
|-------|--------|
| Request Panel → Render | <1s |
| Descarga SHP desde CKAN | 5-30s |
| Conversión ogr2ogr | 30-60s |
| Lectura SQLite temporal | 5-10s |
| Callback Render → Panel | <1s |
| Inserción en current.db | 10-30s |
| **Total estimado** | **2-3 minutos** |

### Cold Start de Render

- Render Free: 30-60s (NO recomendado)
- Render Starter: <5s
- Render Standard: <2s

### Límites

- Payload máximo: 10 MB (ajustable en Render)
- Registros por recurso: 10,000 registros típicos
- Recursos concurrentes: 3-5 (según plan Render)

---

## ✅ VENTAJAS DE ESTA ARQUITECTURA

1. **No requiere GDAL local**
   - Servidor de producción sin dependencias pesadas
   - Windows: evita OSGeo4W (~500 MB)
   - Linux: evita GDAL (~20 MB)

2. **Integración automática**
   - Tablas `ckan_*` en current.db
   - QueryBuilder las reconoce sin configuración
   - Wizard de componentes las lista automáticamente

3. **Sincronización bajo demanda**
   - Admin decide cuándo actualizar
   - No hay cronjobs automáticos
   - Control total del proceso

4. **Background processing**
   - Interfaz no se bloquea
   - Polling muestra progreso
   - Notificaciones en tiempo real

5. **Escalable**
   - Render puede procesar múltiples recursos en paralelo
   - Separación clara: Panel (UI) vs Render (Processing)
   - Fácil upgrade de plan según necesidad

6. **Mantenible**
   - Logs centralizados en Render dashboard
   - Deploy automático con GitHub
   - Rollback fácil si hay errores

---

## 🔄 PRÓXIMOS PASOS

### Completados ✅

1. ✅ Backend panel admin (280 líneas)
2. ✅ Vista interfaz admin (240 líneas)
3. ✅ Endpoint callback (implementado y protegido)
4. ✅ ShpProcessor con `importToMainDatabase()`
5. ✅ Configuración `renderServiceUrl` y `callbackToken`
6. ✅ Rutas configuradas
7. ✅ Tabla `ckan_resource_sync` migrada

### Pendientes ⏳

1. ⏳ **Implementar microservicio Render**
   - Crear repositorio en GitHub
   - Implementar endpoints
   - Configurar buildpacks GDAL

2. ⏳ **Deploy en Render.com**
   - Configurar variables de entorno
   - Verificar GDAL instalado
   - Obtener URL del servicio

3. ⏳ **Configurar Panel Admin**
   - Actualizar `renderServiceUrl` en `Ckan.php`
   - Actualizar Resource IDs reales en `CkanSync.php`

4. ⏳ **Testing end-to-end**
   - Sincronizar primer recurso
   - Verificar callback
   - Verificar tabla en current.db
   - Verificar integración QueryBuilder

---

**Última actualización:** 21 de Octubre de 2025
**Estado:** ✅ Documentación completa - ⏳ Microservicio pendiente
**Versión:** 1.0.0

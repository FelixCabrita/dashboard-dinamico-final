# CONFIGURACIÓN DE GITHUB ACTIONS PARA CKAN SYNC

**Proyecto:** Dashboard Dinámico Ambiental - Bosque Modelo Palencia
**Fecha:** 21 de Octubre de 2025
**Versión:** 1.0.0

---

## 🎯 OBJETIVO

Automatizar la sincronización de recursos SHP desde CKAN usando GitHub Actions en lugar de cronjobs locales.

---

## ✅ VENTAJAS DE GITHUB ACTIONS vs CRONJOB LOCAL

| Característica | GitHub Actions | Cronjob Local |
|----------------|----------------|---------------|
| **Infraestructura** | ✅ Gratuita (2000 min/mes) | ❌ Requiere servidor 24/7 |
| **Mantenimiento** | ✅ Cero mantenimiento | ❌ Actualizar servidor |
| **Logs** | ✅ Interfaz web integrada | ❌ Revisar archivos manualmente |
| **Notificaciones** | ✅ Email/Slack integrado | ❌ Configurar manualmente |
| **Historial** | ✅ Guardado automático | ❌ Rotar logs manualmente |
| **Artifacts** | ✅ Almacenamiento gratuito | ❌ Gestión manual |
| **Ejecución manual** | ✅ Botón en GitHub | ❌ SSH al servidor |
| **Multi-entorno** | ✅ Fácil replicar | ❌ Configurar en cada servidor |

---

## 📋 WORKFLOWS CREADOS

### 1. ckan-sync-weekly.yml - Sincronización Semanal

**Cuándo se ejecuta:**
- 🕒 Automático: Todos los **lunes a las 3:00 AM UTC**
- 🖱️ Manual: Desde GitHub UI

**Qué hace:**
1. ✅ Descarga código del repositorio
2. ✅ Instala PHP 8.1 + extensiones necesarias
3. ✅ Instala GDAL/OGR para procesar SHP
4. ✅ Ejecuta `php spark ckan:sync`
5. ✅ Genera archivos SQLite y GeoJSON
6. ✅ Sube archivos como artifacts (30 días)
7. ✅ Commitea GeoJSON al repositorio
8. ✅ Envía email con resultado
9. ✅ Genera resumen en GitHub

**Archivo:** `.github/workflows/ckan-sync-weekly.yml`

---

### 2. ckan-sync-manual.yml - Sincronización Manual

**Cuándo se ejecuta:**
- 🖱️ Solo manual desde GitHub UI

**Opciones:**
- **Recurso:** Elegir cuál sincronizar (all, miradores, aparcamientos, puntos_interes)
- **Forzar:** Ignorar caché y descargar aunque esté actualizado

**Qué hace:**
- Igual que el semanal pero con control granular

**Archivo:** `.github/workflows/ckan-sync-manual.yml`

---

### 3. test-ckan-sync.yml - Tests

**Cuándo se ejecuta:**
- 🔀 Automático: En pull requests que modifiquen archivos de sync
- 🖱️ Manual: Desde GitHub UI

**Qué hace:**
- Verifica dependencias instaladas
- Prueba conectividad con CKAN
- Valida configuración

**Archivo:** `.github/workflows/test-ckan-sync.yml`

---

## 🔐 CONFIGURACIÓN DE SECRETS

### Paso 1: Ir a Settings del Repositorio

1. Abrir repositorio en GitHub
2. Ir a **Settings** → **Secrets and variables** → **Actions**
3. Click en **New repository secret**

### Paso 2: Agregar Secrets Requeridos

#### Secret 1: CKAN_API_TOKEN

```
Nombre: CKAN_API_TOKEN
Valor: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiJNcjE4aXZWWkpVN0ZYbWVpR2JwTTBHZTR4dzdnaS1fY2s0QjROQXNWeXJZIiwiaWF0IjoxNzU4NzkxMTE1fQ._ibqSjv1RGPc0NYz3YV-IlUU02z6kyb0he6OILRZO8Q
```

**⚠️ IMPORTANTE:** Este es el token JWT para autenticación con CKAN

---

#### Secret 2: CKAN_BASE_URL

**Desarrollo:**
```
Nombre: CKAN_BASE_URL
Valor: http://ckan.dev.dipupalenciaindicadores.secmotic.com
```

**Producción:**
```
Nombre: CKAN_BASE_URL
Valor: http://ckan.dipupalenciaindicadores.secmotic.com
```

---

#### Secret 3: NOTIFICATION_EMAIL (Opcional)

```
Nombre: NOTIFICATION_EMAIL
Valor: admin@proyecto.com
```

Email donde se enviarán notificaciones de éxito/error

---

#### Secret 4: MAIL_USERNAME (Opcional - para notificaciones)

```
Nombre: MAIL_USERNAME
Valor: tu-email@gmail.com
```

---

#### Secret 5: MAIL_PASSWORD (Opcional - para notificaciones)

```
Nombre: MAIL_PASSWORD
Valor: tu-app-password-de-gmail
```

**Nota:** Usar App Password de Gmail, no la contraseña normal
- Ir a: https://myaccount.google.com/apppasswords
- Generar password para "GitHub Actions"

---

## 🚀 CÓMO USAR

### Ejecución Automática (Semanal)

✅ **No requiere acción** - Se ejecuta automáticamente todos los lunes a las 3 AM UTC

**Verificar:**
1. Ir a **Actions** en GitHub
2. Ver workflow "CKAN Sync - Semanal"
3. Revisar runs anteriores

---

### Ejecución Manual

#### Opción 1: Sincronizar TODO

1. Ir a **Actions** en GitHub
2. Seleccionar "CKAN Sync - Manual"
3. Click en **Run workflow**
4. Seleccionar:
   - **resource:** `all`
   - **force:** `false` (dejar sin marcar)
5. Click **Run workflow**

#### Opción 2: Sincronizar UN recurso

1. Ir a **Actions** en GitHub
2. Seleccionar "CKAN Sync - Manual"
3. Click en **Run workflow**
4. Seleccionar:
   - **resource:** `miradores` (o el que necesites)
   - **force:** `true` (marcar si quieres forzar)
5. Click **Run workflow**

---

### Ver Resultados

#### 1. Logs en Tiempo Real

1. Ir a **Actions**
2. Click en el workflow en ejecución
3. Click en el job "Sincronizar Recursos SHP desde CKAN"
4. Ver logs expandiendo cada step

#### 2. Archivos Generados (Artifacts)

1. Ir a **Actions**
2. Click en el workflow completado
3. Scroll hasta **Artifacts**
4. Descargar:
   - `ckan-sync-data-XXX` - Archivos SQLite y GeoJSON
   - `ckan-sync-logs-XXX` - Logs de sincronización

**Retención:**
- Datos: 30 días
- Logs: 7 días

#### 3. Resumen Automático

GitHub Actions genera un resumen automático que muestra:
- ✅ Estado del sync
- 📊 Recursos procesados
- 📋 Últimas líneas del log
- ⏱️ Duración

---

## 📧 NOTIFICACIONES

### Configurar Email

Si configuraste `MAIL_USERNAME`, `MAIL_PASSWORD` y `NOTIFICATION_EMAIL`, recibirás emails como este:

```
Asunto: CKAN Sync: ✅ Exitoso - #42

Sincronización de recursos CKAN completada.

Estado: success
Workflow: CKAN Sync - Semanal
Run ID: 1234567890
Fecha: 2025-10-21T03:00:00Z

Ver detalles: https://github.com/tu-usuario/tu-repo/actions/runs/1234567890
```

### Configurar Slack (Opcional)

Agregar step al workflow:

```yaml
- name: Slack Notification
  uses: 8398a7/action-slack@v3
  with:
    status: ${{ job.status }}
    text: 'CKAN Sync completado'
    webhook_url: ${{ secrets.SLACK_WEBHOOK }}
  if: always()
```

---

## 🗂️ ESTRUCTURA DE ARCHIVOS

### En el Repositorio

```
.github/
└── workflows/
    ├── ckan-sync-weekly.yml      ← Sincronización automática semanal
    ├── ckan-sync-manual.yml      ← Sincronización manual con opciones
    └── test-ckan-sync.yml        ← Tests de validación

writable/
└── ckan_sync/
    ├── .gitkeep                  ← Mantiene directorio en Git
    ├── sqlite/                   ← (ignorado) Bases de datos SQLite
    ├── geojson/                  ← Archivos GeoJSON (commiteados)
    ├── temp/                     ← (ignorado) Archivos temporales
    └── logs/                     ← (ignorado) Logs de sincronización
```

### Archivos Commiteados al Repo

**SÍ se commitean:**
- ✅ `writable/ckan_sync/.gitkeep`
- ✅ `writable/ckan_sync/geojson/*.geojson` (solo GeoJSON)

**NO se commitean (están en .gitignore):**
- ❌ `writable/ckan_sync/sqlite/` (archivos grandes)
- ❌ `writable/ckan_sync/temp/`
- ❌ `writable/ckan_sync/logs/`

**Razón:** Los archivos SQLite pueden ser muy grandes (varios MB). Los GeoJSON son más ligeros y útiles para mapas web.

---

## 📊 MONITOREO Y MANTENIMIENTO

### Revisar Logs Semanalmente

1. Ir a **Actions**
2. Filtrar por "CKAN Sync - Semanal"
3. Verificar que todos estén ✅ verdes

### Revisar Artifacts Descargados

```bash
# Descargar artifact
# Descomprimir
tar -xzf ckan_sync_data.tar.gz

# Ver bases de datos
ls -lh sqlite/
ls -lh geojson/

# Consultar SQLite
sqlite3 sqlite/shp_miradores.db
> SELECT COUNT(*) FROM miradores;
> .quit
```

### Revisar Consumo de Minutos GitHub

**Plan Free:** 2,000 minutos/mes

**Estimación:**
- 1 sync completo: ~5-10 minutos
- 4 syncs/mes (semanales): ~40 minutos/mes
- **Consumo:** ~2% del límite

**Verificar uso:**
1. Settings → Billing → Plans and usage
2. Ver "Actions & Packages"

---

## 🔧 TROUBLESHOOTING

### Error: "Secret CKAN_API_TOKEN not found"

**Solución:**
1. Ir a Settings → Secrets and variables → Actions
2. Verificar que existe `CKAN_API_TOKEN`
3. Verificar que el nombre está EXACTAMENTE igual

---

### Error: "ogr2ogr: command not found"

**Causa:** GDAL no instalado correctamente

**Solución:** Ya está en el workflow:
```yaml
- name: 🗺️ Instalar GDAL
  run: |
    sudo apt-get update
    sudo apt-get install -y gdal-bin
```

Si falla, agregar versión específica:
```yaml
sudo apt-get install -y gdal-bin=3.4.1+dfsg-1ubuntu4
```

---

### Error: "No space left on device"

**Causa:** Muchos archivos temporales acumulados

**Solución:** Ya se limpian automáticamente en el workflow, pero si persiste:
```yaml
- name: Limpiar espacio
  run: |
    df -h
    sudo rm -rf /tmp/*
    df -h
```

---

### Error: "GitHub Actions timeout after 6 hours"

**Causa:** Sincronización muy lenta

**Solución:**
1. Sincronizar recursos uno por uno
2. Aumentar timeout (max 6h):
```yaml
jobs:
  sync:
    timeout-minutes: 360  # 6 horas
```

---

### Warning: "Artifacts will be deleted after 30 days"

**Solución:** Descargar artifacts importantes antes de que expiren, o usar step de commit para guardar en repo.

---

## 🎯 MEJORAS FUTURAS

### 1. Deploy Automático

Agregar step para deployar archivos sincronizados a servidor:

```yaml
- name: 🚀 Deploy a servidor
  uses: appleboy/scp-action@master
  with:
    host: ${{ secrets.SERVER_HOST }}
    username: ${{ secrets.SERVER_USER }}
    key: ${{ secrets.SSH_PRIVATE_KEY }}
    source: "writable/ckan_sync/sqlite/,writable/ckan_sync/geojson/"
    target: "/var/www/dashboard/writable/ckan_sync/"
```

### 2. Validación de Datos

Agregar tests para verificar integridad:

```yaml
- name: 🧪 Validar datos
  run: |
    # Verificar que SQLite no esté corrupto
    for db in writable/ckan_sync/sqlite/*.db; do
      sqlite3 "$db" "PRAGMA integrity_check;" || exit 1
    done

    # Verificar que GeoJSON sean válidos
    for geojson in writable/ckan_sync/geojson/*.geojson; do
      python -m json.tool "$geojson" > /dev/null || exit 1
    done
```

### 3. Cache de Dependencias

Cachear GDAL para acelerar:

```yaml
- name: Cache GDAL
  uses: actions/cache@v3
  with:
    path: /usr/bin/ogr2ogr
    key: gdal-${{ runner.os }}-3.4.1
```

### 4. Matrix Strategy (Múltiples Recursos en Paralelo)

```yaml
jobs:
  sync:
    strategy:
      matrix:
        resource: [miradores, aparcamientos, puntos_interes]
    steps:
      - run: php spark ckan:sync --resource=${{ matrix.resource }}
```

---

## 📋 CHECKLIST DE IMPLEMENTACIÓN

### Configuración Inicial

- [ ] Crear repositorio en GitHub (si no existe)
- [ ] Subir código al repositorio
- [ ] Agregar secrets (CKAN_API_TOKEN, CKAN_BASE_URL)
- [ ] Verificar que workflows están en `.github/workflows/`
- [ ] Habilitar GitHub Actions en Settings

### Primer Test

- [ ] Ir a Actions → "Test CKAN Sync"
- [ ] Click "Run workflow"
- [ ] Verificar que todos los checks pasen ✅

### Primera Sincronización Manual

- [ ] Ir a Actions → "CKAN Sync - Manual"
- [ ] Run workflow con resource="miradores"
- [ ] Verificar logs en tiempo real
- [ ] Descargar artifact generado
- [ ] Verificar SQLite y GeoJSON

### Configurar Notificaciones (Opcional)

- [ ] Generar App Password de Gmail
- [ ] Agregar secrets: MAIL_USERNAME, MAIL_PASSWORD, NOTIFICATION_EMAIL
- [ ] Ejecutar sync de prueba
- [ ] Verificar que llega email

### Activar Sync Automático

- [ ] Verificar que cron está configurado: `0 3 * * 1`
- [ ] Esperar al siguiente lunes 3 AM
- [ ] Revisar que ejecutó automáticamente

---

## 🎓 RECURSOS ADICIONALES

### Documentación GitHub Actions

- [Workflow syntax](https://docs.github.com/en/actions/reference/workflow-syntax-for-github-actions)
- [Scheduled events](https://docs.github.com/en/actions/reference/events-that-trigger-workflows#schedule)
- [Secrets](https://docs.github.com/en/actions/security-guides/encrypted-secrets)

### Cron Expression

Herramienta para generar/verificar cron: https://crontab.guru/

Ejemplos:
- `0 3 * * 1` - Lunes 3 AM
- `0 3 * * *` - Todos los días 3 AM
- `0 3 1 * *` - Primer día del mes 3 AM

---

## ✅ CONCLUSIÓN

GitHub Actions proporciona:

- ✅ **Infraestructura gratuita** (2000 min/mes)
- ✅ **Cero mantenimiento** de servidores
- ✅ **Logs y artifacts** automáticos
- ✅ **Ejecución manual** con 1 click
- ✅ **Notificaciones** integradas
- ✅ **Historial completo** de ejecuciones

**Recomendación:** Usar GitHub Actions en lugar de cronjobs locales para mayor confiabilidad y menor mantenimiento.

---

**Última actualización:** 21 de Octubre de 2025
**Autor:** Claude Code
**Estado:** ✅ Listo para implementación

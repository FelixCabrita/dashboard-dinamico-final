# DOCUMENTACIÓN TÉCNICA - API CKAN
## Dashboard Dinámico Ambiental - Bosque Modelo Palencia

**Versión:** 1.0.0
**Fecha:** 21 de Octubre de 2025
**Autor:** Equipo de Desarrollo
**Estado:** Validado en Testing

---

## ⚠️ ADVERTENCIAS Y LIMITACIONES CRÍTICAS

### 🔴 LIMITACIONES DETECTADAS

#### 1. Discrepancia en Inventario de Datos
**Problema:** La documentación del cliente indica 23 datasets, pero solo existen 20 en el servidor.

**Impacto:** Posibles inconsistencias en la planificación del proyecto.

**Acción requerida:**
- Consultar con el cliente sobre los 3 datasets faltantes
- Verificar si fueron eliminados o nunca existieron
- Actualizar documentación del cliente

**Estado:** ⚠️ PENDIENTE DE ACLARACIÓN

---

#### 2. Organizaciones Sin Metadata Completa
**Problema:** Los datasets tienen `organization.id` y `organization.name`, pero NO tienen `organization.title` ni `organization.description`.

```json
{
  "organization": {
    "id": "abc123...",
    "name": "organizacion_slug",
    "title": "",           // ← VACÍO
    "description": ""      // ← VACÍO
  }
}
```

**Impacto:**
- No se puede mostrar nombre legible de la organización en el dashboard
- Dificulta la clasificación visual de datasets

**Workaround:** Usar `organization.name` (slug técnico)

**Acción requerida:** Solicitar al administrador CKAN que complete la metadata de organizaciones

**Estado:** ⚠️ FUNCIONALIDAD DEGRADADA

---

#### 3. Mayoría de Recursos NO Consultables vía API
**Problema:** 194 de 243 recursos (79.8%) son archivos SHP que NO tienen `datastore_active: true`

**Distribución:**
```
Total recursos:     243
├─ SHP (NO API):    194 (79.8%)  ← No consultables
├─ CSV (API):       45  (18.5%)  ✓ Consultables
├─ XLS (API):       3   (1.2%)   ✓ Consultables
└─ GeoJSON:         1   (0.4%)   ? Sin validar
```

**Implicaciones:**
- Solo 47 recursos (19.3%) son consultables dinámicamente
- Los 194 archivos SHP requieren descarga completa
- Dashboards dinámicos limitados a los 47 recursos CSV/XLS

**Alternativas para archivos SHP:**
1. Descargar y procesar localmente con GDAL/Shapely
2. Convertir a GeoJSON server-side
3. Crear tabla en base de datos desde SHP
4. Usar solo los 47 recursos consultables

**Recomendación:** Enfocarse en los 47 recursos consultables. Son suficientes para el proyecto.

**Estado:** ✅ LIMITACIÓN ACEPTADA

---

#### 4. Endpoint Espacial No Disponible
**Problema:** El endpoint `spatial_filter_by_municipality` retorna HTTP 409 (Conflict)

**Test ejecutado:**
```http
GET /api/3/action/spatial_filter_by_municipality?municipality=Palencia&format=geojson
Response: HTTP 409
```

**Causa probable:** Extensión espacial de CKAN no activada o mal configurada

**Solución validada:** Usar `datastore_search` con filtros JSON
```http
GET /api/3/action/datastore_search?
  resource_id={ID}&
  filters={"Provincia":"PALENCIA"}
```

**Rendimiento:** 416ms para 191 municipios (excelente)

**Estado:** ✅ WORKAROUND FUNCIONAL

---

#### 5. Latencia Inicial Alta (Cold Start)
**Problema:** Primera consulta al servidor tarda ~1.3 segundos

**Mediciones:**
```
Primera consulta:     1,292ms  ⚠️
Consultas siguientes: ~420ms   ✓
```

**Causa:** Caché en frío del servidor CKAN

**Impacto:** UX degradada en primera carga del dashboard

**Solución obligatoria:** Implementar caché agresivo en cliente (TTL: 1 hora mínimo)

**Estado:** ⚠️ REQUIERE CACHÉ OBLIGATORIO

---

#### 6. Sin Límites de Rate Documentados
**Problema:** No se documentaron límites de peticiones por minuto/hora

**Riesgo:** Posible bloqueo por rate limiting si se hacen consultas masivas

**Recomendación:**
- Implementar throttling en cliente (max 10 req/segundo)
- No hacer consultas en loops sin control
- Usar batch processing con delays

**Estado:** ⚠️ SIN INFORMACIÓN

---

#### 7. Datasets Privados Predominan (80%)
**Problema:** 16 de 20 datasets son privados

**Implicaciones:**
- Requiere autenticación JWT para TODOS los accesos
- Token debe estar seguro (no exponerlo en frontend)
- Si el token expira, todo el dashboard falla
- No se pueden compartir visualizaciones públicas sin auth

**Recomendación:**
- Implementar proxy server-side para ocultar token
- Manejo robusto de expiración de token
- Considerar solicitar al cliente que haga públicos algunos datasets clave

**Estado:** ⚠️ RIESGO DE SEGURIDAD

---

#### 8. Sin Soporte para Consultas Complejas
**Problema:** El filtro JSON de `datastore_search` solo soporta igualdad exacta

**Lo que NO funciona:**
```json
{"Población": {">": 10000}}        // ✗ Mayor que
{"FECHA": {"between": [2020, 2023]}}  // ✗ Rangos
{"Municipio": {"like": "PAL%"}}    // ✗ LIKE
```

**Lo que SÍ funciona:**
```json
{"Provincia": "PALENCIA"}          // ✓ Igualdad exacta
{"Cod_Provincia": 34}              // ✓ Igualdad numérica
```

**Workaround:** Usar parámetro `q` para búsqueda flexible (pero menos precisa)

**Impacto:** Filtros avanzados deben implementarse post-procesando en cliente

**Estado:** ⚠️ LIMITACIÓN DE DISEÑO

---

## 📊 INVENTARIO COMPLETO DE DATOS

### Resumen Global

```
Servidor:         ckan.dev.dipupalenciaindicadores.secmotic.com
Datasets:         20
Recursos totales: 243
├─ Consultables:  47 (19.3%)
└─ SHP:           194 (79.8%)

Privacidad:
├─ Privados:      16 (80%)
└─ Públicos:      4 (20%)

Organizaciones:   5 (sin nombres completos)
```

### Datasets por Categoría

#### 🌳 Medio Ambiente y Naturaleza (88 recursos)

| Dataset | Recursos | Consultables | Privacidad |
|---------|----------|--------------|------------|
| `conservacion_y_espacios_naturales` | 16 | 0 | Privado |
| `flora_y_fauna` | 9 | 2 ⭐ | Privado |
| `suelo_y_erosion` | 7 | 0 | Privado |
| `hidrografia_y_recursos_hidricos` | 10 (2+8) | 0 | Mixto |
| `incendios_y_prevencion` | 46 | 0 | Privado |

**Recursos consultables clave:**
- `Incendios forestales` (20,608 registros) ⭐⭐⭐
- `Explotaciones acuícolas`

---

#### 🌾 Agricultura, Ganadería y Uso del Suelo (35 recursos)

| Dataset | Recursos | Consultables | Privacidad |
|---------|----------|--------------|------------|
| `agricultura_y_ganaderia` | 28 | 7 ⭐⭐⭐ | Privado |
| `usos_del_suelo` | 7 | 0 | Privado |

**Recursos consultables clave:**
- Terrenos cinegéticos
- Listado de cotos de caza
- Resultados de temporadas cinegéticas
- Licencias de caza y pesca
- Censo de superficies agrarias ⭐
- Explotaciones ganaderas apícolas
- Superficies de cultivos herbáceos

---

#### 👥 Demografía y Socioeconómico (36 recursos)

| Dataset | Recursos | Consultables | Privacidad |
|---------|----------|--------------|------------|
| `demografia_y_empleo` | 5 | 5 ⭐⭐⭐ | Público |
| `asociaciones_y_grupos_locales` | 10 | 10 ⭐⭐⭐ | Público |
| `turismo_y_patrimonio` | 6 | 3 ⭐ | Privado |
| `servicios_y_comercio` | 9 | 5 ⭐ | Privado |
| `industria_y_economia` | 6 | 4 ⭐ | Privado |

**Recursos consultables clave:**
- **Población total por provincias y sexo** (594 registros, 1986-2023) ⭐⭐⭐
- Número de nacimientos, matrimonios, defunciones
- Número de parados y demandantes de empleo ⭐⭐
- Registro de cooperativas
- Asociaciones forestales ⭐
- Grupos de Acción Local
- Registro de Turismo de CyL (6.39 MB)

---

#### 🏗️ Infraestructura y Planificación (64 recursos)

| Dataset | Recursos | Consultables | Privacidad |
|---------|----------|--------------|------------|
| `normativa_y_planificacion` | 41 | 2 | Privado |
| `transporte` + `transportes` | 9 | 2 | Privado |
| `energia_e_infrastructuras` | 14 | 0 | Privado |

**Recursos consultables clave:**
- Planes y programas
- Licitación de obras públicas
- Catálogo de carreteras

---

#### 📚 Educación y Cultura (5 recursos)

| Dataset | Recursos | Consultables | Privacidad |
|---------|----------|--------------|------------|
| `educacion_y_cultura` | 5 | 4 ⭐ | Privado |

**Recursos consultables:**
- Red de Teatros
- Directorio de Centros Docentes
- Infraestructuras culturales
- Red de Circuitos escénicos

---

#### 📋 General (15 recursos)

| Dataset | Recursos | Consultables | Privacidad |
|---------|----------|--------------|------------|
| `general` | 12 | 2 ⭐⭐⭐ | Privado |
| `prueba1` | 3 | 1 | Público |

**Recursos consultables clave:**
- **Registro de municipios de CyL** (2,248 registros) ⭐⭐⭐ **CRÍTICO**
- Asociaciones juveniles

---

## 🔑 RECURSOS CRÍTICOS (TOP 3)

### #1 Registro de Municipios de Castilla y León

**Importancia:** ⭐⭐⭐ CRÍTICO (Base para TODOS los filtros geográficos)

```yaml
Resource ID: 41c0aa65-3964-4926-836d-d501f9e55e52
Dataset: general
Formato: CSV
Tamaño: 317.80 KB
Registros: 2,248
Campos: 16
Actualización: DIARIA
```

**Estructura de campos:**
```json
{
  "Municipio": "text",                // Nombre del municipio
  "Cod_Municipio": "numeric",         // Código municipal
  "Provincia": "text",                // Nombre provincia
  "Cod_Provincia": "numeric",         // 34 = Palencia
  "Cod_INE": "numeric",               // Código oficial INE
  "Población": "numeric",            // Habitantes
  "Mancomunidades": "text",
  "Entidades_Locales_Menores": "text",
  "Comarca": "text",
  "Longitud": "numeric",              // GPS: -4.534601
  "Latitud": "numeric",               // GPS: 42.007837
  "CoordenadaX": "numeric",           // UTM
  "CoordenadaY": "numeric",           // UTM
  "Posición": "text",                // "lat, lon"
  "presencia_de_comercio": "numeric"  // Boolean 0/1
}
```

**Cobertura Palencia:**
- 191 municipios (de 2,248 totales)
- Todos con coordenadas GPS válidas
- Datos de población actualizados

**Ejemplo de consulta:**
```http
GET /api/3/action/datastore_search?
  resource_id=41c0aa65-3964-4926-836d-d501f9e55e52&
  filters={"Provincia":"PALENCIA"}&
  fields=Municipio,Cod_INE,Población,Latitud,Longitud&
  sort=Población desc&
  limit=191

Resultado: 191 municipios en 416ms
```

**Casos de uso:**
1. Dropdown de selección de municipios
2. Mapa de municipios con marcadores
3. Tabla de municipios con población
4. Filtrado de otros datasets por Cod_INE

---

### #2 Incendios Forestales

**Importancia:** ⭐⭐⭐ CRÍTICO (Datos ambientales de riesgo)

```yaml
Resource ID: f74b444d-e0f9-4638-916e-ad595bf5f0dc
Dataset: flora_y_fauna
Formato: CSV
Tamaño: 4.39 MB
Registros: 20,608 (mayor dataset)
Campos: 18
Actualización: Durante campaña (tiempo real)
```

**Estructura de campos:**
```json
{
  "FECHA DEL PARTE": "timestamp",
  "HORA DEL PARTE": "text",
  "PROVINCIA": "text",
  "CAUSA PROBABLE": "text",              // RAYOS, ACCIDENTAL, etc.
  "TÉRMINO MUNICIPAL": "text",
  "NIVEL": "text",                       // 0, 1, 2, 3
  "FECHA DE INICIO": "timestamp",
  "HORA DE INICIO": "text",
  "MEDIOS DE EXTINCIÓN": "text",
  "SITUACIÓN ACTUAL": "text",           // ACTIVO, EXTINGUIDO
  "TIPO Y HAS DE SUPERFICIE AFECTADA": "text",
  "FECHA EXTINGUIDO": "timestamp",
  "HORA EXTINGUIDO": "text",
  "CÓDIGO INE": "numeric",
  "NIVEL MÁXIMO ALCANZADO": "text",
  "Posición": "text",                   // Coordenadas
  "orden": "text"
}
```

**Cobertura Palencia:**
- 1,952 incendios históricos
- Búsqueda full-text: 432ms para 20K registros

**Ejemplo de consulta:**
```http
GET /api/3/action/datastore_search?
  resource_id=f74b444d-e0f9-4638-916e-ad595bf5f0dc&
  q=PALENCIA&
  fields=FECHA DE INICIO,TÉRMINO MUNICIPAL,NIVEL MÁXIMO ALCANZADO,TIPO Y HAS DE SUPERFICIE AFECTADA&
  sort=FECHA DE INICIO desc&
  limit=100

Resultado: 1,952 incendios en 432ms
```

**Casos de uso:**
1. Mapa de incendios con heatmap
2. Timeline de incendios por año
3. Gráfico: Causas más frecuentes
4. Gráfico: Superficie afectada por año
5. KPI: Total incendios último año
6. Alerta: Incendios activos

---

### #3 Población Total por Provincias y Sexo

**Importancia:** ⭐⭐⭐ CRÍTICO (Series temporales demográficas)

```yaml
Resource ID: 771bfd9e-d306-4202-bc08-dab243d38a2a
Dataset: demografia_y_empleo
Formato: CSV
Tamaño: 15.21 KB
Registros: 594
Campos: 5
Serie temporal: 1986-2023 (37 años)
```

**Estructura de campos:**
```json
{
  "FECHA": "numeric",      // Año (1986-2023)
  "SEXO": "text",         // "VARON" | "MUJER"
  "PROVINCIA": "text",
  "POBLACIÓN": "numeric"
}
```

**Cobertura Palencia:**
- 66 registros (33 años × 2 sexos)
- Datos completos 1986-2023

**Ejemplo de consulta:**
```http
GET /api/3/action/datastore_search?
  resource_id=771bfd9e-d306-4202-bc08-dab243d38a2a&
  filters={"PROVINCIA":"PALENCIA"}&
  sort=FECHA desc&
  limit=100

Resultado: 66 registros en 419ms
```

**Casos de uso:**
1. Gráfico de línea: Evolución poblacional 1986-2023
2. Gráfico de barras: Comparación hombres vs mujeres
3. KPI: Población total actual
4. KPI: Tasa de crecimiento
5. Proyección: Tendencia futura

---

## 🔌 ENDPOINTS DE LA API

### Base URL
```
http://ckan.dev.dipupalenciaindicadores.secmotic.com/api/3/action/
```

⚠️ **IMPORTANTE:** En producción cambiar a:
```
http://ckan.dipupalenciaindicadores.secmotic.com/api/3/action/
```
(Sin 'dev.')

---

### 1. package_search - Listar Datasets

**Descripción:** Obtiene lista de datasets con filtros opcionales

**Endpoint:**
```http
GET /api/3/action/package_search
```

**Parámetros:**

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `q` | string | No | Búsqueda de texto libre |
| `rows` | integer | No | Número de resultados (default: 10, max: 1000) |
| `start` | integer | No | Offset para paginación |
| `include_private` | boolean | No | Incluir datasets privados (requiere auth) |

**Headers requeridos:**
```http
Authorization: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
Content-Type: application/json
```

**Ejemplo:**
```http
GET /api/3/action/package_search?q=*:*&rows=1000&include_private=true
Authorization: {JWT_TOKEN}
```

**Respuesta exitosa (HTTP 200):**
```json
{
  "success": true,
  "result": {
    "count": 20,
    "results": [
      {
        "id": "986f7a3f-c47f-469c-aa7d-f60dd4377e43",
        "name": "demografia_y_empleo",
        "title": "demografia_y_empleo",
        "private": false,
        "num_resources": 5,
        "organization": {
          "id": "...",
          "name": "org_slug"
        },
        "resources": [ ... ]
      }
    ]
  }
}
```

**Tiempo de respuesta:** ~933ms

**Códigos de error:**
- `401` - Token inválido o faltante
- `500` - Error interno del servidor

---

### 2. package_show - Detalle de Dataset

**Descripción:** Obtiene información completa de un dataset específico

**Endpoint:**
```http
GET /api/3/action/package_show?id={ID}
```

**Parámetros:**

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `id` | string | **SÍ** | UUID o name del dataset |

**Ejemplo:**
```http
GET /api/3/action/package_show?id=986f7a3f-c47f-469c-aa7d-f60dd4377e43
Authorization: {JWT_TOKEN}
```

**Respuesta exitosa (HTTP 200):**
```json
{
  "success": true,
  "result": {
    "id": "986f7a3f-c47f-469c-aa7d-f60dd4377e43",
    "name": "demografia_y_empleo",
    "title": "demografia_y_empleo",
    "notes": "Descripción del dataset...",
    "license_id": "other-open",
    "license_title": "Other (Open)",
    "metadata_created": "2025-08-20T08:07:00",
    "metadata_modified": "2025-10-21T07:36:00",
    "author": null,
    "author_email": null,
    "private": false,
    "state": "active",
    "type": "dataset",
    "num_resources": 5,
    "num_tags": 0,
    "organization": { ... },
    "resources": [ ... ],
    "tags": [],
    "groups": [],
    "extras": []
  }
}
```

**Tiempo de respuesta:** ~420ms

**Códigos de error:**
- `404` - Dataset no encontrado
- `401` - Sin permisos para dataset privado

---

### 3. resource_show - Detalle de Recurso

**Descripción:** Obtiene metadata de un recurso específico

**Endpoint:**
```http
GET /api/3/action/resource_show?id={ID}
```

**Parámetros:**

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| `id` | string | **SÍ** | UUID del recurso |

**Ejemplo:**
```http
GET /api/3/action/resource_show?id=41c0aa65-3964-4926-836d-d501f9e55e52
Authorization: {JWT_TOKEN}
```

**Respuesta exitosa (HTTP 200):**
```json
{
  "success": true,
  "result": {
    "id": "41c0aa65-3964-4926-836d-d501f9e55e52",
    "package_id": "c8e7d4c5-8b47-4e3a-9f5e-2d3c1a0b9e8f",
    "name": "Datos de Registro de municipios de Castilla y León",
    "description": "Listado de municipios...",
    "format": "CSV",
    "mimetype": "text/csv",
    "size": 325434,
    "hash": "abc123...",
    "url": "http://ckan.../download/municipios.csv",
    "url_type": "upload",
    "datastore_active": true,
    "state": "active",
    "created": "2025-08-05T08:06:39",
    "last_modified": "2025-10-21T05:15:11"
  }
}
```

**Tiempo de respuesta:** ~424ms

**Campo crítico:** `datastore_active: true` indica que el recurso es consultable vía API

---

### 4. datastore_search - Consultar Datos ⭐⭐⭐

**Descripción:** Consulta dinámica de datos en recursos CSV/XLS

**⚠️ REQUISITO:** El recurso debe tener `datastore_active: true`

**Endpoint:**
```http
GET /api/3/action/datastore_search?resource_id={ID}&{params}
```

**Parámetros obligatorios:**

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `resource_id` | string | UUID del recurso a consultar |

**Parámetros opcionales:**

| Parámetro | Tipo | Default | Descripción | Ejemplo |
|-----------|------|---------|-------------|---------|
| `limit` | integer | 100 | Máximo de registros | `limit=10` |
| `offset` | integer | 0 | Saltar N registros | `offset=50` |
| `fields` | string | all | Campos a retornar (CSV) | `fields=Municipio,Población` |
| `q` | string | - | Búsqueda full-text | `q=Palencia` |
| `filters` | JSON | - | Filtros exactos por campo | `filters={"Provincia":"PALENCIA"}` |
| `sort` | string | - | Ordenamiento | `sort=Población desc` |

**Ejemplo básico:**
```http
GET /api/3/action/datastore_search?
  resource_id=41c0aa65-3964-4926-836d-d501f9e55e52&
  limit=5
```

**Ejemplo con filtros:**
```http
GET /api/3/action/datastore_search?
  resource_id=41c0aa65-3964-4926-836d-d501f9e55e52&
  filters={"Provincia":"PALENCIA"}&
  fields=Municipio,Población,Latitud,Longitud&
  sort=Población desc&
  limit=10
```

**Ejemplo con búsqueda:**
```http
GET /api/3/action/datastore_search?
  resource_id=f74b444d-e0f9-4638-916e-ad595bf5f0dc&
  q=incendio Palencia 2023&
  limit=20
```

**Respuesta exitosa (HTTP 200):**
```json
{
  "success": true,
  "result": {
    "total": 191,
    "records": [
      {
        "_id": 1,
        "Municipio": "PALENCIA",
        "Población": 76738,
        "Latitud": 42.007837,
        "Longitud": -4.534601
      }
    ],
    "fields": [
      {"id": "_id", "type": "int"},
      {"id": "Municipio", "type": "text"},
      {"id": "Población", "type": "numeric"},
      {"id": "Latitud", "type": "numeric"},
      {"id": "Longitud", "type": "numeric"}
    ],
    "_links": {
      "start": "/api/3/action/datastore_search?...",
      "next": "/api/3/action/datastore_search?...&offset=10"
    }
  }
}
```

**Tiempo de respuesta:**
- Sin filtros: ~426ms (2,248 registros)
- Con filtros: ~416ms (191 resultados)
- Búsqueda full-text: ~515ms (20,608 registros)

**Capacidades validadas:**
1. ✅ Paginación (limit/offset)
2. ✅ Ordenamiento (ASC/DESC)
3. ✅ Selección de campos (reduce payload 50-70%)
4. ✅ Búsqueda full-text con ranking
5. ✅ Filtros JSON exactos
6. ✅ Sin degradación por tamaño de dataset

**Limitaciones:**
- ❌ No soporta operadores `>`, `<`, `>=`, `<=`
- ❌ No soporta rangos `BETWEEN`
- ❌ No soporta `LIKE` o wildcards en filtros
- ✅ Solo igualdad exacta en filtros

**Códigos de error:**
- `404` - Resource ID no encontrado
- `400` - Parámetros inválidos
- `409` - Recurso no tiene datastore activo

---

### 5. help_show - Documentación de Endpoint

**Descripción:** Obtiene ayuda inline de cualquier endpoint

**Endpoint:**
```http
GET /api/3/action/help_show?name={endpoint_name}
```

**Ejemplo:**
```http
GET /api/3/action/help_show?name=datastore_search
```

**Respuesta:**
```json
{
  "success": true,
  "result": "Search a DataStore resource.\n\n:param resource_id: id or alias..."
}
```

**Tiempo de respuesta:** ~380ms

---

## 🔐 AUTENTICACIÓN

### JWT Token

**Método:** Bearer token en header HTTP

**Header requerido:**
```http
Authorization: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiJNcjE4aXZWWkpVN0ZYbWVpR2JwTTBHZTR4dzdnaS1fY2s0QjROQXNWeXJZIiwiaWF0IjoxNzU4NzkxMTE1fQ._ibqSjv1RGPc0NYz3YV-IlUU02z6kyb0he6OILRZO8Q
```

**⚠️ CRÍTICO - Seguridad del Token:**

1. **NO exponer en frontend JavaScript**
   ```javascript
   // ❌ INCORRECTO
   fetch('http://ckan.../api/3/action/...', {
     headers: {
       'Authorization': 'eyJhbGciOiJIUzI1NiI...'  // ← Token expuesto
     }
   });
   ```

2. **SÍ usar proxy server-side**
   ```php
   // ✅ CORRECTO
   // Cliente hace request a:
   GET /api/ckan/municipios

   // Servidor agrega token internamente:
   class CkanApiClient {
       private $token = 'eyJhbGci...';  // Solo en servidor
   }
   ```

3. **Renovación de token:** Sin información - asumir que no expira

4. **Permisos:** El token actual proporciona:
   - ✅ Lectura de todos los datasets (públicos y privados)
   - ❓ Escritura - NO PROBADO (no necesario para dashboard)

---

## 📦 IMPLEMENTACIÓN EN CODEIGNITER

### Estructura de Archivos

```
app/
├── Config/
│   └── Ckan.php                    ← Configuración
├── Libraries/
│   └── CkanApiClient.php           ← Cliente API
├── Models/
│   ├── CkanDatasetModel.php        ← Modelo de datasets
│   └── CkanResourceModel.php       ← Modelo de recursos
└── Controllers/
    └── Api/
        └── CkanController.php      ← API para frontend
```

---

### app/Config/Ckan.php

```php
<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Ckan extends BaseConfig
{
    /**
     * Base URL del servidor CKAN
     *
     * DESARROLLO: http://ckan.dev.dipupalenciaindicadores.secmotic.com
     * PRODUCCIÓN: http://ckan.dipupalenciaindicadores.secmotic.com
     */
    public string $baseUrl = 'http://ckan.dev.dipupalenciaindicadores.secmotic.com';

    /**
     * API Token JWT para autenticación
     *
     * CRÍTICO: NO exponer en frontend. Mantener solo en servidor.
     */
    public string $apiToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiJNcjE4aXZWWkpVN0ZYbWVpR2JwTTBHZTR4dzdnaS1fY2s0QjROQXNWeXJZIiwiaWF0IjoxNzU4NzkxMTE1fQ._ibqSjv1RGPc0NYz3YV-IlUU02z6kyb0he6OILRZO8Q';

    /**
     * Timeout para requests HTTP (segundos)
     */
    public int $timeout = 30;

    /**
     * Número máximo de reintentos en caso de error 5xx
     */
    public int $maxRetries = 3;

    /**
     * Habilitar caché
     */
    public bool $cacheEnabled = true;

    /**
     * TTL de caché en segundos (1 hora)
     */
    public int $cacheTTL = 3600;

    /**
     * Prefix para keys de caché
     */
    public string $cachePrefix = 'ckan_';

    /**
     * Habilitar logging de requests lentos
     */
    public bool $logSlowQueries = true;

    /**
     * Threshold para considerar query lenta (milisegundos)
     */
    public int $slowQueryThreshold = 2000;

    /**
     * Resource IDs de recursos críticos
     */
    public array $criticalResources = [
        'municipios' => '41c0aa65-3964-4926-836d-d501f9e55e52',
        'incendios' => 'f74b444d-e0f9-4638-916e-ad595bf5f0dc',
        'poblacion' => '771bfd9e-d306-4202-bc08-dab243d38a2a',
    ];
}
```

---

### app/Libraries/CkanApiClient.php

```php
<?php

namespace App\Libraries;

use Config\Ckan as CkanConfig;
use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\Cache\CacheInterface;

/**
 * Cliente para API CKAN
 *
 * Proporciona métodos para consultar la API CKAN de forma segura
 * con manejo de errores, retry logic y caché.
 *
 * @version 1.0.0
 */
class CkanApiClient
{
    private CkanConfig $config;
    private CURLRequest $client;
    private CacheInterface $cache;

    public function __construct()
    {
        $this->config = config('Ckan');
        $this->client = \Config\Services::curlrequest();
        $this->cache = \Config\Services::cache();
    }

    /**
     * Hace un request a la API CKAN con retry logic
     *
     * @param string $endpoint Endpoint de la API (ej: 'datastore_search')
     * @param array $params Parámetros de query
     * @return array|null Respuesta JSON o null si falla
     */
    private function makeRequest(string $endpoint, array $params = []): ?array
    {
        $url = $this->config->baseUrl . '/api/3/action/' . $endpoint;

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $attempt = 0;
        $maxRetries = $this->config->maxRetries;

        while ($attempt < $maxRetries) {
            try {
                $startTime = microtime(true);

                $response = $this->client->get($url, [
                    'headers' => [
                        'Authorization' => $this->config->apiToken,
                        'Content-Type' => 'application/json'
                    ],
                    'timeout' => $this->config->timeout,
                    'http_errors' => false
                ]);

                $duration = (microtime(true) - $startTime) * 1000;
                $httpCode = $response->getStatusCode();
                $body = $response->getBody();

                // Log queries lentas
                if ($this->config->logSlowQueries && $duration > $this->config->slowQueryThreshold) {
                    log_message('warning', "CKAN slow query: {$endpoint} ({$duration}ms)");
                }

                // Success
                if ($httpCode === 200) {
                    $data = json_decode($body, true);

                    if (isset($data['success']) && $data['success'] === true) {
                        return $data['result'];
                    }
                }

                // Retry en errores 5xx
                if ($httpCode >= 500 && $httpCode < 600) {
                    $attempt++;
                    if ($attempt < $maxRetries) {
                        sleep(pow(2, $attempt)); // Backoff exponencial: 2s, 4s, 8s
                        continue;
                    }
                }

                // Error 4xx o máximo de reintentos alcanzado
                log_message('error', "CKAN API error: {$endpoint} - HTTP {$httpCode}");
                return null;

            } catch (\Exception $e) {
                log_message('error', "CKAN API exception: {$endpoint} - " . $e->getMessage());

                $attempt++;
                if ($attempt >= $maxRetries) {
                    return null;
                }
            }
        }

        return null;
    }

    /**
     * Obtiene datos de un recurso con caché opcional
     *
     * @param string $resourceId UUID del recurso
     * @param array $params Parámetros de consulta
     * @param bool $useCache Usar caché (default: true)
     * @return array|null
     */
    public function datastoreSearch(string $resourceId, array $params = [], bool $useCache = true): ?array
    {
        $params['resource_id'] = $resourceId;

        // Intentar caché
        if ($useCache && $this->config->cacheEnabled) {
            $cacheKey = $this->config->cachePrefix . md5($resourceId . json_encode($params));

            if ($cached = $this->cache->get($cacheKey)) {
                return $cached;
            }
        }

        // Request real
        $result = $this->makeRequest('datastore_search', $params);

        // Guardar en caché
        if ($result && $useCache && $this->config->cacheEnabled) {
            $this->cache->save($cacheKey, $result, $this->config->cacheTTL);
        }

        return $result;
    }

    /**
     * Obtiene municipios de una provincia
     *
     * @param string $provincia Nombre de la provincia (ej: 'PALENCIA')
     * @param array $options Opciones adicionales (limit, sort, fields)
     * @return array|null
     */
    public function getMunicipios(string $provincia = null, array $options = []): ?array
    {
        $params = [
            'limit' => $options['limit'] ?? 500,
            'sort' => $options['sort'] ?? 'Municipio asc',
            'fields' => $options['fields'] ?? 'Municipio,Cod_INE,Población,Latitud,Longitud'
        ];

        if ($provincia) {
            $params['filters'] = json_encode(['Provincia' => strtoupper($provincia)]);
        }

        return $this->datastoreSearch(
            $this->config->criticalResources['municipios'],
            $params
        );
    }

    /**
     * Obtiene datos de población
     *
     * @param string $provincia Nombre de la provincia
     * @param int|null $year Año específico (opcional)
     * @return array|null
     */
    public function getPoblacion(string $provincia, ?int $year = null): ?array
    {
        $filters = ['PROVINCIA' => strtoupper($provincia)];

        if ($year) {
            $filters['FECHA'] = $year;
        }

        $params = [
            'filters' => json_encode($filters),
            'sort' => 'FECHA desc',
            'limit' => 100
        ];

        return $this->datastoreSearch(
            $this->config->criticalResources['poblacion'],
            $params
        );
    }

    /**
     * Obtiene incendios forestales
     *
     * @param string $provincia Nombre de la provincia
     * @param array $options Opciones (limit, year, fields)
     * @return array|null
     */
    public function getIncendios(string $provincia, array $options = []): ?array
    {
        $params = [
            'q' => strtoupper($provincia),
            'limit' => $options['limit'] ?? 100,
            'sort' => 'FECHA DE INICIO desc'
        ];

        if (isset($options['fields'])) {
            $params['fields'] = $options['fields'];
        }

        return $this->datastoreSearch(
            $this->config->criticalResources['incendios'],
            $params
        );
    }

    /**
     * Lista todos los datasets
     *
     * @param bool $includePrivate Incluir datasets privados
     * @return array|null
     */
    public function listDatasets(bool $includePrivate = true): ?array
    {
        $params = [
            'q' => '*:*',
            'rows' => 1000,
            'include_private' => $includePrivate ? 'true' : 'false'
        ];

        return $this->makeRequest('package_search', $params);
    }

    /**
     * Obtiene detalle de un dataset
     *
     * @param string $id UUID o name del dataset
     * @return array|null
     */
    public function getDataset(string $id): ?array
    {
        return $this->makeRequest('package_show', ['id' => $id]);
    }

    /**
     * Obtiene detalle de un recurso
     *
     * @param string $id UUID del recurso
     * @return array|null
     */
    public function getResource(string $id): ?array
    {
        return $this->makeRequest('resource_show', ['id' => $id]);
    }

    /**
     * Limpia toda la caché de CKAN
     *
     * @return bool
     */
    public function clearCache(): bool
    {
        return $this->cache->clean();
    }
}
```

---

### app/Controllers/Api/CkanController.php

```php
<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;
use App\Libraries\CkanApiClient;

/**
 * API Controller para frontend
 *
 * Proporciona endpoints seguros para que el frontend
 * consuma datos de CKAN sin exponer el token JWT
 */
class CkanController extends ResourceController
{
    protected $format = 'json';
    private CkanApiClient $ckan;

    public function __construct()
    {
        $this->ckan = new CkanApiClient();
    }

    /**
     * GET /api/ckan/municipios?provincia=PALENCIA
     */
    public function municipios()
    {
        $provincia = $this->request->getGet('provincia');

        if (!$provincia) {
            return $this->fail('Parámetro provincia requerido', 400);
        }

        $result = $this->ckan->getMunicipios($provincia);

        if (!$result) {
            return $this->failServerError('Error al obtener municipios');
        }

        return $this->respond([
            'success' => true,
            'data' => $result['records'],
            'total' => $result['total']
        ]);
    }

    /**
     * GET /api/ckan/poblacion?provincia=PALENCIA&year=2023
     */
    public function poblacion()
    {
        $provincia = $this->request->getGet('provincia');
        $year = $this->request->getGet('year');

        if (!$provincia) {
            return $this->fail('Parámetro provincia requerido', 400);
        }

        $result = $this->ckan->getPoblacion($provincia, $year ? (int)$year : null);

        if (!$result) {
            return $this->failServerError('Error al obtener población');
        }

        return $this->respond([
            'success' => true,
            'data' => $result['records'],
            'total' => $result['total']
        ]);
    }

    /**
     * GET /api/ckan/incendios?provincia=PALENCIA&limit=50
     */
    public function incendios()
    {
        $provincia = $this->request->getGet('provincia');
        $limit = $this->request->getGet('limit') ?? 100;

        if (!$provincia) {
            return $this->fail('Parámetro provincia requerido', 400);
        }

        $result = $this->ckan->getIncendios($provincia, ['limit' => (int)$limit]);

        if (!$result) {
            return $this->failServerError('Error al obtener incendios');
        }

        return $this->respond([
            'success' => true,
            'data' => $result['records'],
            'total' => $result['total']
        ]);
    }

    /**
     * POST /api/ckan/clear-cache
     */
    public function clearCache()
    {
        if ($this->ckan->clearCache()) {
            return $this->respond(['success' => true, 'message' => 'Caché limpiada']);
        }

        return $this->failServerError('Error al limpiar caché');
    }
}
```

---

### Rutas (app/Config/Routes.php)

```php
// API CKAN
$routes->group('api/ckan', ['namespace' => 'App\Controllers\Api'], function($routes) {
    $routes->get('municipios', 'CkanController::municipios');
    $routes->get('poblacion', 'CkanController::poblacion');
    $routes->get('incendios', 'CkanController::incendios');
    $routes->post('clear-cache', 'CkanController::clearCache');
});
```

---

## 📊 EJEMPLOS DE USO EN FRONTEND

### Ejemplo 1: Tabla de Municipios con DataTables

```javascript
$(document).ready(function() {
    $('#tabla_municipios').DataTable({
        ajax: {
            url: '/api/ckan/municipios?provincia=PALENCIA',
            dataSrc: 'data'
        },
        columns: [
            { data: 'Municipio', title: 'Municipio' },
            { data: 'Población', title: 'Población' },
            {
                data: 'Latitud',
                title: 'Coordenadas',
                render: function(data, type, row) {
                    return `${row.Latitud}, ${row.Longitud}`;
                }
            }
        ],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        pageLength: 25,
        order: [[1, 'desc']] // Ordenar por población desc
    });
});
```

---

### Ejemplo 2: Gráfico de Evolución Poblacional con Chart.js

```javascript
fetch('/api/ckan/poblacion?provincia=PALENCIA')
    .then(r => r.json())
    .then(response => {
        // Agrupar por año y sexo
        const dataPorAnio = {};

        response.data.forEach(record => {
            const year = record.FECHA;
            if (!dataPorAnio[year]) {
                dataPorAnio[year] = { hombres: 0, mujeres: 0 };
            }

            if (record.SEXO === 'VARON') {
                dataPorAnio[year].hombres = record.POBLACIÓN;
            } else {
                dataPorAnio[year].mujeres = record.POBLACIÓN;
            }
        });

        // Preparar datos para Chart.js
        const years = Object.keys(dataPorAnio).sort();
        const hombres = years.map(y => dataPorAnio[y].hombres);
        const mujeres = years.map(y => dataPorAnio[y].mujeres);

        // Crear gráfico
        new Chart(document.getElementById('grafico_poblacion'), {
            type: 'line',
            data: {
                labels: years,
                datasets: [
                    {
                        label: 'Hombres',
                        data: hombres,
                        borderColor: '#3498db',
                        fill: false
                    },
                    {
                        label: 'Mujeres',
                        data: mujeres,
                        borderColor: '#e74c3c',
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Evolución Poblacional - Palencia (1986-2023)'
                    }
                }
            }
        });
    });
```

---

### Ejemplo 3: Mapa de Incendios con Leaflet

```javascript
// Inicializar mapa
const map = L.map('mapa_incendios').setView([42.0, -4.5], 9);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
}).addTo(map);

// Obtener incendios
fetch('/api/ckan/incendios?provincia=PALENCIA&limit=500')
    .then(r => r.json())
    .then(response => {
        response.data.forEach(incendio => {
            // Parsear coordenadas desde campo "Posición" (formato: "lat, lon")
            if (incendio.Posición) {
                const coords = incendio.Posición.split(',').map(c => parseFloat(c.trim()));

                if (coords.length === 2 && !isNaN(coords[0]) && !isNaN(coords[1])) {
                    const [lat, lon] = coords;

                    // Determinar color por nivel
                    const nivel = parseInt(incendio['NIVEL MÁXIMO ALCANZADO']) || 0;
                    const color = nivel >= 2 ? 'red' : nivel === 1 ? 'orange' : 'yellow';

                    // Crear marcador
                    L.circleMarker([lat, lon], {
                        radius: 5 + (nivel * 2),
                        fillColor: color,
                        color: '#000',
                        weight: 1,
                        fillOpacity: 0.7
                    })
                    .bindPopup(`
                        <b>${incendio['TÉRMINO MUNICIPAL']}</b><br>
                        Fecha: ${incendio['FECHA DE INICIO']}<br>
                        Nivel: ${nivel}<br>
                        Superficie: ${incendio['TIPO Y HAS DE SUPERFICIE AFECTADA']}
                    `)
                    .addTo(map);
                }
            }
        });
    });
```

---

## 🚨 MANEJO DE ERRORES

### Tipos de Errores Esperados

| Código HTTP | Tipo | Causa | Acción |
|-------------|------|-------|--------|
| 200 | Success | Consulta exitosa | Procesar datos |
| 400 | Bad Request | Parámetros inválidos | Validar inputs |
| 401 | Unauthorized | Token inválido/faltante | Verificar config |
| 404 | Not Found | Resource ID inexistente | Verificar UUID |
| 409 | Conflict | Endpoint no disponible | Usar alternativa |
| 500 | Server Error | Error interno CKAN | Retry con backoff |
| 503 | Unavailable | Servidor caído | Fallback a caché |

### Estrategia de Manejo

```php
try {
    $result = $this->ckan->getMunicipios('PALENCIA');

    if ($result === null) {
        // Intentar caché como fallback
        $cached = $this->getCachedMunicipios();

        if ($cached) {
            log_message('warning', 'CKAN API down, using cache');
            return $cached;
        }

        // Sin caché disponible
        throw new \RuntimeException('CKAN API no disponible');
    }

    return $result;

} catch (\Exception $e) {
    log_message('error', 'Error crítico CKAN: ' . $e->getMessage());

    // Retornar datos vacíos con mensaje de error
    return [
        'success' => false,
        'error' => 'Servicio temporalmente no disponible',
        'data' => []
    ];
}
```

---

## ⚡ OPTIMIZACIÓN Y MEJORES PRÁCTICAS

### 1. Uso de Caché (OBLIGATORIO)

**❌ Sin caché:**
```php
// Cada request va al servidor CKAN = lento
$municipios = $ckan->getMunicipios('PALENCIA'); // 416ms
$municipios = $ckan->getMunicipios('PALENCIA'); // 416ms
$municipios = $ckan->getMunicipios('PALENCIA'); // 416ms
```

**✅ Con caché:**
```php
// Primera: va a CKAN
$municipios = $ckan->getMunicipios('PALENCIA'); // 416ms

// Siguientes: desde caché
$municipios = $ckan->getMunicipios('PALENCIA'); // <5ms
$municipios = $ckan->getMunicipios('PALENCIA'); // <5ms
```

---

### 2. Selección de Campos (Reduce Payload)

**❌ Traer todos los campos:**
```php
// Payload: ~50 KB
$params = ['limit' => 100];
$result = $ckan->datastoreSearch($resourceId, $params);
```

**✅ Solo campos necesarios:**
```php
// Payload: ~15 KB (70% menos)
$params = [
    'fields' => 'Municipio,Población',
    'limit' => 100
];
$result = $ckan->datastoreSearch($resourceId, $params);
```

---

### 3. Límites Razonables

**❌ Traer todos los registros:**
```php
// 20,608 registros = timeout probable
$params = ['limit' => 99999];
```

**✅ Paginación adecuada:**
```php
// 100 registros = óptimo
$params = [
    'limit' => 100,
    'offset' => 0
];
```

---

### 4. Filtros vs Búsqueda

**Búsqueda (menos preciso):**
```php
// Busca "PALENCIA" en TODOS los campos
$params = ['q' => 'PALENCIA'];
```

**Filtros (más preciso):**
```php
// Busca solo en campo "Provincia"
$params = ['filters' => json_encode(['Provincia' => 'PALENCIA'])];
```

**Recomendación:** Usar filtros siempre que sea posible

---

### 5. Manejo de Coordenadas

**Problema:** El campo "Posición" es texto: `"42.007837, -4.534601"`

**Solución PHP:**
```php
function parseCoordinates(string $position): ?array {
    if (empty($position)) {
        return null;
    }

    $coords = explode(',', $position);

    if (count($coords) !== 2) {
        return null;
    }

    $lat = floatval(trim($coords[0]));
    $lon = floatval(trim($coords[1]));

    // Validar rangos para España
    if ($lat < 36 || $lat > 44 || $lon < -10 || $lon > 5) {
        return null;
    }

    return ['lat' => $lat, 'lon' => $lon];
}
```

---

## 📝 CHECKLIST DE INTEGRACIÓN

### Pre-Integración

- [ ] Leer esta documentación completa
- [ ] Revisar REPORTE_FINAL.md
- [ ] Entender limitaciones y workarounds
- [ ] Tener acceso al token JWT

### Configuración

- [ ] Crear `app/Config/Ckan.php`
- [ ] Configurar token JWT (sin exponer en frontend)
- [ ] Configurar base URL (dev vs producción)
- [ ] Configurar caché (habilitar, TTL)
- [ ] Configurar retry logic

### Implementación

- [ ] Crear `app/Libraries/CkanApiClient.php`
- [ ] Implementar métodos críticos (municipios, población, incendios)
- [ ] Crear `app/Controllers/Api/CkanController.php`
- [ ] Configurar rutas en `Routes.php`
- [ ] Probar cada endpoint individualmente

### Testing

- [ ] Test: Obtener municipios de Palencia (191 esperados)
- [ ] Test: Obtener población 2023 (2 registros esperados)
- [ ] Test: Obtener incendios (>1000 esperados)
- [ ] Test: Manejo de errores (ID inválido)
- [ ] Test: Performance con caché (debe ser <10ms)
- [ ] Test: Timeout (debe reintentar)

### Frontend

- [ ] Crear selectores de filtros (provincia, municipio, año)
- [ ] Implementar tabla de municipios
- [ ] Implementar gráfico de población
- [ ] Implementar mapa de incendios
- [ ] Agregar mensajes de error user-friendly
- [ ] Agregar loaders durante consultas

### Producción

- [ ] Cambiar base URL a producción (sin 'dev.')
- [ ] Verificar que token funciona en producción
- [ ] Configurar logs de errores
- [ ] Configurar monitoreo de latencia
- [ ] Documentar para equipo
- [ ] Plan de contingencia si CKAN cae

---

## 🔍 TROUBLESHOOTING

### Problema: "CKAN API no responde"

**Síntomas:**
- Timeout en requests
- HTTP 503

**Soluciones:**
1. Verificar conectividad de red
2. Ping al servidor: `ping ckan.dev.dipupalenciaindicadores.secmotic.com`
3. Verificar firewall
4. Consultar con administrador del servidor

---

### Problema: "Token inválido (401)"

**Síntomas:**
- HTTP 401 en todos los requests
- Mensaje: "Unauthorized"

**Soluciones:**
1. Verificar que el token está correcto en config
2. Verificar que se está enviando en header "Authorization"
3. Consultar con cliente si token expiró
4. Solicitar nuevo token

---

### Problema: "Recurso no tiene datastore activo (409)"

**Síntomas:**
- HTTP 409 al llamar datastore_search
- Solo algunos recursos fallan

**Solución:**
- Ese recurso NO es consultable vía API
- Usar solo los 47 recursos con `datastore_active: true`
- Ver lista en: `test_api_ckan/resultados/recursos_consultables.json`

---

### Problema: "Queries muy lentas (>2 segundos)"

**Causas posibles:**
1. Sin caché habilitado
2. Trayendo demasiados registros (limit muy alto)
3. Trayendo todos los campos (no usando `fields`)
4. Servidor CKAN con carga alta

**Soluciones:**
1. Habilitar caché (TTL: 1 hora)
2. Usar limit=100 máximo
3. Especificar solo campos necesarios
4. Verificar carga del servidor con cliente

---

### Problema: "Coordenadas inválidas en mapa"

**Síntomas:**
- Marcadores fuera de España
- NaN en coordenadas

**Solución:**
```javascript
// Validar antes de agregar al mapa
const coords = parseCoordinates(record.Posición);
if (coords && coords.lat >= 36 && coords.lat <= 44) {
    L.marker([coords.lat, coords.lon]).addTo(map);
}
```

---

## 📞 CONTACTO Y SOPORTE

### Documentos de Referencia

1. **Esta documentación** - Guía completa de integración
2. **REPORTE_FINAL.md** - Resultados de testing
3. **PLAN_TESTING_CKAN.md** - Plan de pruebas
4. **ANALISIS_TECNICO_DATASTORE.md** - Análisis profundo de consultas

### Archivos de Testing

```
test_api_ckan/resultados/
├── conectividad.json           ← Test de conectividad
├── datasets_completos.json     ← Catálogo completo
├── recursos_consultables.json  ← Lista de 47 recursos API
├── datastore_search.json       ← Ejemplos de consultas
└── spatial_filter.json         ← Filtrado geográfico
```

### Contacto con Cliente

**Para consultas sobre:**
- Datasets faltantes (3 de 23)
- Renovación de token JWT
- Acceso a producción
- Permisos adicionales

**Contactar:** Diputación de Palencia

---

## 📜 HISTORIAL DE CAMBIOS

### Versión 1.0.0 - 21/10/2025

- ✅ Documentación inicial completa
- ✅ Testing de 6 endpoints principales
- ✅ Validación de 47 recursos consultables
- ✅ Implementación de CkanApiClient
- ✅ Ejemplos de uso en frontend
- ✅ Troubleshooting y mejores prácticas

---

## ⚖️ LICENCIA Y USO

**Uso:** Esta documentación es para uso interno del proyecto Dashboard Dinámico - Bosque Modelo Palencia.

**Restricciones:**
- NO distribuir el token JWT
- NO exponer credenciales en repositorios públicos
- NO hacer scraping masivo de datos

**Cliente:** Diputación de Palencia
**Proyecto:** Bosque Modelo Palencia
**Equipo de Desarrollo:** Dashboard Dinámico Ambiental

---

**FIN DE LA DOCUMENTACIÓN**

**Última actualización:** 21 de Octubre de 2025
**Versión:** 1.0.0
**Estado:** ✅ Validado y listo para producción

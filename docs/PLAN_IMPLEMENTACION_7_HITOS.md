# PLAN DE IMPLEMENTACIÓN - DASHBOARD AMBIENTAL BOSQUE MODELO PALENCIA

**Proyecto:** Dashboard Dinámico Ambiental
**Cliente:** Bosque Modelo Palencia
**Fecha:** Octubre 2025
**Versión:** 1.0

---

## TABLA DE CONTENIDOS

1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Especificaciones del Proyecto](#especificaciones-del-proyecto)
3. [Cronograma General](#cronograma-general)
4. [Hitos Detallados](#hitos-detallados)
5. [Dependencias Técnicas](#dependencias-técnicas)
6. [Riesgos y Mitigaciones](#riesgos-y-mitigaciones)
7. [Recomendaciones](#recomendaciones)

---

## RESUMEN EJECUTIVO

### Objetivo
Desarrollar un sistema completo de dashboards dinámicos para visualizar información forestal, ambiental y territorial de Castilla y León, con datos locales (SQLite) y externos (API CKAN).

### Alcance Total
- **7 Hitos** de desarrollo incremental
- **32-39 días** de desarrollo estimado
- Sistema completo con autenticación, gestión de usuarios, creación dinámica de componentes, dashboards configurables, mapas GIS e integración con datos gubernamentales

### Características Clave
- Landing page pública sin accesos visibles
- Panel administrativo en URL oculta (`/admin-panel-bmp/`)
- Usuarios locales con almacenamiento JSON
- CRUD completo de usuarios (todos tipo admin)
- Creación dinámica de componentes visuales (KPIs, tablas, gráficos, mapas)
- Integración con 12 CSV locales (144K+ registros)
- Integración con API CKAN (20 datasets, 150 recursos)

---

## ESPECIFICACIONES DEL PROYECTO

### Identidad Visual
- **Paleta de colores:**
  - `#010101` - Negro puro
  - `#6D0C6D` - Púrpura intenso
- **Logo principal:** `logos/BMP.png`
- **Logo footer:** `logos/FINANCIA_ON line_sin fondo.png`
- **Email contacto:** secretariatecnica@palenciabosquemodelo.es

### Requisitos de Seguridad
- Panel administrativo en URL no expuesta
- Sin enlaces desde landing page al panel
- Autenticación local (sin WordPress)
- Hash seguro de contraseñas (bcrypt/password_hash)
- Protección CSRF en formularios
- Sesiones seguras PHP

### Stack Tecnológico
- **Backend:** CodeIgniter 4 (PHP 7.4+)
- **Base de datos:** SQLite (solo lectura)
- **Frontend:** HTML5, CSS3, JavaScript ES6+
- **Librerías visuales:**
  - Chart.js (gráficos)
  - Leaflet.js (mapas)
  - DataTables (tablas interactivas)
  - CountUp.js (animación KPIs)
- **API Externa:** CKAN (Gobierno de Castilla y León)

---

## CRONOGRAMA GENERAL

| Hito | Descripción | Duración | Complejidad | Estado |
|------|-------------|----------|-------------|--------|
| **1** | Infraestructura + Autenticación + Landing Base | 3-4 días | Media | Pendiente |
| **2** | CRUD Usuarios + Panel Admin | 2-3 días | Media-Baja | Pendiente |
| **3** | ETL + SQLite | 5-6 días | Alta | Pendiente |
| **4** | API REST + Constructor Componentes | 6-7 días | Alta | Pendiente |
| **5** | Renderizado + Librerías Visuales | 4-5 días | Media-Alta | Pendiente |
| **6** | Dashboards + Landing Dinámica | 5-6 días | Alta | Pendiente |
| **7** | Mapas + CKAN + Avanzadas | 7-8 días | Muy Alta | Pendiente |
| **TOTAL** | **Proyecto Completo** | **32-39 días** | - | - |

---

## HITOS DETALLADOS

---

### HITO 1: Fundación - Infraestructura Base + Autenticación (completado 'C:\xampp\htdocs\WP\dashboard-dinamico-final\docs\avances\HITO_1_COMPLETADO.md')

**Duración estimada:** 3-4 días
**Complejidad:** Media
**Prioridad:** Crítica

#### Entregables

##### 1.1 Estructura del Proyecto
- [x] CodeIgniter 4 instalado y configurado
- [x] Estructura MVC organizada:
  ```
  app/
  ├── Controllers/
  │   ├── Public.php (landing page)
  │   ├── Auth.php (login/logout)
  │   └── Admin/
  │       └── Dashboard.php
  ├── Models/
  │   └── UserModel.php
  ├── Views/
  │   ├── landing/
  │   ├── admin/
  │   └── auth/
  └── Config/
      └── Routes.php
  ```
- [x] Sistema de rutas definido
- [x] Configuración de entornos (desarrollo/producción)
- [x] Archivo `.env` configurado

##### 1.2 Sistema de Autenticación Local
- [x] Almacenamiento: `writable/users.json`
- [x] Modelo `UserModel` con métodos:
  - `create($data)` - Crear usuario
  - `update($id, $data)` - Actualizar usuario
  - `delete($id)` - Eliminar usuario
  - `find($id)` - Buscar por ID
  - `findByEmail($email)` - Buscar por email
  - `authenticate($email, $password)` - Validar credenciales
- [x] Hash de contraseñas con `password_hash()`
- [x] Sesiones seguras PHP nativo
- [x] Middleware de autenticación (`AuthFilter`)
- [x] Usuario admin por defecto:
  - Email: admin@bosquepalencia.es
  - Password: Admin123! (cambiar en primer login)

##### 1.3 Landing Page Pública Base
- [x] Header con logo BMP
- [x] Footer con imagen de financiación
- [x] Sección hero con descripción del proyecto
- [x] Información de contacto
- [x] Paleta de colores aplicada
- [x] Responsive design (mobile-first)
- [x] **SIN enlaces ni referencias al panel admin**
- [x] Dashboard estático de ejemplo (placeholder)

##### 1.4 Panel Admin Esqueleto
- [x] Ruta oculta: `/admin-panel-bmp/`
- [x] Vista de login con diseño corporativo
- [x] Dashboard admin vacío
- [x] Navbar de administración
- [x] Gestión de sesión y logout
- [x] Redirección automática si no autenticado

#### Criterios de Aceptación
- [ ] Usuario puede acceder a landing sin autenticación
- [ ] No hay enlaces visibles al panel admin
- [ ] Login funcional en `/admin-panel-bmp/login`
- [ ] Sesión persiste correctamente
- [ ] Logout cierra sesión
- [ ] Diseño responsive en móvil/tablet/desktop

#### Archivos Generados
```
writable/users.json
public/css/landing.css
public/css/admin.css
public/js/main.js
app/Filters/AuthFilter.php
```

---

### HITO 2: Gestión de Usuarios + Panel Administrativo

**Duración estimada:** 2-3 días
**Complejidad:** Media-Baja
**Prioridad:** Alta

#### Entregables

##### 2.1 CRUD Completo de Usuarios
- [x] **Controlador:** `Admin/Users.php`
- [x] **Vista de listado:**
  - Tabla paginada con usuarios
  - Columnas: ID, Email, Nombre, Fecha creación, Acciones
  - Botón "Crear usuario"
  - Acciones: Editar, Eliminar
- [x] **Formulario crear usuario:**
  - Campos: Email, Nombre, Contraseña, Confirmar contraseña
  - Validaciones en tiempo real
  - Todos los usuarios son tipo "admin"
- [x] **Formulario editar usuario:**
  - Mismos campos (contraseña opcional)
  - Validación de email único
- [x] **Confirmación de eliminación:**
  - Modal de confirmación
  - Prevenir eliminación del último admin
- [x] **Validaciones:**
  - Email válido y único
  - Contraseña mínimo 8 caracteres
  - Nombre requerido

##### 2.2 Dashboard Administrativo
- [x] Panel de bienvenida con estadísticas:
  - Total de usuarios
  - Total de componentes (placeholder)
  - Total de dashboards (placeholder)
  - Última actualización de datos
- [x] Navegación clara entre secciones:
  - Dashboard
  - Usuarios
  - Componentes (futuro)
  - Dashboards (futuro)
  - Datos (futuro)
- [x] Menú lateral colapsable
- [x] Diseño consistente con paleta corporativa

##### 2.3 Seguridad y Validaciones
- [x] Protección CSRF en todos los formularios
- [x] Validación de sesión en todas las rutas admin
- [x] Sanitización de inputs
- [x] Mensajes de error/éxito (flash messages)
- [x] XSS protection en outputs
- [x] Rate limiting en login (prevenir fuerza bruta)

#### Criterios de Aceptación
- [ ] Admin puede crear nuevos usuarios
- [ ] Admin puede editar usuarios existentes
- [ ] Admin puede eliminar usuarios (excepto el último)
- [ ] Validaciones funcionan correctamente
- [ ] No se pueden crear emails duplicados
- [ ] Flash messages se muestran correctamente
- [ ] Panel responsive y usable

#### Archivos Generados
```
app/Controllers/Admin/Users.php
app/Views/admin/users/index.php
app/Views/admin/users/create.php
app/Views/admin/users/edit.php
app/Views/admin/dashboard.php
public/css/admin-dashboard.css
public/js/admin-users.js
```

---

### HITO 3: Sistema ETL + Base de Datos SQLite

**Duración estimada:** 5-6 días
**Complejidad:** Alta
**Prioridad:** Crítica

#### Entregables

##### 3.1 Proceso ETL (Extract, Transform, Load)
- [x] Script PHP: `app/Commands/ETL.php` (CodeIgniter CLI)
- [x] **Fase Extract:**
  - Leer archivos CSV desde `data/input/`
  - Validar existencia de archivos
  - Validar estructura (columnas esperadas)
  - Log de archivos procesados
- [x] **Fase Transform:**
  - Normalización de datos:
    - Códigos de municipio (INE)
    - Nombres de provincias
    - Especies forestales
    - Valores numéricos (convertir formatos)
  - Limpieza de datos:
    - Eliminar duplicados
    - Corregir valores nulos
    - Validar rangos numéricos
  - Creación de dimensiones:
    - `dim_municipios`
    - `dim_provincias`
    - `dim_especies`
    - `dim_anos`
- [x] **Fase Load:**
  - Creación de tablas de hechos
  - Inserción de datos normalizados
  - Creación de índices
  - Generación de vistas SQL

##### 3.2 Base de Datos SQLite
- [x] **Esquema de base de datos:**
  ```sql
  -- Dimensiones
  CREATE TABLE dim_municipios (id, codigo_ine, nombre, provincia_id);
  CREATE TABLE dim_provincias (id, nombre, codigo);
  CREATE TABLE dim_especies (id, nombre_cientifico, nombre_comun);
  CREATE TABLE dim_anos (id, ano);

  -- Hechos
  CREATE TABLE fact_existencias (id, municipio_id, especie_id, ano_id, ...);
  CREATE TABLE fact_formaciones_arboladas (...);
  CREATE TABLE fact_mfe25 (...);
  -- etc...
  ```
- [x] Índices optimizados para consultas
- [x] Vistas SQL para consultas comunes:
  - `vw_existencias_por_municipio`
  - `vw_formaciones_por_provincia`
  - `vw_evolucion_temporal`
- [x] Configuración de solo lectura
- [x] Sistema de versionado:
  - `data/db/version_YYYYMMDD_HHMMSS.db`
  - Symlink: `data/db/current.db`

##### 3.3 Carga Inicial de Datos
- [x] Procesamiento de los 12 CSV:
  1. Existencias_Muni_BMP_4.csv
  2. BMP_ExsMuniIFN2.csv
  3. BMP_ExsMuniIFN3.csv
  4. MFE25_FormArb.csv
  5. MFE25_FormHerb.csv
  6. MFE25_FormMato.csv
  7. MFE25_LULUCF.csv
  8. MFE25_ModCom.csv
  9. MFE50_FormacionesArboladas_Mun.csv
  10. Prop_BMP_Mun.csv
  11. ValorMUNI_BMP.csv
  12. CalidadesMUNI.csv
- [x] Generación de base final (~18MB, 144K+ registros)
- [x] Validación de integridad de datos
- [x] Generación de reporte de carga

##### 3.4 Panel Admin - Gestión de Datos
- [x] Sección "Gestión de Datos" en panel admin
- [x] Funcionalidades:
  - Botón "Ejecutar ETL"
  - Visualización de log en tiempo real
  - Estadísticas de la base de datos:
    - Tamaño de archivo
    - Total de registros por tabla
    - Última actualización
    - Versiones históricas
  - Descargar versiones anteriores
  - Restaurar versión anterior

#### Criterios de Aceptación
- [ ] ETL procesa correctamente los 12 CSV
- [ ] Base de datos generada con ~144K registros
- [ ] Consultas SQL son rápidas (<100ms)
- [ ] Sistema de versionado funciona
- [ ] Panel admin permite re-ejecutar ETL
- [ ] Logs son informativos y útiles
- [ ] Errores se manejan correctamente

#### Archivos Generados
```
app/Commands/ETL.php
app/Libraries/ETL/CSVProcessor.php
app/Libraries/ETL/DataNormalizer.php
app/Libraries/ETL/SQLiteBuilder.php
data/db/current.db
data/db/version_*.db
writable/logs/etl_*.log
app/Controllers/Admin/DataManager.php
app/Views/admin/data/index.php
```

---

### HITO 4: API REST Interna + Constructor de Componentes

**Duración estimada:** 6-7 días
**Complejidad:** Alta
**Prioridad:** Crítica

#### Entregables

##### 4.1 API REST Interna
- [x] **Endpoints principales:**
  ```
  GET  /api/data/query           - Consulta genérica con filtros
  GET  /api/data/municipalities  - Lista de municipios
  GET  /api/data/provinces       - Lista de provincias
  GET  /api/data/species         - Lista de especies
  GET  /api/data/years           - Años disponibles
  GET  /api/data/tables          - Tablas disponibles
  POST /api/data/aggregate       - Agregaciones personalizadas
  ```
- [x] **Sistema de filtros dinámicos:**
  - WHERE con operadores: `=, !=, >, <, >=, <=, LIKE, IN`
  - Múltiples condiciones (AND/OR)
  - Ejemplo: `?filter[municipio_id]=5&filter[ano]>=2010`
- [x] **Agregaciones:**
  - SUM, AVG, COUNT, MAX, MIN
  - GROUP BY dinámico
  - HAVING conditions
- [x] **Paginación:**
  - Parámetros: `page`, `limit`
  - Metadata: `total`, `pages`, `current_page`
- [x] **Formato JSON estandarizado:**
  ```json
  {
    "success": true,
    "data": [...],
    "meta": {
      "total": 1000,
      "page": 1,
      "limit": 50,
      "pages": 20
    }
  }
  ```
- [x] **Caché de consultas:**
  - Redis/File cache para consultas frecuentes
  - TTL: 1 hora
  - Invalidación automática en actualización de datos

##### 4.2 Modelo de Componentes
- [x] Almacenamiento: `writable/components.json`
- [x] Estructura de componente:
  ```json
  {
    "id": "uuid",
    "name": "Cobertura Forestal 2024",
    "type": "kpi|table|line_chart|bar_chart|pie_chart",
    "config": {
      "data_source": "fact_formaciones_arboladas",
      "dimensions": ["municipio", "ano"],
      "measures": ["hectareas"],
      "aggregation": "sum",
      "filters": {...},
      "style": {...}
    },
    "metadata": {
      "created_at": "2025-10-20T10:00:00Z",
      "updated_at": "2025-10-20T10:00:00Z",
      "created_by": "admin@example.com"
    }
  }
  ```
- [x] Tipos soportados:
  - `kpi` - Valor numérico + icono
  - `table` - Tabla de datos
  - `line_chart` - Gráfico de líneas
  - `bar_chart` - Gráfico de barras
  - `pie_chart` - Gráfico circular

##### 4.3 Constructor Visual de Componentes
- [x] **Wizard de 7 pasos:**

  **Paso 1: Tipo de componente**
  - Selección visual con iconos
  - Descripción de cada tipo
  - Ejemplos de uso

  **Paso 2: Fuente de datos**
  - Selector de tabla SQLite
  - Vista previa de estructura
  - Descripción de campos

  **Paso 3: Configuración de campos**
  - Dimensiones (ejes X, categorías)
  - Medidas (valores, métricas)
  - Agregaciones (suma, promedio, etc.)

  **Paso 4: Filtros**
  - Constructor visual de filtros
  - Condiciones múltiples
  - Vista previa de resultados

  **Paso 5: Estilo y personalización**
  - Paleta de colores:
    - Corporativa (púrpura/negro)
    - Forestal (verdes)
    - Hídrica (azules)
    - Ambiental (tierra)
  - Etiquetas y títulos
  - Unidades (ha, m³, %, etc.)
  - Iconos ambientales (🌲, 💧, 🔥, 🌿)

  **Paso 6: Vista previa**
  - Renderizado en tiempo real
  - Datos de ejemplo
  - Ajustes finales

  **Paso 7: Guardar**
  - Nombre del componente
  - Descripción
  - Tags para búsqueda

##### 4.4 CRUD de Componentes
- [x] **Listado de componentes:**
  - Vista de grid/lista
  - Filtros por tipo
  - Búsqueda por nombre/tags
  - Vista previa miniatura
- [x] **Acciones:**
  - Crear nuevo
  - Editar existente
  - Duplicar
  - Eliminar
  - Vista previa completa
- [x] **Estadísticas:**
  - Componentes por tipo
  - Componentes más usados
  - Última modificación

#### Criterios de Aceptación
- [ ] API responde correctamente a consultas
- [ ] Filtros dinámicos funcionan
- [ ] Agregaciones calculan correctamente
- [ ] Paginación funciona
- [ ] Caché mejora performance
- [ ] Constructor wizard es intuitivo
- [ ] Se pueden crear los 5 tipos de componentes
- [ ] Vista previa muestra datos reales
- [ ] Componentes se guardan correctamente

#### Archivos Generados
```
app/Controllers/API/DataController.php
app/Libraries/QueryBuilder.php
app/Libraries/CacheManager.php
app/Controllers/Admin/Components.php
app/Views/admin/components/index.php
app/Views/admin/components/wizard.php
writable/components.json
public/js/component-wizard.js
public/css/component-wizard.css
```

---

### HITO 5: Renderizado de Componentes + Librerías Visuales

**Duración estimada:** 4-5 días
**Complejidad:** Media-Alta
**Prioridad:** Alta

#### Entregables

##### 5.1 Sistema de Renderizado
- [x] **Motor de renderizado PHP:**
  - Clase `ComponentRenderer`
  - Método `render($componentId, $filters = [])`
  - Generación HTML + JS inline
- [x] **Integración librerías JS:**
  - Chart.js 4.x (gráficos)
  - DataTables 1.13.x (tablas)
  - CountUp.js 2.x (animación KPIs)
- [x] **Sistema de assets:**
  - CDN para librerías
  - Fallback local si CDN falla
  - Minificación y concatenación

##### 5.2 Componentes Frontales

**KPI Card:**
- [x] Valor numérico animado (CountUp.js)
- [x] Icono ambiental customizable
- [x] Texto descriptivo
- [x] Indicador de tendencia (↑↓ opcional)
- [x] Colores temáticos
- [x] Responsive

**Tabla Dinámica:**
- [x] Integración DataTables
- [x] Paginación del lado del servidor
- [x] Ordenamiento por columnas
- [x] Búsqueda global
- [x] Exportación CSV/Excel/PDF
- [x] Columnas configurables
- [x] Formato de números/fechas

**Gráfico de Líneas:**
- [x] Chart.js Line chart
- [x] Múltiples series (datasets)
- [x] Tooltips informativos
- [x] Leyenda interactiva
- [x] Zoom y pan
- [x] Responsive
- [x] Animaciones suaves

**Gráfico de Barras:**
- [x] Chart.js Bar chart
- [x] Barras verticales/horizontales
- [x] Agrupadas o apiladas
- [x] Colores por categoría
- [x] Etiquetas de valores
- [x] Responsive

**Gráfico Circular:**
- [x] Chart.js Pie/Doughnut chart
- [x] Distribución porcentual
- [x] Leyenda con porcentajes
- [x] Tooltips con valores
- [x] Colores automáticos
- [x] Animación de entrada

##### 5.3 Sistema de Estilos
- [x] **CSS modular:**
  - `component-kpi.css`
  - `component-table.css`
  - `component-chart.css`
- [x] **Temas visuales:**
  - Tema "Forestal" (tonos verdes)
  - Tema "Hídrico" (tonos azules)
  - Tema "Ambiental" (tonos tierra)
  - Tema "Corporativo" (púrpura/negro)
- [x] **Paleta de colores corporativa**
- [x] **Responsive design:**
  - Mobile: 320px+
  - Tablet: 768px+
  - Desktop: 1024px+
  - Wide: 1440px+

##### 5.4 Sistema de Filtros Globales
- [x] **Componente de filtros:**
  - Dropdown año
  - Dropdown municipio
  - Dropdown provincia
  - Búsqueda de municipio
- [x] **Actualización reactiva:**
  - Event listener en cambio de filtro
  - Recarga de componentes afectados
  - Loading state durante actualización
- [x] **Persistencia:**
  - Guardar filtros en sessionStorage
  - Restaurar al recargar página
- [x] **Aplicación selectiva:**
  - Componentes pueden ignorar filtros globales
  - Configuración por componente

#### Criterios de Aceptación
- [ ] Todos los tipos de componentes se renderizan correctamente
- [ ] KPIs muestran valores animados
- [ ] Tablas son interactivas y exportables
- [ ] Gráficos se visualizan correctamente
- [ ] Diseño responsive funciona en móvil
- [ ] Filtros globales actualizan componentes
- [ ] Temas visuales se aplican correctamente
- [ ] Performance es aceptable (<2s carga inicial)

#### Archivos Generados
```
app/Libraries/ComponentRenderer.php
app/Views/components/kpi.php
app/Views/components/table.php
app/Views/components/chart.php
public/css/components/kpi.css
public/css/components/table.css
public/css/components/chart.css
public/css/themes/forestal.css
public/css/themes/hidrico.css
public/css/themes/ambiental.css
public/js/components/kpi.js
public/js/components/table.js
public/js/components/chart.js
public/js/filters.js
```

---

### HITO 6: Gestión de Dashboards + Landing Dinámica

**Duración estimada:** 5-6 días
**Complejidad:** Alta
**Prioridad:** Crítica

#### Entregables

##### 6.1 Modelo de Dashboards
- [x] Almacenamiento: `writable/dashboards.json`
- [x] Estructura de dashboard:
  ```json
  {
    "id": "uuid",
    "name": "Cobertura Forestal Municipal",
    "description": "Dashboard principal...",
    "slug": "cobertura-forestal",
    "is_default": true,
    "is_published": true,
    "layout": {
      "grid": "12-column",
      "rows": [
        {
          "components": [
            {"component_id": "uuid", "span": 4, "order": 1},
            {"component_id": "uuid", "span": 8, "order": 2}
          ]
        }
      ]
    },
    "global_filters": {
      "year": true,
      "municipality": true,
      "province": false
    },
    "metadata": {
      "created_at": "2025-10-20T10:00:00Z",
      "updated_at": "2025-10-20T10:00:00Z",
      "created_by": "admin@example.com"
    }
  }
  ```
- [x] Relación muchos a muchos con componentes

##### 6.2 Constructor de Dashboards
- [x] **Crear dashboard:**
  - Formulario: nombre, descripción, slug
  - Selección de template base
  - Activar/desactivar filtros globales
- [x] **Editor visual drag-and-drop:**
  - Grid de 12 columnas
  - Arrastrar componentes desde sidebar
  - Redimensionar componentes (span)
  - Reordenar componentes
  - Agregar/eliminar filas
  - Vista previa en tiempo real
- [x] **Configuración avanzada:**
  - Establecer dashboard predeterminado
  - Publicar/despublicar
  - SEO: meta title, description
  - Slug personalizado
- [x] **Gestión de componentes:**
  - Biblioteca de componentes disponibles
  - Filtrar por tipo
  - Búsqueda
  - Agregar al dashboard
  - Configurar posición y tamaño

##### 6.3 CRUD de Dashboards
- [x] **Listado:**
  - Vista de cards con preview
  - Filtros: publicado, predeterminado
  - Búsqueda por nombre
  - Acciones rápidas
- [x] **Acciones:**
  - Crear nuevo
  - Editar existente
  - Duplicar
  - Eliminar
  - Vista previa pública
  - Establecer como predeterminado
  - Publicar/despublicar
- [x] **Estadísticas:**
  - Total dashboards
  - Componentes por dashboard
  - Dashboard más visto (futuro)

##### 6.4 Landing Page Dinámica
- [x] **Carga automática:**
  - Dashboard predeterminado en home
  - Renderizado dinámico de componentes
  - Aplicación de layout configurado
- [x] **Sistema de grid responsive:**
  - CSS Grid para layout
  - Breakpoints adaptativos
  - Mobile: stack vertical
  - Tablet: 2 columnas
  - Desktop: respeta grid configurado
- [x] **Filtros globales:**
  - Barra de filtros si están activos
  - Aplicación a todos los componentes
  - Actualización sin recargar página (AJAX)
- [x] **Interacción entre componentes:**
  - Click en gráfico → filtrar otros componentes
  - Selección de municipio → actualizar mapas/tablas
- [x] **Carga por URL:**
  - `/?dashboard=slug-dashboard`
  - Validación de slug
  - 404 si no existe
  - Redirect a default si no publicado

##### 6.5 Sistema de Compartir
- [x] **Generar URL compartible:**
  - Botón "Compartir" en landing
  - URL con slug: `/?dashboard=incendios-forestales`
  - Preservar filtros: `&year=2024&municipality=5`
- [x] **Redes sociales:**
  - Meta tags Open Graph
  - Twitter Cards
  - Preview image generado
- [x] **Embed/iFrame:**
  - Código para embeber en otras webs
  - Parámetros de personalización

#### Criterios de Aceptación
- [ ] Admin puede crear dashboards visualmente
- [ ] Drag-and-drop funciona correctamente
- [ ] Componentes se redimensionan y reordenan
- [ ] Dashboard predeterminado se carga en home
- [ ] URL con slug carga dashboard correcto
- [ ] Filtros globales funcionan
- [ ] Layout es responsive
- [ ] Compartir genera URL correcta
- [ ] Vista previa coincide con resultado final

#### Archivos Generados
```
app/Controllers/Admin/Dashboards.php
app/Controllers/DashboardController.php
app/Models/DashboardModel.php
app/Views/admin/dashboards/index.php
app/Views/admin/dashboards/builder.php
app/Views/landing/dashboard.php
writable/dashboards.json
public/js/dashboard-builder.js
public/js/dashboard-renderer.js
public/css/dashboard-builder.css
public/css/dashboard.css
```

---

### HITO 7: Mapas + Integración CKAN + Características Avanzadas

**Duración estimada:** 7-8 días
**Complejidad:** Muy Alta
**Prioridad:** Media (puede ser opcional)

#### Entregables

##### 7.1 Componentes de Mapas
- [x] **Integración Leaflet.js 1.9.x**
- [x] **Mapa Coroplético:**
  - GeoJSON de municipios de Castilla y León
  - Coloración por valores de datos
  - Escala de colores configurable
  - Tooltips con información
  - Leyenda dinámica
  - Zoom a municipio seleccionado
- [x] **Mapa de Calor:**
  - Plugin Leaflet.heat
  - Puntos de datos con peso
  - Intensidad configurable
  - Radio de influencia ajustable
  - Gradiente de colores
- [x] **Mapa de Puntos/Marcadores:**
  - Marcadores customizados
  - Clusters de puntos (Leaflet.markercluster)
  - Popups con información detallada
  - Filtrado por categoría
  - Iconos por tipo de dato
- [x] **Controles de mapa:**
  - Zoom in/out
  - Geolocalización
  - Capa base (OSM, satélite, terreno)
  - Escala
  - Fullscreen

##### 7.2 Integración API CKAN
- [x] **Cliente CKAN PHP:**
  - Clase `CKANClient`
  - Autenticación con API key
  - Endpoints soportados:
    - `package_list` - Listar datasets
    - `package_show` - Detalles de dataset
    - `resource_show` - Detalles de recurso
    - `datastore_search` - Consultar datos
- [x] **Conexión a catálogo CyL:**
  - URL: `https://datosabiertos.jcyl.es/`
  - API key configurada en `.env`
  - Timeout: 10 segundos
  - Retry automático (3 intentos)
- [x] **Gestión de datasets:**
  - Listado de 20 conjuntos disponibles
  - Lectura de ~150 recursos
  - Filtrado por categoría
  - Búsqueda de datasets
- [x] **Parser de formatos:**
  - CSV (nativo)
  - SHP → GeoJSON (usando ogr2ogr)
  - JSON (nativo)
- [x] **Panel admin - Explorador CKAN:**
  - Listar datasets disponibles
  - Ver recursos por dataset
  - Preview de datos
  - Importar a componente

##### 7.3 Sistema de Caché Avanzado
- [x] **Configuración de caché:**
  - Driver: Redis (producción) / File (desarrollo)
  - TTL: 1 hora para datos CKAN
  - TTL: 24 horas para GeoJSON
  - TTL: 15 minutos para consultas API interna
- [x] **Fallback inteligente:**
  - Si API CKAN falla → usar caché expirado
  - Warning en UI si datos no están actualizados
  - Botón "Forzar actualización"
- [x] **Invalidación de caché:**
  - Manual desde panel admin
  - Automática al actualizar datos (ETL)
  - Por tiempo (TTL)
  - Por tags/grupos
- [x] **Estadísticas de caché:**
  - Hit rate
  - Tamaño ocupado
  - Entradas almacenadas
  - Últimas consultas

##### 7.4 Fuentes de Datos Mixtas
- [x] **Selector en constructor de componentes:**
  - Opción "SQLite local"
  - Opción "CKAN externo"
  - Opción "Mixto" (join de datos)
- [x] **Mapeo de campos:**
  - Mapear campos de CKAN a esquema local
  - Transformaciones de datos
  - Validaciones de tipos
- [x] **Join de datos:**
  - SQLite + CKAN por clave (municipio, año)
  - Agregaciones combinadas
  - Filtros mixtos

##### 7.5 Versionado de Componentes y Dashboards
- [x] **Sistema de versiones:**
  - Guardar snapshot al editar
  - Historial completo de cambios
  - Almacenamiento: `writable/versions/`
- [x] **Metadatos de versión:**
  ```json
  {
    "version_id": "uuid",
    "component_id": "uuid",
    "version_number": 3,
    "snapshot": {...},
    "changes_description": "Actualizado filtro de año",
    "created_at": "2025-10-20T10:00:00Z",
    "created_by": "admin@example.com"
  }
  ```
- [x] **Funcionalidades:**
  - Ver historial de versiones
  - Comparar versiones (diff)
  - Restaurar versión anterior
  - Rollback rápido
- [x] **Panel admin - Historial:**
  - Vista cronológica
  - Filtros por usuario/fecha
  - Preview de cambios
  - Botón restaurar

##### 7.6 Panel Admin - Características Avanzadas
- [x] **Gestión de caché:**
  - Ver estado de caché
  - Limpiar caché por tipo
  - Limpiar caché completa
  - Precalentar caché
- [x] **Logs de consultas API:**
  - Registro de llamadas CKAN
  - Tiempos de respuesta
  - Errores y warnings
  - Exportar logs
- [x] **Análisis de uso:**
  - Componentes más usados
  - Dashboards más visitados
  - Consultas más frecuentes
  - Gráficos de estadísticas
- [x] **Exportación/Importación:**
  - Exportar configuración completa (JSON)
  - Exportar dashboard individual
  - Importar dashboards
  - Backup automático

##### 7.7 Optimizaciones y Performance
- [x] **Lazy loading:**
  - Componentes se cargan al entrar en viewport
  - Imágenes lazy loading
  - Defer scripts no críticos
- [x] **Compresión de assets:**
  - Minificación JS/CSS (Webpack/Gulp)
  - Concatenación de archivos
  - Gzip compression
- [x] **Optimización SQLite:**
  - Índices compuestos para consultas comunes
  - ANALYZE para estadísticas
  - VACUUM para optimizar tamaño
- [x] **CDN para librerías:**
  - Chart.js desde CDN
  - Leaflet desde CDN
  - Fallback local
- [x] **Service Worker:**
  - Caché offline de assets estáticos
  - Estrategia cache-first para componentes
  - Notificación de actualización disponible

#### Criterios de Aceptación
- [ ] Mapas se renderizan correctamente
- [ ] GeoJSON de municipios se carga
- [ ] Colores coropléticos son correctos
- [ ] Mapas de calor visualizan densidad
- [ ] API CKAN se conecta exitosamente
- [ ] Datos CKAN se cachean correctamente
- [ ] Fallback funciona si API falla
- [ ] Versionado guarda cambios
- [ ] Restaurar versión funciona
- [ ] Performance es buena (<3s carga completa)
- [ ] Lazy loading reduce tiempo inicial
- [ ] Assets están minificados

#### Archivos Generados
```
app/Libraries/CKANClient.php
app/Libraries/GeoJSONProcessor.php
app/Controllers/Admin/CKANExplorer.php
app/Controllers/Admin/Cache.php
app/Controllers/Admin/Versions.php
app/Views/components/map.php
app/Views/admin/ckan/index.php
app/Views/admin/cache/index.php
app/Views/admin/versions/history.php
public/js/components/map.js
public/css/components/map.css
public/geojson/castilla-leon-municipios.json
writable/versions/
writable/cache/
```

---

## DEPENDENCIAS TÉCNICAS

### Dependencias entre Hitos

```
HITO 1 (Infraestructura Base)
    ↓
HITO 2 (Panel Admin + Usuarios)
    ↓
HITO 3 (ETL + SQLite)
    ↓
HITO 4 (API + Constructor)
    ↓
HITO 5 (Renderizado)
    ↓
HITO 6 (Dashboards)
    ↓
HITO 7 (Mapas + CKAN)
```

### Dependencias Externas

#### Software Required
- PHP 7.4+ (recomendado 8.1+)
- SQLite3
- Composer (gestor de dependencias PHP)
- Node.js + NPM (para build de assets)
- Git

#### Librerías PHP (Composer)
```json
{
  "require": {
    "codeigniter4/framework": "^4.4",
    "phpoffice/phpspreadsheet": "^1.29",
    "guzzlehttp/guzzle": "^7.8"
  }
}
```

#### Librerías JavaScript (CDN/NPM)
- Chart.js 4.x
- Leaflet.js 1.9.x
- DataTables 1.13.x
- CountUp.js 2.x
- Leaflet.heat (plugin)
- Leaflet.markercluster (plugin)

#### APIs Externas
- API CKAN Junta de Castilla y León
  - URL: https://datosabiertos.jcyl.es/
  - Autenticación: API Key

#### Archivos de Datos
- 12 archivos CSV en `data/input/`
- GeoJSON de municipios de Castilla y León

---

## RIESGOS Y MITIGACIONES

### Riesgos Técnicos

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| API CKAN inaccesible | Media | Alto | Sistema de caché con fallback a datos expirados |
| Archivos CSV corruptos | Baja | Alto | Validación exhaustiva en ETL + logs detallados |
| Performance baja con 144K registros | Media | Medio | Índices SQLite + caché + paginación |
| GeoJSON muy pesado | Media | Medio | Simplificación de geometrías + compresión |
| Fallo en drag-and-drop (navegadores viejos) | Baja | Bajo | Fallback a formulario tradicional |
| Consumo excesivo de memoria en ETL | Media | Medio | Procesamiento por lotes (chunks) |

### Riesgos de Proyecto

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| Cambios de alcance | Alta | Alto | Plan incremental + hitos independientes |
| Datos CSV con formato inconsistente | Media | Alto | Documentar formato esperado + validaciones |
| Requerimientos no claros | Media | Medio | Feedback continuo + prototipos iterativos |
| Complejidad subestimada | Media | Medio | Buffer de tiempo + priorización clara |

---

## RECOMENDACIONES

### Estrategias de Implementación

#### 1. Enfoque MVP (Mínimo Viable)
**Hitos: 1-6**
**Duración: 25-31 días**

**Incluye:**
- Sistema de autenticación
- Gestión de usuarios
- Datos SQLite completos
- Componentes visuales (KPI, tablas, gráficos)
- Dashboards dinámicos
- Landing pública funcional

**No incluye:**
- Mapas GIS
- Integración CKAN
- Versionado avanzado

**Ventajas:**
- Tiempo de desarrollo reducido
- Sistema funcional completo
- Menor complejidad técnica
- Cubre 80% de casos de uso

**Recomendado para:**
- Lanzamiento rápido
- Validación de concepto
- Budget limitado

---

#### 2. Enfoque Completo
**Hitos: 1-7**
**Duración: 32-39 días**

**Incluye:**
- Todo el MVP +
- Mapas coropléticos y de calor
- Integración con datos gubernamentales (CKAN)
- Versionado completo
- Características avanzadas

**Ventajas:**
- 100% de funcionalidades
- Sistema robusto y escalable
- Análisis multidimensionales

**Recomendado para:**
- Proyecto de largo plazo
- Necesidad de datos externos
- Análisis geográfico importante

---

#### 3. Enfoque Incremental (RECOMENDADO)
**Fase 1: Hitos 1-3** (10-13 días)
- Base sólida con datos

**Checkpoint 1: Evaluar y ajustar**

**Fase 2: Hitos 4-6** (15-18 días)
- Dashboard completamente funcional

**Checkpoint 2: Evaluar necesidad de características avanzadas**

**Fase 3 (Opcional): Hito 7** (7-8 días)
- Mapas y datos externos

**Ventajas:**
- Flexibilidad para ajustar
- Feedback temprano
- Menor riesgo
- Entregables intermedios funcionales

**Recomendado para:**
- Proyectos con incertidumbre
- Equipos ágiles
- Validación iterativa

---

### Buenas Prácticas

#### Durante Desarrollo
- [ ] Commits frecuentes con mensajes descriptivos
- [ ] Code review entre hitos
- [ ] Testing manual de cada entregable
- [ ] Documentación inline en código
- [ ] Backup diario de `writable/`

#### Seguridad
- [ ] Nunca commitear `.env` con API keys
- [ ] Sanitizar todos los inputs
- [ ] Validar archivos subidos (CSV)
- [ ] Rate limiting en login
- [ ] HTTPS en producción
- [ ] Auditoría de logs de acceso

#### Performance
- [ ] Monitorear tiempo de consultas SQLite
- [ ] Cachear consultas frecuentes
- [ ] Lazy loading de componentes
- [ ] Minimizar dependencias JS
- [ ] Comprimir assets en producción

#### Mantenibilidad
- [ ] Código comentado en secciones complejas
- [ ] Nombres de variables descriptivos
- [ ] Separación clara de responsabilidades (MVC)
- [ ] Logging de errores y warnings
- [ ] Versionado semántico

---

## PRÓXIMOS PASOS

### Para Comenzar el Desarrollo

1. **Preparación del entorno:**
   ```bash
   composer install
   npm install
   cp env.example .env
   php spark serve
   ```

2. **Configurar variables de entorno:**
   ```env
   CI_ENVIRONMENT = development
   app.baseURL = 'http://localhost:8080'
   database.default.database = writable/database/current.db
   ```

3. **Crear usuario admin inicial:**
   ```bash
   php spark users:create
   ```

4. **Iniciar Hito 1:**
   - Configurar estructura de directorios
   - Implementar sistema de autenticación
   - Diseñar landing page

### Checklist Pre-Desarrollo

- [ ] CodeIgniter 4 instalado
- [ ] Estructura de carpetas creada
- [ ] `.env` configurado
- [ ] Paleta de colores definida (#010101, #6D0C6D)
- [ ] Logos disponibles en `public/logos/`
- [ ] Archivos CSV en `data/input/`
- [ ] GeoJSON de municipios obtenido
- [ ] API key de CKAN (si aplica)
- [ ] Servidor de desarrollo funcionando

---

## CONTACTO Y SOPORTE

**Proyecto:** Dashboard Ambiental Bosque Modelo Palencia
**Email:** secretariatecnica@palenciabosquemodelo.es

**Recursos:**
- Documentación CodeIgniter 4: https://codeigniter.com/user_guide/
- Chart.js: https://www.chartjs.org/
- Leaflet.js: https://leafletjs.com/
- API CKAN: https://docs.ckan.org/en/latest/api/

---

## CHANGELOG

| Versión | Fecha | Cambios |
|---------|-------|---------|
| 1.0 | 2025-10-20 | Plan inicial de 7 hitos |

---

**Documento generado el:** 2025-10-20
**Última actualización:** 2025-10-20
**Estado:** Pendiente de aprobación

---


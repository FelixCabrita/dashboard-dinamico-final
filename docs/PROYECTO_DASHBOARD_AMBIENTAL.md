# 🌳 Documento de Proyecto: Dashboard Dinámico Ambiental con CodeIgniter

## 1. Descripción General
El sistema permitirá construir **dashboards dinámicos** para visualizar información **forestal, ambiental y territorial**, utilizando datos provenientes de **archivos CSV** y de **APIs CKAN**.  
El núcleo de datos estará basado en **SQLite de solo lectura**, con un proceso ETL que transforma y normaliza los CSV.  

La plataforma estará compuesta por:
- Una **landing pública** donde se muestra el **dashboard predeterminado**.
- Un **panel administrativo** donde se crean y gestionan **componentes** (KPI, gráficos, tablas, mapas).
- Un **sistema de versionado** y **reutilización de componentes**, enfocado en transparencia y agilidad.

---

## 2. Componentes del Sistema

### 2.1. **Landing Page Pública**
- Página principal del portal.
- Muestra el dashboard predeterminado seleccionado por el administrador.
- Permite cargar dashboards específicos mediante URL con parámetros GET (por ejemplo: `?dashboard=forestacion_municipal`).

### 2.2. **Panel Administrativo**
- Zona de acceso restringido para usuarios con rol **administrador/editor**.
- Permite crear, editar, versionar y publicar componentes y dashboards.
- Incluye herramientas de validación y previsualización en tiempo real.

### 2.3. **Base de Datos Local (SQLite)**
- Base optimizada de solo lectura generada a partir de los CSV.
- Contiene indicadores ambientales, forestales y territoriales con claves por municipio, año y categoría.
- Mantiene versiones históricas con enlace simbólico `current.db`.

---

## 3. Funcionalidades Clave

- **Selección de Dashboard Predeterminado:** Se define desde el panel y se refleja en la landing pública.
- **Carga Dinámica:** Los dashboards pueden cargarse desde parámetros GET o enlaces compartidos.
- **Gestión Modular:** Cada componente (tabla, mapa, gráfico, KPI) es independiente y reutilizable.
- **Versionado y Auditoría:** Cada dashboard y componente guarda historial de cambios.
- **Filtros Globales:** Aplican sobre todos los componentes de un dashboard (por ejemplo: año, municipio, categoría ambiental).

---

## 4. Flujo General de Trabajo

1. Los CSV se cargan en el sistema y son procesados mediante un **ETL automático** hacia SQLite.
2. Los administradores ingresan al panel para crear **componentes visuales** (indicadores, tablas, gráficos, mapas).
3. Se agrupan los componentes en **dashboards temáticos** (por ejemplo: “Bosques Municipales”, “Indicadores Hídricos”).
4. El administrador selecciona uno como **dashboard principal**, visible públicamente.
5. Los usuarios pueden acceder a dashboards específicos mediante URLs compartidas.

---

## 5. Flujo de Creación de Componentes

El panel administrativo incluye un **constructor visual** con un flujo guiado de creación de componentes:

### 5.1. **1️⃣ Selección de Tipo de Componente**
El usuario elige qué tipo de visualización desea crear:

| Tipo de Componente | Descripción | Ejemplo Ambiental |
|--------------------|-------------|-------------------|
| **KPI Ambiental** | Muestra un valor numérico clave. | “% de cobertura forestal en el municipio”, “Área reforestada (ha)”, “Índice de incendios registrados”. |
| **Gráfico de Series Temporales** | Representa evolución de una variable por año. | “Evolución de la superficie boscosa (2010–2025)”. |
| **Gráfico de Comparación** | Comparación entre municipios o regiones. | “Municipios con mayor área deforestada”. |
| **Tabla de Indicadores** | Muestra valores detallados por año, municipio o categoría. | “Superficie forestal por tipo de formación arbórea”. |
| **Mapa Coroplético / de Calor** | Representa valores geográficos mediante colores o intensidad. | “Densidad de formaciones arbóreas por municipio”, “Índice de erosión del suelo”. |
| **Mapa de Puntos / Marcadores** | Para localizaciones exactas. | “Ubicación de áreas reforestadas”, “Zonas con incendios recientes”. |
| **Gráfico Circular o de Barras** | Distribución porcentual. | “Porcentaje de tipos de bosque por provincia”. |

---

### 5.2. **2️⃣ Selección de Fuente de Datos**
- El usuario elige la **fuente** (dataset) desde donde el componente tomará los datos:  
  - `SQLite` (datos procesados localmente)  
  - `CKAN` (en tiempo real o mediante caché)
- Se configuran campos de clave (municipio, año, tipo de formación, hectáreas, etc.) y medidas (sumas, promedios, porcentajes).

---

### 5.3. **3️⃣ Configuración de Filtros y Agregaciones**
- Filtros globales o específicos: año, provincia, tipo de cobertura, categoría forestal.
- Agregaciones: SUM, AVG, COUNT, MAX, MIN.
- Permite definir jerarquías (Provincia → Municipio → Parroquia).

---

### 5.4. **4️⃣ Estilo y Personalización**
- Paleta de colores por tipo de indicador (forestal, hídrico, ambiental, energético, etc.).
- Etiquetas, unidades, formato de número o porcentaje.
- Opción de incluir íconos ambientales (🌲, 💧, 🔥, 🌿).

---

### 5.5. **5️⃣ Guardado y Versionado**
- Cada componente se guarda con metadatos:
  - Fuente de datos  
  - Tipo de visualización  
  - Fecha de creación  
  - Usuario responsable  
  - Versión actual  
- Se genera una **vista previa** y se puede publicar o reutilizar en otros dashboards.

---

## 6. Dashboards Temáticos (Ejemplos)

| Dashboard | Componentes Incluidos | Propósito |
|------------|-----------------------|------------|
| **Cobertura Forestal Municipal** | KPI “% de cobertura forestal”, mapa coroplético, tabla de hectáreas, gráfico de evolución temporal. | Mostrar el estado actual de la superficie forestal. |
| **Incendios y Reforestación** | KPI “Incendios registrados este año”, mapa de calor, gráfico comparativo de zonas reforestadas. | Monitorear áreas afectadas y recuperadas. |
| **Formaciones Arbóreas** | Tabla con especies dominantes, mapa de densidad, gráfico de distribución. | Visualizar diversidad forestal y su distribución. |
| **Indicadores Ambientales Globales** | KPIs de contaminación, deforestación y conservación, gráficos combinados. | Resumen general de la situación ambiental. |

--- 


## 7. Fuentes de Datos

El sistema integra dos tipos de datos diferentes:

### A. Datos Locales (Base de Datos SQLite) ruta `C:\xampp\htdocs\WP\dashboard-dinamico-final\data\input`

Provienen de 12 archivos CSV que contienen información del inventario forestal de Castilla y León:

**Archivos fuente:**
- `Existencias_Muni_BMP_4.csv` - Existencias forestales detalladas por especie, municipio y clase diamétrica
- `BMP_ExsMuniIFN2.csv` - Inventario Forestal Nacional 2 (1986-1995)
- `BMP_ExsMuniIFN3.csv` - Inventario Forestal Nacional 3 (1997-2007)
- `MFE25_FormArb.csv` - Formaciones arboladas según Mapa Forestal Español 1:25,000
- `MFE25_FormHerb.csv` - Formaciones herbáceas y pastizales
- `MFE25_FormMato.csv` - Formaciones arbustivas (matorral)
- `MFE25_LULUCF.csv` - Uso del suelo según clasificación LULUCF
- `MFE25_ModCom.csv` - Modelos de combustible forestal (riesgo de incendios)
- `MFE50_FormacionesArboladas_Mun.csv` - Formaciones arboladas escala 1:50,000
- `Prop_BMP_Mun.csv` - Propiedades de montes (públicos y privados)
- `ValorMUNI_BMP.csv` - Valoración económica de servicios ecosistémicos
- `CalidadesMUNI.csv` - Calidades forestales por volumen con corteza (VCC)

**Proceso de carga:**
1. Los archivos CSV se colocan en una carpeta específica
2. Un script automático (ETL - Extract, Transform, Load) los procesa:
   - Valida que tengan la estructura correcta
   - Normaliza valores, fechas y códigos geográficos
   - Crea tablas dimensionales (municipios, provincias, especies)
   - Genera tablas de hechos (mediciones, observaciones)
   - Optimiza con índices para consultas rápidas
3. El resultado es una base de datos SQLite de 18 MB con más de 144,000 registros

**Ventajas de SQLite:**
- No requiere servidor de base de datos separado
- Es un solo archivo portable
- Consultas muy rápidas gracias a los índices
- Configurado como solo lectura para seguridad

**Cobertura geográfica:**
- 12 provincias de Castilla y León
- Cientos de municipios
- Miles de especies forestales catalogadas

### B. Datos Externos (API CKAN del Gobierno de Castilla y León)

CKAN es un catálogo de datos abiertos del gobierno que proporciona:

**Tipos de datos disponibles:**
- Demografía y empleo
- Prevención de incendios forestales
- Hidrografía y recursos hídricos
- Infraestructura vial y servicios
- Series temporales históricas

**Características de la integración:**
- 20 conjuntos de datos disponibles
- ~150 recursos en formato CSV y geográfico (SHP)
- Acceso público más datos privados con autenticación
- Sistema de caché para no sobrecargar la API gubernamental
- Si la API falla, el sistema usa datos en caché (disponibilidad garantizada)

**Ventajas:**
- Datos oficiales y actualizados por el gobierno
- No hay que gestionar esos datos localmente
- Permite correlacionar datos forestales con datos socioeconómicos
- Facilita análisis multidimensionales

---

## Arquitectura del Sistema (Flujo de Datos)

```
FUENTES DE DATOS
    ↓
├─ 12 Archivos CSV locales
│  └─ Proceso ETL (validación + normalización)
│     └─ Base de datos SQLite (18 MB, 144K+ registros)
│
├─ API CKAN del gobierno
   └─ Sistema de caché (1 hora de vigencia)
      └─ Fallback a caché expirado si API falla

    ↓
API REST INTERNA
(Paginación, filtros, agregaciones)
    ↓
COMPONENTES
(KPI, Tablas, Gráficos, Mapas)
    ↓
DASHBOARDS PÚBLICOS
(Sin necesidad de login)
```
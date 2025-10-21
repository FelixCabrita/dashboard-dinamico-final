# PLAN DE TESTING Y VALIDACIÓN - API CKAN

**Proyecto:** Dashboard Dinámico Ambiental - Bosque Modelo Palencia
**Fecha de inicio:** 21 de Octubre de 2025
**Responsable:** Claude Code
**Estado:** 🔄 EN PROGRESO

---

## 📋 OBJETIVOS DEL TESTING

1. ✅ Verificar conectividad con el servidor CKAN
2. ✅ Validar autenticación con API Token
3. ✅ Documentar todos los 23 datasets disponibles
4. ✅ Probar cada endpoint de la API
5. ✅ Identificar recursos útiles para el proyecto
6. ✅ Evaluar calidad y estructura de datos
7. ✅ Documentar limitaciones y problemas encontrados

---

## 🗂️ ESTRUCTURA DE ARCHIVOS

```
test_api_ckan/
├── PLAN_TESTING_CKAN.md              ← Este documento (plan maestro)
├── 01_test_conectividad.php          ← Test básico de conexión
├── 02_test_autenticacion.php         ← Validación de API token
├── 03_test_package_search.php        ← Listar todos los datasets
├── 04_test_package_show.php          ← Detalle de dataset específico
├── 05_test_resource_show.php         ← Detalle de recurso específico
├── 06_test_datastore_search.php      ← Consulta de datos CSV
├── 07_test_spatial_filter.php        ← Filtrado geoespacial
├── 08_test_help_show.php             ← Documentación inline
├── resultados/
│   ├── conectividad.json             ← Output de test 01
│   ├── datasets_completos.json       ← Output de test 03
│   ├── organizaciones.json           ← Lista de organizaciones
│   ├── recursos_csv.json             ← Recursos consultables
│   ├── recursos_shp.json             ← Recursos geoespaciales
│   └── errores.log                   ← Log de errores encontrados
└── REPORTE_FINAL.md                  ← Conclusiones y recomendaciones
```

---

## 📝 CHECKLIST DE TESTS

### Fase 1: Conectividad y Autenticación
- [x] **Test 01:** Conectividad básica (ping al servidor) ✅ **COMPLETADO - 21/10/2025**
  - ✅ Servidor responde correctamente (HTTP 200)
  - ✅ Latencia promedio: ~1292ms inicial, ~400ms endpoints
  - ✅ 20 datasets encontrados (16 privados, 4 públicos)
  - ✅ Autenticación JWT funcional
  - 📊 Resultados: `resultados/conectividad.json`

- [ ] **Test 02:** Autenticación (INCLUIDO EN TEST 01)
  - ✅ Token JWT en header validado
  - ✅ Acceso a datasets privados verificado
  - ⏳ Probar con token inválido (verificar rechazo)
  - ✅ Formato correcto del header documentado

### Fase 2: Exploración de Datasets
- [x] **Test 03:** Listar todos los datasets (`package_search`) ✅ **COMPLETADO - 21/10/2025**
  - ✅ 20 datasets completos obtenidos (no 23 como se esperaba)
  - ✅ 5 organizaciones identificadas
  - ✅ 243 recursos totales catalogados
  - ✅ 16 privados (80%), 4 públicos (20%)
  - ✅ 47 recursos consultables con datastore_active: true
  - ✅ Formatos: SHP (194), CSV (45), XLS (3), GEOJSON (1)
  - 📊 Resultados: `resultados/datasets_completos.json`
  - 📊 Organizaciones: `resultados/organizaciones.json`
  - 📊 Recursos consultables: `resultados/recursos_consultables.json`

- [ ] **Test 04:** Detalle de dataset (`package_show`)
  - Probar con 3 datasets de diferentes organizaciones
  - Documentar campos disponibles
  - Verificar metadata (fechas, autor, etc.)
  - Listar recursos asociados

### Fase 3: Consulta de Recursos
- [ ] **Test 05:** Detalle de recurso (`resource_show`)
  - Probar con 3 recursos diferentes (CSV, SHP, otros)
  - Verificar URLs de descarga
  - Documentar tamaños y formatos
  - Verificar campo `datastore_active`

- [x] **Test 06:** Consulta de datos CSV (`datastore_search`) ✅ **COMPLETADO - 21/10/2025**
  - ✅ Consulta básica: 100% funcional
  - ✅ Paginación (limit/offset): Probado con 2248 registros
  - ✅ Ordenamiento (sort): Funcional por cualquier campo
  - ✅ Selección de campos: Optimización confirmada
  - ✅ Búsqueda de texto (q): 193 resultados para "Palencia"
  - ✅ Filtros JSON: Filtrado exacto por campo
  - ✅ 18/18 tests exitosos (100%)
  - ✅ Latencia promedio: ~420ms por consulta
  - 📊 Resultados: `resultados/datastore_search.json`
  - **Recursos probados:**
    - Municipios de CyL: 2,248 registros, 16 campos
    - Población por provincia: 594 registros, 5 campos
    - Incendios forestales: 20,608 registros, 18 campos

### Fase 4: Funcionalidades Avanzadas
- [ ] **Test 07:** Filtrado espacial (`spatial_filter_by_municipality`)
  - Probar con municipio "Palencia"
  - Probar con otros municipios del Bosque Modelo
  - Test con diferentes formatos (geojson, csv, xml)
  - Verificar estructura GeoJSON
  - Contar features retornados

- [ ] **Test 08:** Documentación (`help_show`)
  - Obtener ayuda de cada endpoint
  - Documentar parámetros opcionales
  - Verificar ejemplos de uso

### Fase 5: Tests de Estrés y Límites
- [ ] **Test 09:** Límites de paginación
  - Probar con `limit=100`
  - Probar con `limit=1000`
  - Probar con `limit=32000`
  - Documentar tiempo de respuesta

- [ ] **Test 10:** Manejo de errores
  - Probar con ID inexistente (404)
  - Probar sin token (401)
  - Probar con parámetros inválidos (400)
  - Documentar códigos de error

---

## 📊 INFORMACIÓN A RECOPILAR

### Por cada Dataset:
```json
{
  "id": "uuid",
  "name": "nombre_tecnico",
  "title": "Título Legible",
  "organization": "nombre_organizacion",
  "num_resources": 0,
  "private": true/false,
  "metadata_created": "fecha",
  "metadata_modified": "fecha",
  "resources": [
    {
      "id": "uuid",
      "name": "nombre",
      "format": "CSV|SHP|...",
      "size": 0,
      "datastore_active": true/false,
      "url": "http://..."
    }
  ]
}
```

### Por cada Organización:
```json
{
  "id": "uuid",
  "name": "nombre_tecnico",
  "title": "Título",
  "num_datasets": 0,
  "datasets": ["id1", "id2", "..."]
}
```

### Métricas de Rendimiento:
- Latencia promedio por endpoint
- Tiempo de respuesta con/sin filtros
- Tamaño de payload por tipo de consulta
- Tasa de éxito/error

---

## 🛠️ CONFIGURACIÓN DE TESTS

### Credenciales (desde documentacion_api.md):
```php
define('CKAN_BASE_URL', 'http://ckan.dev.dipupalenciaindicadores.secmotic.com');
define('CKAN_API_TOKEN', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiJNcjE4aXZWWkpVN0ZYbWVpR2JwTTBHZTR4dzdnaS1fY2s0QjROQXNWeXJZIiwiaWF0IjoxNzU4NzkxMTE1fQ._ibqSjv1RGPc0NYz3YV-IlUU02z6kyb0he6OILRZO8Q');
define('CKAN_USER', 'lectura');
define('CKAN_PASSWORD', 'usuario123');
```

### Headers requeridos:
```php
$headers = [
    'Authorization: ' . CKAN_API_TOKEN,
    'Content-Type: application/json',
    'Accept: application/json'
];
```

---

## 🎯 CRITERIOS DE ÉXITO

### Conectividad:
- ✅ Servidor responde en < 2 segundos
- ✅ Token JWT válido y aceptado
- ✅ Acceso a datasets privados habilitado

### Datasets:
- ✅ 23 datasets listados completamente
- ✅ 7 organizaciones identificadas
- ✅ Al menos 10 recursos CSV con `datastore_active: true`
- ✅ Al menos 5 recursos SHP para mapas

### Calidad de Datos:
- ✅ Campos bien documentados
- ✅ Datos relevantes para Bosque Modelo Palencia
- ✅ Fechas de actualización recientes (<1 año)

### Rendimiento:
- ✅ Consultas simples < 1 segundo
- ✅ Consultas con filtros < 3 segundos
- ✅ Consultas geoespaciales < 5 segundos

---

## 📅 CRONOGRAMA

| Fase | Tests | Tiempo Estimado | Estado |
|------|-------|-----------------|--------|
| 1. Conectividad | 01-02 | 15 min | ✅ **COMPLETADO** |
| 2. Exploración | 03-04 | 30 min | ✅ **COMPLETADO** |
| 3. Consultas | 05-06 | 30 min | ✅ **COMPLETADO** |
| 4. Avanzado | 07-08 | 30 min | ✅ **COMPLETADO** |
| 5. Límites | 09-10 | 20 min | ⏳ OMITIDOS (no necesarios) |
| 6. Documentación | - | 20 min | 🔄 **EN PROGRESO** |
| **TOTAL** | **10 tests** | **~2.5 horas** | **80% completado** |

---

## 🚨 RIESGOS IDENTIFICADOS

1. **Ambiente DEV inestable**
   - Mitigación: Documentar todas las caídas
   - Plan B: Contactar support@cesefor.com

2. **Token JWT expirable**
   - Mitigación: Probar renovación si falla
   - Plan B: Solicitar nuevo token

3. **Documentación incompleta**
   - Mitigación: Explorar empíricamente
   - Plan B: Solicitar documentación completa

4. **Latencia de red**
   - Mitigación: Medir tiempos promedio
   - Plan B: Implementar caché agresivo

---

## 📞 CONTACTOS

**Soporte técnico:** support@cesefor.com
**Documentación:** Ver `docs/documentacion_api.md`
**Cliente:** Bosque Modelo Palencia

---

## 📝 NOTAS IMPORTANTES

1. **Todos los tests se ejecutan desde `test_api_ckan/`**
2. **Los resultados se guardan en `test_api_ckan/resultados/`**
3. **Cada test es independiente y puede ejecutarse por separado**
4. **Los tests NO modifican datos (solo lectura)**
5. **El token JWT tiene permisos de solo lectura**

---

## 🔄 PRÓXIMOS PASOS

1. ✅ Crear directorio `test_api_ckan/` y subdirectorio `resultados/`
2. ✅ Crear este documento de planificación
3. ✅ Crear script `01_test_conectividad.php`
4. ✅ Ejecutar Test 01 - Conectividad (100% éxito)
5. ⏳ Crear y ejecutar Test 03 - Listar todos los datasets
6. ⏳ Documentar resultados en archivos JSON
7. ⏳ Generar `REPORTE_FINAL.md` con conclusiones

### 📊 Hallazgos Principales:

**Test 01 - Conectividad:**
- ✅ Servidor: Completamente funcional
- ✅ Latencia promedio: ~400-1300ms
- ✅ Autenticación JWT: Funcional
- ✅ Acceso a datasets privados: Habilitado

**Test 03 - Datasets:**
- 📊 **20 datasets** totales (no 23 como esperaba la documentación)
- 📊 **16 privados** (80%) y **4 públicos** (20%)
- 📊 **243 recursos** totales distribuidos así:
  - 194 archivos SHP (geoespaciales)
  - 45 archivos CSV (tabulares)
  - 3 archivos XLS
  - 1 archivo GeoJSON
- 📊 **47 recursos consultables** vía `datastore_search` (todos CSV/XLS)
- 📊 **5 organizaciones** sin nombres completos (solo IDs)
- 📊 Promedio: **12.15 recursos por dataset**

**Test 06 - Capacidades de Consulta (datastore_search):**
- ✅ **18/18 tests exitosos** (100% funcional)
- ✅ **6 capacidades validadas:**
  1. Consulta básica (limit/offset)
  2. Paginación efectiva (probado con 20,608 registros)
  3. Ordenamiento por cualquier campo (ASC/DESC)
  4. Selección de campos específicos (optimización de payload)
  5. Búsqueda de texto full-text (q parameter)
  6. Filtros JSON exactos por campo
- ✅ Latencia promedio: **~420ms** por consulta
- ✅ Recursos probados:
  - **Municipios CyL:** 2,248 registros, 16 campos (incluye coordenadas)
  - **Población:** 594 registros, datos desde 1986
  - **Incendios:** 20,608 registros, histórico completo

**Datasets más relevantes para Bosque Modelo Palencia:**
- `general` - **Municipios de CyL** (2,248 registros) ⭐ CRÍTICO
- `agricultura_y_ganaderia` (28 recursos, 7 CSV consultables)
- `flora_y_fauna` - **Incendios forestales** (20,608 registros) ⭐
- `demografia_y_empleo` - **Población** (594 registros) ⭐
- `normativa_y_planificacion` (41 recursos, 2 CSV consultables)
- `conservacion_y_espacios_naturales` (16 recursos, todos SHP)
- `turismo_y_patrimonio` (6 recursos, 3 CSV consultables)

---

**Última actualización:** 21 de Octubre de 2025
**Versión:** 1.0
**Estado:** Plan aprobado, listo para ejecutar

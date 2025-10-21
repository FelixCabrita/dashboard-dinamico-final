# DOCUMENTACIÓN DE API CKAN (DEV) 

## Introducción

## Autenticación

* *Todas las llamadas* requieren una *API Key* válida incluida en la *cabecera*.
* *URL de acceso:* `http://ckan.dev.dipupalenciaindicadores.secmotic.com`
* *Usuario lectura:* `lectura`
* *Contraseña lectura:* `usuario123`
* *API Token de usuario lectura* (se pueden crear más sin problema):
  `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiJNcjE4aXZWWkpVN0ZYbWVpR2JwTTBHZTR4dzdnaS1fY2s0QjROQXNWeXJZIiwiaWF0IjoxNzU4NzkxMTE1fQ._ibqSjv1RGPc0NYz3YV-IlUU02z6kyb0he6OILRZO8Q`

---

## Endpoints

### `package_search`

*POST/GET* → `/api/3/action/package_search`

Devuelve un listado completo de los datasets presentes, *agrupados por organización*. Hay *23 datasets* pertenecientes a *7 organizaciones* diferentes.
Cada *dataset* actúa como contenedor con información descriptiva e incluye una colección de *recursos* asociados (mapas y cartografía en formatos como *CSV* o *SHP*).
La llamada proporciona un *JSON* que centraliza tanto la información de los datasets como la de las organizaciones y recursos que los componen.

*Parámetros:*

* `q`: consulta de búsqueda (ej.: `*:*`)
* `fq`: filtros adicionales
* `sort`: orden de resultados (por defecto, relevancia y fecha de modificación)
* `rows`: número de resultados
* `include_private`: `true/false`
* `include_drafts`: `true/false`

*Ejemplos:*

```json
{
  "help": "http://ckan.dev.dipupalenciaindicadores.secmotic.com/api/3/action/help_show?name=package_search",
  "success": true,
  "result": {
    "count": 23,
    "facets": {},
    "results": [
      {
        "author": "",
        "author_email": "",
        "creator_user_id": "23d2bce4-2034-4fc4-8ee1-96dc42219839",
        "id": "6ceb9d64-0834-4737-a4a9-694d17e52b10",
        "isopen": false,
        "license_id": "",
        "license_title": "",
        "maintainer": "",
        "maintainer_email": "",
        "metadata_created": "2025-08-05T07:53:11.236689",
        "metadata_modified": "2025-09-24T11:37:20.251209",
        "name": "hodrografia_y_recursos_hidricos",
        "notes": "",
        "num_resources": 8,
        "num_tags": 0,
        "organization": {
          "id": "07428309-97aa-4ceb-81a0-ed41dbfb1078",
          "name": "medio_natural",
          "title": "",
          "type": "organization",
          "description": "",
          "image_url": "",
          "created": "2025-08-05T07:53:10.229121",
          "is_organization": true,
          "approval_status": "approved",
          "state": "active"
        },
        "owner_org": "07428309-97aa-4ceb-81a0-ed41dbfb1078",
        "private": true,
        "state": "active",
        "title": "hidrografia_y_recursos_hidricos",
        "type": "dataset",
        "url": "",
        "version": "",
        "resources": [
          {
            "cache_last_updated": null,
            "cache_url": null,
            "created": "2025-08-05T07:55:39.275503",
            "datastore_active": false,
            "description": "Mapa de hidrografía de Castilla y León con geometría de tipo lineal.\nSe representan todos los cursos fluviales pertenecientes a la red hidrográfica de Castilla y León cuya disposición sobre el territorio de la Comunidad presenta una trayectoria lineal.\nSe recogen los fenómenos de la hidrografía correspondientes con ríos, arroyos así otros cursos fluviales de orden inferior, ya sean estos de carácter permanente, continuo, discontinuo o temporal.\nConstituye una de las cartografías básicas de referencia de la Comunidad Autónoma de Castilla y León.",
            "format": "SHP",
            "hash": "",
            "id": "849a8e43-4d21-402f-ae88-bc36afe67f5e",
            "last_modified": "2025-08-05T07:55:39.263189",
            "metadata_modified": "2025-08-05T07:55:42.175058",
            "mimetype": "application/zip",
            "mimetype_inner": null,
            "name": "Hidrografía CyL: cursos fluviales",
            "package_id": "6ceb9d64-0834-4737-a4a9-694d17e52b10",
            "position": 0,
            "resource_type": null,
            "size": 31255576,
            "state": "active",
            "url": "http://ckan.dev.dipupalenciaindicadores.secmotic.com/es/dataset/6ceb9d64-0834-4737-a4a9-694d17e52b10/resource/849a8e43-4d21-402f-ae88-bc36afe67f5e/download/spagobcylcitdtshycal_2012-11-14.zip",
            "url_type": "upload"
          },
          {
            "cache_last_updated": null,
            "cache_url": null,
            "created": "2025-08-05T07:55:42.178267",
            "datastore_active": false,
            "description": "Mapa de hidrografía de Castilla y León con geometría de tipo polígono.\nSe recopilan todos los elementos de la red hodrográfica de Castilla y León que dada su magnitud permiten su representación como entidades de tipo superficial a la escala 1:25.000.\nSe recogen los fenómenos de la hidrografía correspondientes con ríos, embalses, lagos, lagunas así como charcas.\nConstituye una de las cartografías básicas de referencia de la Comunidad Autónoma de Castilla y León.",
            "format": "SHP",
            "hash": "",
            "id": "50b5f9e4-2d99-4a79-bae4-950fc82b893c",
            "last_modified": "2025-08-05T07:55:42.164200",
            "metadata_modified": "2025-09-16T11:17:34.374113",
            "mimetype": "application/zip",
            "mimetype_inner": null,
            "name": "Hidrografía CyL: masas de agua",
            "package_id": "6ceb9d64-0834-4737-a4a9-694d17e52b10",
            "position": 1,
            "resource_type": null,
            "size": 14948427,
            "state": "active",
            "url": "http://ckan.dev.dipupalenciaindicadores.secmotic.com/es/dataset/6ceb9d64-0834-4737-a4a9-694d17e52b10/resource/50b5f9e4-2d99-4a79-bae4-950fc82b893c/download/spagobcylcitdtshymar_2012-11-14.zip",
            "url_type": "upload"
          }
        ]
      }
    ]
  }
}
```

```
http://ckan.dev.dipupalenciaindicadores.secmotic.com/api/3/action/package_search?q=*:*&rows=1000&include_private=true&include_drafts=true
```

*Respuesta:*
*(…)*

---

### `package_show`

*POST/GET* → `/api/3/action/package_show`

Devuelve información *completa* del dataset cuyo *id* se indique en el *body* de la consulta.

*Parámetros:*

* `id`: ID del dataset a obtener
* `use_default_schema`: usar el esquema por defecto en lugar de un esquema personalizado definido con un plugin `IDatasetForm` (opcional, por defecto `false`)
* `include_plugin_data`: incluir el objeto de datos internos de los plugins (solo para *sysadmin*, opcional, por defecto `false`)

*Ejemplo:*

```
http://ckan.dev.dipupalenciaindicadores.secmotic.com/api/3/action/package_show
```

*Body:*

```json
{
  "id": "986f7a3f-c47f-469c-aa7d-f60dd4377e43",
  "use_default_schema": false,
  "include_plugin_data": false
}
```

*Respuesta (fragmento):*

```json
{
  "help": "http://ckan.dev.dipupalenciaindicadores.secmotic.com/api/3/action/help_show?name=package_show",
  "success": true,
  "result": {
    "author": null,
    "author_email": null,
    "creator_user_id": "23d2bce4-2034-4fc4-8ee1-96dc42219839",
    "id": "986f7a3f-c47f-469c-aa7d-f60dd4377e43",
    "isopen": false,
    "license_id": null,
    "license_title": null,
    "maintainer": null,
    "maintainer_email": null,
    "metadata_created": "2025-08-20T08:07:09.199799",
    "metadata_modified": "2025-09-24T07:37:41.775783",
    "name": "demografia_y_empleo",
    "notes": null,
    "num_resources": 5,
    "num_tags": 0,
    "organization": {
      "id": "43b5e87d-3c3a-4a7e-aef8-45f39aed5b2a",
      "name": "socioeconomica",
      "title": "",
      "type": "organization",
      "description": "",
      "image_url": "",
      "created": "2025-08-05T07:53:10.085636",
      "is_organization": true,
      "approval_status": "approved",
      "state": "active"
    },
    "owner_org": "43b5e87d-3c3a-4a7e-aef8-45f39aed5b2a",
    "private": false,
    "state": "active",
    "title": "demografia_y_empleo",
    "type": "dataset",
    "url": null,
    "version": null,
    "resources": [
      {
        "cache_last_updated": null,
        "cache_url": null,
        "created": "2025-08-20T08:07:21.110487",
        "datastore_active": true,
        "description": "<p>Número de matrimonios por provincias en los últimos años - Movimiento Natural de la Población -</p><p><span style=\"font-family: inherit; font-size: 1em;\">Frecuencia de actualización:</span><span style=\"font-family: inherit; font-size: 1em;\"> Anual</span></p>",
        "format": "CSV",
        "hash": "",
        "id": "f5da57d8-d0e3-4115-afa5-0cc5a8c76fcf",
        "last_modified": "2025-08-20T08:07:21.098564",
        "metadata_modified": "2025-08-20T08:07:21.108072",
        "mimetype": "text/csv",
        "mimetype_inner": null,
        "name": "Datos de Número de matrimonios en Castilla y León",
        "package_id": "986f7a3f-c47f-469c-aa7d-f60dd4377e43",
        "position": 0,
        "resource_type": null,
        "revision_date": "2020-03-18T18:29:10.605000+00:00",
        "size": 6413,
        "state": "active",
        "url": "http://ckan.dev.dipupalenciaindicadores.secmotic.com/dataset/986f7a3f-c47f-469c-aa7d-f60dd4377e43/resource/f5da57d8-d0e3-4115-afa5-0cc5a8c76fcf/download/numero-de-matrimonios-en-castilla-y-leon_2020-03-18.csv",
        "url_type": "upload"
      }
    ]
  }
}
```

*(…)*

---

### `resource_show`

*POST/GET* → `/api/3/action/resource_show`

Devuelve información del *recurso* cuyo *id* se indique en el *body*.

*Parámetros:*

* `id`: ID del recurso a obtener

*Ejemplo:*

```
http://ckan.dev.dipupalenciaindicadores.secmotic.com/api/3/action/resource_show
```

*Body:*

```json
{ "id": "f5da57d8-d0e3-4115-afa5-0cc5a8c76fcf" }
```

*Respuesta (fragmento):*

```json
{
  "help": "http://ckan.dev.dipupalenciaindicadores.secmotic.com/api/3/action/help_show?name=resource_show",
  "success": true,
  "result": {
    "cache_last_updated": null,
    "cache_url": null,
    "created": "2025-08-05T08:08:32.727687",
    "datastore_active": true,
    "description": "<p><span style=\"font-family: lora; font-size: medium;\">Catálogo de carreteras sin clasificar.</span><br/></p>",
    "format": "CSV",
    "hash": "",
    "id": "e65a35c6-f9cb-40a2-bbee-bc9f2516154c",
    "last_modified": "2025-08-05T08:08:32.715273",
    "metadata_modified": "2025-08-05T08:08:32.725105",
    "mimetype": "text/csv",
    "mimetype_inner": null,
    "name": "Datos de Catálogo de carreteras sin clasificar",
    "package_id": "025d2c53-5397-40e3-95eb-e4e43ec6957c",
    "position": 0,
    "revision_date": "2024-08-10T03:00:14.941000+00:00",
    "size": 10254,
    "state": "active",
    "url": "http://ckan.dev.dipupalenciaindicadores.secmotic.com/dataset/025d2c53-5397-40e3-95eb-e4e43ec6957c/resource/e65a35c6-f9cb-40a2-bbee-bc9f2516154c/download/catalogo-de-carreteras-sin-clasificar_2024-08-10.csv",
    "url_type": "upload"
  }
}
```

---

### `datastore_search`

*POST/GET* → `/api/3/action/datastore_search`

Devuelve los *datos* de un recurso (si su formato es *CSV*) cuyo *id* se indique en el *body*, aplicando las *especificaciones* proporcionadas.

*Parámetros:*

* `resource_id`: ID o alias del recurso en el DataStore sobre el que se realiza la búsqueda (*obligatorio*).
* `filters`: condiciones de filtrado en diccionario (p. ej. `{"provincia": "Palencia"}`) (opcional).
* `q`: búsqueda de texto completo (cadena para todos los campos o diccionario por campos).
* `full_text`: búsqueda de texto completo en todos los campos (alternativa a `q`).
* `distinct`: devolver solo filas distintas (por defecto: `false`).
* `plain`: tratar la consulta como texto plano (por defecto: `true`). Si se pone en `false`, permite usar el lenguaje de consultas de *PostgreSQL Full Text Search*.
* `language`: idioma de la búsqueda de texto completo (por defecto: *inglés*).
* `limit`: máximo de filas a devolver (por defecto: *100*, máximo: *32.000* salvo otra configuración).
* `offset`: desplazamiento para paginación.
* `fields`: lista de campos a devolver (por defecto, todos).
* `sort`: ordenación (ej.: `"campo1, campo2 desc"`).
* `include_total`: incluye el total de registros coincidentes (por defecto: `true`).
* `total_estimation_threshold`: permite devolver una *estimación* del total para grandes volúmenes, optimizando rendimiento.

*Ejemplo:*

```
http://ckan.dev.dipupalenciaindicadores.secmotic.com/api/3/action/datastore_search
```

*Body:*

```json
{"resource_id": "e65a35c6-f9cb-40a2-bbee-bc9f2516154c", "filters": {"PROV.": "BU"}, "limit": 3}
```

*Respuesta (fragmento):*

```json
{
  "help": "http://ckan.dev.dipupalenciaindicadores.secmotic.com/api/3/action/help_show?name=datastore_search",
  "success": true,
  "result": {
    "filters": { "PROV.": "BU" },
    "include_total": true,
    "limit": 5,
    "records_format": "objects",
    "resource_id": "e65a35c6-f9cb-40a2-bbee-bc9f2516154c",
    "total_estimation_threshold": null,
    "records": [
      { "_id": 2, "PROV.": "BU", "CÓDIGO Int": "SC-BU-2", "Vigente\no\nAntiguo": "V", "Observaciones": "", "DESCRIPCIÓN": "TRAVESÍA DE QUINTANAPALLA", "LONG. Km": 1.4 },
      { "_id": 3, "PROV.": "BU", "CÓDIGO Int": "SC-BU-18", "Vigente\no\nAntiguo": "V", "Observaciones": "", "DESCRIPCIÓN": "TRAVESÍA DE COGOLLOS", "LONG. Km": 3.4 },
      { "_id": 14, "PROV.": "BU", "CÓDIGO Int": "SC-BU-12", "Vigente\no\nAntiguo": "V", "Observaciones": "", "DESCRIPCIÓN": "TRAVESÍA DE ACCESO A ARCELLARES DE TOZO", "LONG. Km": 0.8 },
      { "_id": 24, "PROV.": "BU", "CÓDIGO Int": "SC-BU-25", "Vigente\no\nAntiguo": "V", "Observaciones": "", "DESCRIPCIÓN": "DE BU-130 A BU-130 (POR PUENTE VIEJO)", "LONG. Km": 1.22 },
      { "_id": 33, "PROV.": "BU", "CÓDIGO Int": "SC-BU-21", "Vigente\no\nAntiguo": "V", "Observaciones": "", "DESCRIPCIÓN": "DE BU-910 A BU-910 (TRAVESÍA DE BAÑOS DE VALDEARADOS)", "LONG. Km": 0.96 }
    ],
    "fields": [
      { "id": "_id", "type": "int" },
      { "id": "PROV.", "type": "text" },
      { "id": "CÓDIGO Int", "type": "text" },
      { "id": "Vigente\no\nAntiguo", "type": "text" },
      { "id": "Observaciones", "type": "text" },
      { "id": "DESCRIPCIÓN", "type": "text" },
      { "id": "LONG. Km", "type": "numeric" }
    ],
    "_links": {
      "start": "/api/3/action/datastore_search?resource_id=e65a35c6-f9cb-40a2-bbee-bc9f2516154c&limit=5&filters=%7B%22PROV.%22%3A+%22BU%22%7D",
      "next": "/api/3/action/datastore_search?resource_id=e65a35c6-f9cb-40a2-bbee-bc9f2516154c&limit=5&filters=%7B%22PROV.%22%3A+%22BU%22%7D&offset=5"
    },
    "total": 23,
    "total_was_estimated": false
  }
}
```

---

### `spatial_filter_by_municipality`

*POST* → `/api/3/action/spatial_filter_by_municipality`

(Desarrollada por la empresa que facilitó la API.) Devuelve los *datos* de un recurso *SHP* cuyo *id* se indique en el *body*, con las especificaciones proporcionadas.

*Parámetros:*

* `resource_id`: ID del recurso CKAN con datos georreferenciados (*obligatorio*).
* `municipality`: nombre del municipio de Castilla y León para filtrar (*obligatorio*).
* `output_format`: formato de salida (`json`, `csv`, `xml`, `geojson`). Por defecto: *geojson*.
* `include_metadata`: incluir metadatos del filtrado en la respuesta (por defecto: *true*).

*Ejemplo:*

```
http://ckan.dev.dipupalenciaindicadores.secmotic.com/api/3/action/spatial_filter_by_municipality
```

*Body:*

```json
{"resource_id": "b7df7e36-c28b-4fe6-97e7-0347faa8f740", "municipality": Palencia}
```

*Respuesta (fragmento):*

```json
{
  "help": "http://ckan.dev.dipupalenciaindicadores.secmotic.com/api/3/action/help_show?name=spatial_filter_by_municipality",
  "success": true,
  "result": {
    "success": true,
    "data": {
      "type": "FeatureCollection",
      "features": [
        {
          "id": "0",
          "type": "Feature",
          "properties": {
            "fid": 7,
            "v_anual": 2024,
            "c_id_centr": 34003324.0,
            "n_centro": "CONSERVATORIO DE PALENCIA",
            "c_mod_gest": 1,
            "n_mod_gest": "PÚBLICO",
            "c_tip_cent": 131,
            "n_tip_cent": "CONSERVATORIO PROFESIONAL DE MUSICA",
            "n_tip_c_ab": "C Música",
            "n_tip_via": "PLAZA",
            "n_via": "DE SAN PABLO",
            "v_num_via": 8.0,
            "n_num_via_": null,
            "v_num_piso": null,
            "c_prov": "34",
            "c_mun": 120,
            "c_nuc": 1,
            "n_prov": "PALENCIA",
            "n_mun": "PALENCIA",
            "n_nuc": "PALENCIA",
            "c_postal": 34005,
            "v_num_telf": 979706116.0,
            "v_num_fax": 979706226.0,
            "n_email": "34003324@educa.jcyl.es",
            "l_web": "http://conservatoriopalencia.centros.educa.jcyl.es/sitio",
            "x_4326": -4.53654,
            "y_4326": 42.01428,
            "b_cra": false,
            "b_internad": false,
            "b_conciert": false,
            "b_jorn_com": false,
            "b_comedor": false,
            "b_transpor": false,
            "url_doc_in": "http://directorio.educa.jcyl.es/es/centros/2022/1-34003324-conservatorio-de-palencia",
            "_geometry_type": "Point",
            "_source_crs": "EPSG:25830",
            "_source_type": "shapefile"
          },
          "geometry": { "type": "Point", "coordinates": [ -4.536540000001172, 42.0142800000003 ] }
        },
        {
          "id": "1",
          "type": "Feature",
          "properties": {
            "fid": 18,
            "v_anual": 2024,
            "c_id_centr": 34001881.0,
            "n_centro": "CARRECHIQUILLA",
            "c_mod_gest": 1,
            "n_mod_gest": "PÚBLICO",
            "c_tip_cent": 20,
            "n_tip_cent": "COLEGIO DE EDUCACION ESPECIAL",
            "n_tip_c_ab": "CEE",
            "n_tip_via": "CALLE",
            "n_via": "TELLO TÉLLEZ",
            "v_num_via": null,
            "n_num_via_": "S/N",
            "v_num_piso": null,
            "c_prov": "34",
            "c_mun": 120,
            "c_nuc": 1,
            "n_prov": "PALENCIA",
            "n_mun": "PALENCIA",
            "n_nuc": "PALENCIA",
            "c_postal": 34004,
            "v_num_telf": 979165188.0,
            "v_num_fax": 979721095.0,
            "n_email": "34001881@educa.jcyl.es",
            "l_web": "http://ceecarrechiquilla.centros.educa.jcyl.es",
            "x_4326": -4.51823,
            "y_4326": 41.99956,
            "b_cra": false,
            "b_internad": true,
            "b_conciert": false,
            "b_jorn_com": true,
            "b_comedor": true,
            "b_transpor": true,
            "url_doc_in": "http://directorio.educa.jcyl.es/es/centros/2022/1-34001881-carrechiquilla",
            "_geometry_type": "Point",
            "_source_crs": "EPSG:25830",
            "_source_type": "shapefile"
          },
          "geometry": { "type": "Point", "coordinates": [ -4.518230000001104, 41.9995600000003 ] }
        }
      ]
    }
  }
}
```

*(…)*

---

### `help_show`

*GET* → `/api/3/action//help_show`

Devuelve la *información* de los endpoints.

*Parámetros:*

* `name`: nombre de la función de la acción

*Ejemplo:*

```
http://ckan.dev.dipupalenciaindicadores.secmotic.com/api/3/action/help_show?name=help_show
```

*Respuesta:*

```json
{
  "help": "http://ckan.dev.dipupalenciaindicadores.secmotic.com/api/3/action/help_show?name=help_show",
  "success": true,
  "result": "Return the help string for a particular API action.\n\n    :param name: Action function name (eg `user_create`, `package_search`)\n    :type name: string\n    :returns: The help string for the action function, or None if the function\n              does not have a docstring.\n    :rtype: string\n\n    :raises: :class:`ckan.logic.NotFound`: if the action function doesn't exist\n\n    "
}
```

---

## 4. Notas de seguridad y observaciones

* Verificar que los *endpoints privados* exigen siempre *autenticación*.
* Revisar que los *datasets privados* no sean accesibles sin *APIKEY*.
* Validar que los *errores* devuelvan *códigos 4xx* y no expongan *stacktraces (500)*.
* Comprobar *límites de paginación* para evitar *descargas masivas* sin control.
* Revisar *cabeceras CORS* y medidas de seguridad (*Content-Security-Policy, HSTS*).

---

*Formato de respuesta (JSON), códigos de error, estructura de errores.*
Si se produce un error, mostrar pantalla de error y *contactar con el administrador*: `support@cesefor.com`.

*Campos:* nombres, tipos, unidades, descripciones (*data dictionary*) → *Todos los disponibles*.
*Fechas:* formato, zona horaria, granularidad (diaria/semanal/mensual) → *Llamada siempre en directo*.
*Paginación:* esquema (*page/limit* o *cursor*), límites por página → *Libre extensión de la pantalla por parte del administrador*.
Tablas con el típico *DataTables* que muestra un desplegable para que el usuario elija los resultados por página.

/**
 * Component Wizard JavaScript
 * Maneja la lógica del wizard de creación/edición de componentes
 */

(function() {
    'use strict';

    // Estado del wizard
    const wizardState = {
        currentStep: 1,
        totalSteps: 8,
        componentData: {
            type: '',
            config: {
                data_source: '',
                dimensions: [],
                measures: [],
                aggregation: 'sum',
                filters: [],
                labels: {}, // Etiquetas personalizadas para campos: {campo: 'Etiqueta'}
                style: {
                    color: '#6D0C6D',
                    icon: '',
                    unit: '',
                    suffix: ''
                }
            },
            name: '',
            description: '',
            tags: []
        },
        availableTables: [],
        tableColumns: {},
        previewData: null
    };

    // URLs de la API
    const API_BASE = '/wp/dashboard-dinamico-final/public/api/data';
    const ADMIN_BASE = '/wp/dashboard-dinamico-final/public/admin-panel-bmp/components';

    /**
     * Inicialización
     */
    document.addEventListener('DOMContentLoaded', function() {
        initWizard();
        loadTables();
        attachEventListeners();

        // Cargar componente para edición si existe
        if (window.EDIT_COMPONENT) {
            loadComponentForEdit(window.EDIT_COMPONENT);
        }
    });

    /**
     * Inicializa el wizard
     */
    function initWizard() {
        updateStepIndicators();
        updateNavigationButtons();
    }

    /**
     * Carga un componente existente para edición
     */
    function loadComponentForEdit(component) {
        console.log('Cargando componente para editar:', component);

        // Cargar datos básicos
        wizardState.componentData.id = component.id;
        wizardState.componentData.name = component.name;
        wizardState.componentData.description = component.description || '';
        wizardState.componentData.type = component.type;
        wizardState.componentData.tags = component.tags || [];

        // Cargar configuración
        if (component.config) {
            wizardState.componentData.config.data_source = component.config.data_source || '';
            wizardState.componentData.config.dimensions = component.config.dimensions || [];
            wizardState.componentData.config.measures = component.config.measures || [];
            wizardState.componentData.config.aggregation = component.config.aggregation || 'sum';
            wizardState.componentData.config.filters = component.config.filters || [];
            wizardState.componentData.config.labels = component.config.labels || {};

            if (component.config.style) {
                wizardState.componentData.config.style = {
                    color: component.config.style.color || '#6D0C6D',
                    icon: component.config.style.icon || '',
                    unit: component.config.style.unit || '',
                    suffix: component.config.style.suffix || ''
                };
            }
        }

        // Cargar tabla si ya está seleccionada
        if (wizardState.componentData.config.data_source) {
            loadTableColumns(wizardState.componentData.config.data_source);
        }

        // Marcar tipo de componente en UI (paso 1)
        setTimeout(() => {
            const typeOption = document.querySelector(`.type-option[data-type="${component.type}"]`);
            if (typeOption) {
                document.querySelectorAll('.type-option').forEach(opt => opt.classList.remove('selected'));
                typeOption.classList.add('selected');
                document.getElementById('componentType').value = component.type;
            }

            // Llenar campos del paso 8 (guardar)
            document.getElementById('componentName').value = component.name;
            document.getElementById('componentDescription').value = component.description || '';
            document.getElementById('componentTags').value = (component.tags || []).join(', ');

            // Llenar estilo (paso 6)
            if (component.config && component.config.style) {
                document.getElementById('styleColor').value = component.config.style.color || '#6D0C6D';
                document.getElementById('styleColorText').value = component.config.style.color || '#6D0C6D';
                document.getElementById('styleUnit').value = component.config.style.unit || '';
                document.getElementById('styleSuffix').value = component.config.style.suffix || '';

                if (component.config.style.icon) {
                    selectIcon(component.config.style.icon);
                }
            }

            // Llenar fuente de datos (paso 2)
            const dataSourceSelect = document.getElementById('dataSource');
            if (dataSourceSelect && component.config.data_source) {
                dataSourceSelect.value = component.config.data_source;
            }

            // Marcar agregación seleccionada
            if (component.config.aggregation) {
                const aggOption = document.querySelector(`.aggregation-option[data-agg="${component.config.aggregation}"]`);
                if (aggOption) {
                    document.querySelectorAll('.aggregation-option').forEach(opt => opt.classList.remove('selected'));
                    aggOption.classList.add('selected');
                    const aggInput = document.getElementById('aggregation');
                    if (aggInput) aggInput.value = component.config.aggregation;
                }
            }
        }, 500);

        console.log('Componente cargado en wizardState:', wizardState.componentData);
    }

    /**
     * Adjunta event listeners
     */
    function attachEventListeners() {
        // Navegación
        document.getElementById('nextBtn').addEventListener('click', nextStep);
        document.getElementById('prevBtn').addEventListener('click', prevStep);
        document.getElementById('saveBtn').addEventListener('click', saveComponent);

        // Step 1: Selección de tipo
        document.querySelectorAll('.type-option').forEach(option => {
            option.addEventListener('click', function() {
                selectComponentType(this.dataset.type);
            });
        });

        // Step 2: Selección de fuente de datos
        document.getElementById('dataSource').addEventListener('change', function() {
            wizardState.componentData.config.data_source = this.value;
            if (this.value) {
                loadTableColumns(this.value);
            }
        });

        // Step 3: Agregación
        document.querySelectorAll('.aggregation-option').forEach(option => {
            option.addEventListener('click', function() {
                selectAggregation(this.dataset.agg);
            });
        });

        // Step 4: Filtros
        document.getElementById('addFilterBtn').addEventListener('click', addFilter);

        // Step 5: Estilo
        document.getElementById('styleColor').addEventListener('input', function() {
            document.getElementById('styleColorText').value = this.value;
            wizardState.componentData.config.style.color = this.value;
        });

        // Selector de iconos
        document.querySelectorAll('.icon-item').forEach(item => {
            item.addEventListener('click', function() {
                selectIcon(this.dataset.icon);
            });
        });

        document.getElementById('styleUnit').addEventListener('input', function() {
            wizardState.componentData.config.style.unit = this.value;
        });

        document.getElementById('styleSuffix').addEventListener('input', function() {
            wizardState.componentData.config.style.suffix = this.value;
        });

        // Step 6: Vista previa
        document.getElementById('refreshPreviewBtn').addEventListener('click', loadPreview);

        // Step 7: Información final
        document.getElementById('componentName').addEventListener('input', function() {
            wizardState.componentData.name = this.value;
        });

        document.getElementById('componentDescription').addEventListener('input', function() {
            wizardState.componentData.description = this.value;
        });

        document.getElementById('componentTags').addEventListener('input', function() {
            const tags = this.value.split(',').map(t => t.trim()).filter(t => t);
            wizardState.componentData.tags = tags;
        });
    }

    /**
     * Carga las tablas disponibles desde la API
     */
    async function loadTables() {
        try {
            const response = await fetch(`${API_BASE}/tables`);
            const result = await response.json();

            if (result.success && result.data) {
                wizardState.availableTables = result.data;
                populateTableSelect(result.data);
            } else {
                showError('No se pudieron cargar las tablas de la base de datos');
            }
        } catch (error) {
            console.error('Error loading tables:', error);
            showError('Error al conectar con la API');
        }
    }

    /**
     * Puebla el select de tablas
     */
    function populateTableSelect(tables) {
        const select = document.getElementById('dataSource');
        select.innerHTML = '<option value="">Selecciona una tabla...</option>';

        tables.forEach(table => {
            const option = document.createElement('option');
            option.value = table.name;
            option.textContent = `${table.name} (${table.records.toLocaleString()} registros)`;
            select.appendChild(option);
        });
    }

    /**
     * Carga las columnas de una tabla
     */
    async function loadTableColumns(tableName) {
        try {
            const response = await fetch(`${API_BASE}/columns/${tableName}`);
            const result = await response.json();

            if (result.success && result.data) {
                wizardState.tableColumns[tableName] = result.data;
                displayTableInfo(tableName, result.data);
                // Las columnas se pueblan dinámicamente en el paso 3
            } else {
                showError('No se pudieron cargar las columnas de la tabla');
            }
        } catch (error) {
            console.error('Error loading columns:', error);
            showError('Error al cargar las columnas');
        }
    }

    /**
     * Muestra información de la tabla seleccionada
     */
    function displayTableInfo(tableName, columns) {
        const infoDiv = document.getElementById('tableInfo');
        const contentDiv = document.getElementById('tableInfoContent');

        const tableData = wizardState.availableTables.find(t => t.name === tableName);

        contentDiv.innerHTML = `
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                <div>
                    <strong>Nombre:</strong> ${tableName}
                </div>
                <div>
                    <strong>Registros:</strong> ${tableData ? tableData.records.toLocaleString() : 'N/A'}
                </div>
                <div>
                    <strong>Columnas:</strong> ${columns.length}
                </div>
            </div>
        `;

        infoDiv.style.display = 'block';
    }

    /**
     * Crea un item de campo seleccionable
     */
    function createFieldItem(column, fieldType) {
        const div = document.createElement('div');
        div.className = 'field-item';
        div.dataset.field = column.name;
        div.dataset.type = fieldType;

        div.innerHTML = `
            <label style="cursor: pointer; display: block;">
                <input type="checkbox" value="${column.name}">
                <strong>${column.name}</strong>
                <div style="font-size: 11px; color: #666; margin-top: 3px;">${column.type}</div>
            </label>
        `;

        const checkbox = div.querySelector('input[type="checkbox"]');
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                div.classList.add('selected');
                if (fieldType === 'dimension') {
                    wizardState.componentData.config.dimensions.push(column.name);
                } else {
                    wizardState.componentData.config.measures.push(column.name);
                }
            } else {
                div.classList.remove('selected');
                if (fieldType === 'dimension') {
                    const index = wizardState.componentData.config.dimensions.indexOf(column.name);
                    if (index > -1) wizardState.componentData.config.dimensions.splice(index, 1);
                } else {
                    const index = wizardState.componentData.config.measures.indexOf(column.name);
                    if (index > -1) wizardState.componentData.config.measures.splice(index, 1);
                }
            }
        });

        return div;
    }

    /**
     * Selecciona el tipo de componente
     */
    function selectComponentType(type) {
        // Actualizar UI
        document.querySelectorAll('.type-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');

        // Guardar en estado
        wizardState.componentData.type = type;
        document.getElementById('componentType').value = type;
    }

    /**
     * Selecciona la agregación
     */
    function selectAggregation(agg) {
        // Actualizar UI
        document.querySelectorAll('.aggregation-option').forEach(opt => {
            opt.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');

        // Guardar en estado
        wizardState.componentData.config.aggregation = agg;
        document.getElementById('aggregation').value = agg;
    }

    /**
     * Selecciona un icono del grid
     */
    function selectIcon(icon) {
        // Actualizar UI - remover selección previa
        document.querySelectorAll('.icon-item').forEach(item => {
            item.classList.remove('selected');
        });

        // Marcar el icono seleccionado
        const selectedItem = document.querySelector(`.icon-item[data-icon="${icon}"]`);
        if (selectedItem) {
            selectedItem.classList.add('selected');
        }

        // Actualizar campo oculto
        document.getElementById('styleIcon').value = icon;

        // Actualizar preview
        const previewDiv = document.getElementById('selectedIconPreview');
        const displaySpan = document.getElementById('selectedIconDisplay');
        displaySpan.textContent = icon;
        previewDiv.style.display = 'block';

        // Guardar en estado
        wizardState.componentData.config.style.icon = icon;
    }

    /**
     * Agrega un nuevo filtro
     */
    function addFilter() {
        const filtersList = document.getElementById('filtersList');

        // Si es el primer filtro, limpiar el mensaje
        if (filtersList.querySelector('p')) {
            filtersList.innerHTML = '';
        }

        const tableName = wizardState.componentData.config.data_source;
        if (!tableName) {
            alert('Primero debes seleccionar una fuente de datos');
            return;
        }

        const columns = wizardState.tableColumns[tableName] || [];
        const filterIndex = wizardState.componentData.config.filters.length;

        const filterRow = document.createElement('div');
        filterRow.className = 'filter-row';
        filterRow.dataset.index = filterIndex;

        filterRow.innerHTML = `
            <select class="filter-field" required>
                <option value="">Campo...</option>
                ${columns.map(col => `<option value="${col.name}">${col.name}</option>`).join('')}
            </select>
            <select class="filter-operator" required>
                <option value="=">Igual a</option>
                <option value="!=">Diferente de</option>
                <option value=">">Mayor que</option>
                <option value="<">Menor que</option>
                <option value=">=">Mayor o igual</option>
                <option value="<=">Menor o igual</option>
                <option value="LIKE">Contiene</option>
            </select>
            <input type="text" class="filter-value" placeholder="Valor..." required>
            <button type="button" class="btn btn-secondary btn-small remove-filter">✗</button>
        `;

        filtersList.appendChild(filterRow);

        // Event listener para remover
        filterRow.querySelector('.remove-filter').addEventListener('click', function() {
            const index = parseInt(filterRow.dataset.index);
            wizardState.componentData.config.filters.splice(index, 1);
            filterRow.remove();

            // Si no quedan filtros, mostrar mensaje
            if (filtersList.children.length === 0) {
                filtersList.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No hay filtros configurados.</p>';
            }
        });

        // Event listeners para actualizar el estado
        filterRow.querySelector('.filter-field').addEventListener('change', updateFilters);
        filterRow.querySelector('.filter-operator').addEventListener('change', updateFilters);
        filterRow.querySelector('.filter-value').addEventListener('input', updateFilters);

        // Agregar filtro vacío al estado
        wizardState.componentData.config.filters.push({
            field: '',
            operator: '=',
            value: ''
        });
    }

    /**
     * Actualiza los filtros en el estado
     */
    function updateFilters() {
        const filterRows = document.querySelectorAll('.filter-row');
        wizardState.componentData.config.filters = [];

        filterRows.forEach((row, index) => {
            const field = row.querySelector('.filter-field').value;
            const operator = row.querySelector('.filter-operator').value;
            const value = row.querySelector('.filter-value').value;

            if (field && value) {
                wizardState.componentData.config.filters.push({ field, operator, value });
            }
        });
    }

    /**
     * Renderiza el contenido del paso 3 según el tipo de componente
     */
    function renderStep3Content() {
        const container = document.getElementById('step3Content');
        const type = wizardState.componentData.type;
        const tableName = wizardState.componentData.config.data_source;

        if (!tableName || !wizardState.tableColumns[tableName]) {
            container.innerHTML = '<div class="error-message">Primero selecciona una fuente de datos en el paso anterior</div>';
            return;
        }

        const columns = wizardState.tableColumns[tableName];

        switch (type) {
            case 'kpi':
                renderKPIFieldConfig(container, columns);
                break;
            case 'table':
                renderTableFieldConfig(container, columns);
                break;
            case 'line_chart':
            case 'bar_chart':
            case 'pie_chart':
                renderChartFieldConfig(container, columns, type);
                break;
            default:
                container.innerHTML = '<div class="error-message">Tipo de componente no reconocido</div>';
        }
    }

    /**
     * Configuración de campos para KPI
     */
    function renderKPIFieldConfig(container, columns) {
        container.innerHTML = `
            <h3>Configura el KPI</h3>
            <p style="color: #666;">Un KPI muestra un único valor agregado destacado</p>

            <div class="field-selector">
                <h4 style="margin: 0 0 10px 0;">Métrica (Valor a mostrar)</h4>
                <p style="color: #666; font-size: 13px; margin: 0 0 15px 0;">
                    Selecciona el campo numérico que quieres visualizar
                </p>
                <div id="measuresList" class="field-list"></div>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label>Agregación</label>
                <p style="color: #666; font-size: 13px; margin: 5px 0 15px 0;">
                    ¿Cómo quieres calcular el valor?
                </p>
                <div class="aggregation-options">
                    <div class="aggregation-option selected" data-agg="sum">
                        <strong>SUMA</strong>
                        <div style="font-size: 11px; color: #666; margin-top: 5px;">Total acumulado</div>
                    </div>
                    <div class="aggregation-option" data-agg="avg">
                        <strong>PROMEDIO</strong>
                        <div style="font-size: 11px; color: #666; margin-top: 5px;">Valor medio</div>
                    </div>
                    <div class="aggregation-option" data-agg="count">
                        <strong>CONTAR</strong>
                        <div style="font-size: 11px; color: #666; margin-top: 5px;">Cantidad</div>
                    </div>
                    <div class="aggregation-option" data-agg="min">
                        <strong>MÍNIMO</strong>
                        <div style="font-size: 11px; color: #666; margin-top: 5px;">Menor valor</div>
                    </div>
                    <div class="aggregation-option" data-agg="max">
                        <strong>MÁXIMO</strong>
                        <div style="font-size: 11px; color: #666; margin-top: 5px;">Mayor valor</div>
                    </div>
                </div>
                <input type="hidden" name="aggregation" id="aggregation" value="sum">
            </div>

            <div class="field-selector" style="margin-top: 20px;">
                <h4 style="margin: 0 0 10px 0;">Agrupar por (opcional)</h4>
                <p style="color: #666; font-size: 13px; margin: 0 0 15px 0;">
                    Si seleccionas una agrupación, el KPI mostrará el valor agregado por categoría
                </p>
                <div id="dimensionsList" class="field-list"></div>
            </div>
        `;

        populateFieldSelectorsForType(columns, 'kpi');
        attachAggregationListeners();
    }

    /**
     * Configuración de campos para Tabla
     */
    function renderTableFieldConfig(container, columns) {
        container.innerHTML = `
            <h3>Configura la Tabla</h3>
            <p style="color: #666;">Selecciona las columnas que quieres mostrar en la tabla</p>

            <div class="field-selector">
                <h4 style="margin: 0 0 10px 0;">Columnas a mostrar</h4>
                <p style="color: #666; font-size: 13px; margin: 0 0 15px 0;">
                    Selecciona todos los campos que quieres ver como columnas
                </p>
                <div id="allColumnsList" class="field-list"></div>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label>
                    <input type="checkbox" id="tableGroupingEnabled" style="margin-right: 10px;">
                    <strong>Habilitar agrupación y agregación</strong>
                </label>
                <p style="color: #666; font-size: 13px; margin: 5px 0 0 25px;">
                    Activa esto si quieres agrupar datos y aplicar funciones agregadas (SUM, AVG, etc.)
                </p>
            </div>

            <div id="tableGroupingConfig" style="display: none; margin-top: 20px;">
                <div class="field-selector">
                    <h4 style="margin: 0 0 10px 0;">Agrupar por</h4>
                    <p style="color: #666; font-size: 13px; margin: 0 0 15px 0;">
                        Campos que definirán las filas de la tabla
                    </p>
                    <div id="dimensionsList" class="field-list"></div>
                </div>

                <div class="field-selector" style="margin-top: 20px;">
                    <h4 style="margin: 0 0 10px 0;">Métricas agregadas</h4>
                    <p style="color: #666; font-size: 13px; margin: 0 0 15px 0;">
                        Campos numéricos con agregación (SUM, AVG, etc.)
                    </p>
                    <div id="measuresList" class="field-list"></div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label>Agregación para métricas</label>
                    <div class="aggregation-options">
                        <div class="aggregation-option selected" data-agg="sum">
                            <strong>SUMA</strong>
                        </div>
                        <div class="aggregation-option" data-agg="avg">
                            <strong>PROMEDIO</strong>
                        </div>
                        <div class="aggregation-option" data-agg="count">
                            <strong>CONTAR</strong>
                        </div>
                        <div class="aggregation-option" data-agg="min">
                            <strong>MÍNIMO</strong>
                        </div>
                        <div class="aggregation-option" data-agg="max">
                            <strong>MÁXIMO</strong>
                        </div>
                    </div>
                    <input type="hidden" name="aggregation" id="aggregation" value="sum">
                </div>
            </div>
        `;

        populateFieldSelectorsForType(columns, 'table');
        attachTableGroupingListeners();
        attachAggregationListeners();
    }

    /**
     * Configuración de campos para Gráficos
     */
    function renderChartFieldConfig(container, columns, type) {
        const chartTypeLabel = {
            'line_chart': 'Gráfico de Líneas',
            'bar_chart': 'Gráfico de Barras',
            'pie_chart': 'Gráfico Circular'
        };

        container.innerHTML = `
            <h3>Configura el ${chartTypeLabel[type]}</h3>
            <p style="color: #666;">Define los ejes y valores del gráfico</p>

            <div class="field-selector">
                <h4 style="margin: 0 0 10px 0;">${type === 'pie_chart' ? 'Categorías' : 'Eje X (Categorías)'}</h4>
                <p style="color: #666; font-size: 13px; margin: 0 0 15px 0;">
                    ${type === 'pie_chart' ? 'Campo que define las porciones del gráfico' : 'Campo para el eje horizontal'}
                </p>
                <div id="dimensionsList" class="field-list"></div>
            </div>

            <div class="field-selector" style="margin-top: 20px;">
                <h4 style="margin: 0 0 10px 0;">${type === 'pie_chart' ? 'Valor' : 'Eje Y (Valores)'}</h4>
                <p style="color: #666; font-size: 13px; margin: 0 0 15px 0;">
                    ${type === 'pie_chart' ? 'Campo numérico para calcular el tamaño' : 'Campo numérico para el eje vertical'}
                </p>
                <div id="measuresList" class="field-list"></div>
            </div>

            <div class="form-group" style="margin-top: 20px;">
                <label>Agregación de Valores</label>
                <p style="color: #666; font-size: 13px; margin: 5px 0 15px 0;">
                    Función a aplicar cuando hay múltiples valores por categoría
                </p>
                <div class="aggregation-options">
                    <div class="aggregation-option selected" data-agg="sum">
                        <strong>SUMA</strong>
                        <div style="font-size: 11px; color: #666; margin-top: 5px;">Acumular valores</div>
                    </div>
                    <div class="aggregation-option" data-agg="avg">
                        <strong>PROMEDIO</strong>
                        <div style="font-size: 11px; color: #666; margin-top: 5px;">Valor medio</div>
                    </div>
                    <div class="aggregation-option" data-agg="count">
                        <strong>CONTAR</strong>
                        <div style="font-size: 11px; color: #666; margin-top: 5px;">Cantidad de registros</div>
                    </div>
                    <div class="aggregation-option" data-agg="min">
                        <strong>MÍNIMO</strong>
                        <div style="font-size: 11px; color: #666; margin-top: 5px;">Menor valor</div>
                    </div>
                    <div class="aggregation-option" data-agg="max">
                        <strong>MÁXIMO</strong>
                        <div style="font-size: 11px; color: #666; margin-top: 5px;">Mayor valor</div>
                    </div>
                </div>
                <input type="hidden" name="aggregation" id="aggregation" value="sum">
            </div>
        `;

        populateFieldSelectorsForType(columns, type);
        attachAggregationListeners();
    }

    /**
     * Puebla los selectores de campos según el tipo
     */
    function populateFieldSelectorsForType(columns, type) {
        if (type === 'table') {
            // Para tablas sin agrupación: todas las columnas
            const allColumnsList = document.getElementById('allColumnsList');
            if (allColumnsList) {
                allColumnsList.innerHTML = '';
                columns.forEach(col => {
                    const item = createFieldItemForTable(col);
                    allColumnsList.appendChild(item);
                });
            }
        }

        // Para dimensiones (siempre disponible)
        const dimensionsList = document.getElementById('dimensionsList');
        if (dimensionsList) {
            dimensionsList.innerHTML = '';
            columns.forEach(col => {
                const item = createFieldItem(col, 'dimension');
                dimensionsList.appendChild(item);
            });
        }

        // Para métricas (solo campos numéricos)
        const measuresList = document.getElementById('measuresList');
        if (measuresList) {
            measuresList.innerHTML = '';
            columns.forEach(col => {
                if (col.type === 'INTEGER' || col.type === 'REAL') {
                    const item = createFieldItem(col, 'measure');
                    measuresList.appendChild(item);
                }
            });
        }

        // Pre-seleccionar campos si estamos editando
        setTimeout(() => {
            preselectFields();
        }, 100);
    }

    /**
     * Pre-selecciona los campos cuando estamos editando
     */
    function preselectFields() {
        const dimensions = wizardState.componentData.config.dimensions || [];
        const measures = wizardState.componentData.config.measures || [];

        // Seleccionar dimensiones
        dimensions.forEach(fieldName => {
            const checkbox = document.querySelector(`#dimensionsList input[value="${fieldName}"], #allColumnsList input[value="${fieldName}"]`);
            if (checkbox) {
                checkbox.checked = true;
                checkbox.dispatchEvent(new Event('change'));
            }
        });

        // Seleccionar métricas
        measures.forEach(fieldName => {
            const checkbox = document.querySelector(`#measuresList input[value="${fieldName}"]`);
            if (checkbox) {
                checkbox.checked = true;
                checkbox.dispatchEvent(new Event('change'));
            }
        });
    }

    /**
     * Crea un item de campo para tabla (todas las columnas)
     */
    function createFieldItemForTable(column) {
        const div = document.createElement('div');
        div.className = 'field-item';
        div.dataset.field = column.name;

        div.innerHTML = `
            <label style="cursor: pointer; display: block;">
                <input type="checkbox" value="${column.name}">
                <strong>${column.name}</strong>
                <div style="font-size: 11px; color: #666; margin-top: 3px;">${column.type}</div>
            </label>
        `;

        const checkbox = div.querySelector('input[type="checkbox"]');
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                div.classList.add('selected');
                // Para tablas, agregar a dimensions (columnas)
                if (!wizardState.componentData.config.dimensions.includes(column.name)) {
                    wizardState.componentData.config.dimensions.push(column.name);
                }
            } else {
                div.classList.remove('selected');
                const index = wizardState.componentData.config.dimensions.indexOf(column.name);
                if (index > -1) {
                    wizardState.componentData.config.dimensions.splice(index, 1);
                }
            }
        });

        return div;
    }

    /**
     * Adjunta listeners para la agrupación de tablas
     */
    function attachTableGroupingListeners() {
        const checkbox = document.getElementById('tableGroupingEnabled');
        const config = document.getElementById('tableGroupingConfig');

        if (checkbox && config) {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    config.style.display = 'block';
                    // Limpiar columnas simples
                    wizardState.componentData.config.dimensions = [];
                    const allItems = document.querySelectorAll('#allColumnsList .field-item');
                    allItems.forEach(item => item.classList.remove('selected'));
                    const allChecks = document.querySelectorAll('#allColumnsList input[type="checkbox"]');
                    allChecks.forEach(check => check.checked = false);
                } else {
                    config.style.display = 'none';
                    // Limpiar agrupación
                    wizardState.componentData.config.dimensions = [];
                    wizardState.componentData.config.measures = [];
                    const dimItems = document.querySelectorAll('#dimensionsList .field-item');
                    dimItems.forEach(item => item.classList.remove('selected'));
                    const dimChecks = document.querySelectorAll('#dimensionsList input[type="checkbox"]');
                    dimChecks.forEach(check => check.checked = false);
                    const measItems = document.querySelectorAll('#measuresList .field-item');
                    measItems.forEach(item => item.classList.remove('selected'));
                    const measChecks = document.querySelectorAll('#measuresList input[type="checkbox"]');
                    measChecks.forEach(check => check.checked = false);
                }
            });
        }
    }

    /**
     * Adjunta listeners para las opciones de agregación
     */
    function attachAggregationListeners() {
        document.querySelectorAll('.aggregation-option').forEach(option => {
            option.addEventListener('click', function() {
                selectAggregation(this.dataset.agg);
            });
        });
    }

    /**
     * Renderiza el editor de etiquetas personalizadas
     */
    function renderLabelsEditor() {
        const container = document.getElementById('labelsContainer');
        const dimensions = wizardState.componentData.config.dimensions || [];
        const measures = wizardState.componentData.config.measures || [];
        const allFields = [...new Set([...dimensions, ...measures])];

        if (allFields.length === 0) {
            container.innerHTML = '<p style="color: #999; text-align: center; padding: 40px;">No has seleccionado ningún campo aún. Vuelve al paso anterior para seleccionar campos.</p>';
            return;
        }

        let html = '';

        // Editor para dimensiones
        if (dimensions.length > 0) {
            html += `
                <div class="label-editor">
                    <div class="label-editor-header">
                        <h4>📊 Dimensiones / Columnas</h4>
                        <span style="color: #666; font-size: 13px;">${dimensions.length} campo(s)</span>
                    </div>
            `;

            dimensions.forEach(field => {
                const currentLabel = wizardState.componentData.config.labels[field] || '';
                html += `
                    <div class="label-editor-item">
                        <div class="label-original">
                            Campo: <code>${field}</code>
                        </div>
                        <div class="label-input-wrapper">
                            <input
                                type="text"
                                class="label-input"
                                data-field="${field}"
                                value="${currentLabel}"
                                placeholder="Ej: ${generateSuggestion(field)}"
                            >
                        </div>
                    </div>
                `;
            });

            html += '</div>';
        }

        // Editor para métricas
        if (measures.length > 0) {
            html += `
                <div class="label-editor">
                    <div class="label-editor-header">
                        <h4>📈 Métricas / Valores</h4>
                        <span style="color: #666; font-size: 13px;">${measures.length} campo(s)</span>
                    </div>
            `;

            measures.forEach(field => {
                const currentLabel = wizardState.componentData.config.labels[field] || '';
                html += `
                    <div class="label-editor-item">
                        <div class="label-original">
                            Campo: <code>${field}</code>
                        </div>
                        <div class="label-input-wrapper">
                            <input
                                type="text"
                                class="label-input"
                                data-field="${field}"
                                value="${currentLabel}"
                                placeholder="Ej: ${generateSuggestion(field)}"
                            >
                        </div>
                    </div>
                `;
            });

            html += '</div>';
        }

        container.innerHTML = html;

        // Adjuntar event listeners
        document.querySelectorAll('.label-input').forEach(input => {
            input.addEventListener('input', function() {
                const field = this.dataset.field;
                const label = this.value.trim();

                if (label) {
                    wizardState.componentData.config.labels[field] = label;
                } else {
                    delete wizardState.componentData.config.labels[field];
                }
            });
        });
    }

    /**
     * Genera una sugerencia de etiqueta basada en el nombre del campo
     */
    function generateSuggestion(fieldName) {
        // Convertir snake_case a Title Case
        const words = fieldName.split('_');
        const capitalized = words.map(word => {
            return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
        });

        return capitalized.join(' ');
    }

    /**
     * Obtiene la etiqueta personalizada de un campo o su nombre original
     */
    function getFieldLabel(fieldName) {
        return wizardState.componentData.config.labels[fieldName] || fieldName;
    }

    /**
     * Carga la vista previa del componente
     */
    async function loadPreview() {
        const previewContent = document.getElementById('previewContent');
        previewContent.innerHTML = '<div class="loading">Cargando vista previa con datos reales...</div>';

        try {
            // Construir query params
            const params = new URLSearchParams({
                table: wizardState.componentData.config.data_source,
                limit: 10
            });

            // Agregar filtros si existen
            wizardState.componentData.config.filters.forEach((filter, i) => {
                params.append(`filters[${i}][field]`, filter.field);
                params.append(`filters[${i}][operator]`, filter.operator);
                params.append(`filters[${i}][value]`, filter.value);
            });

            const response = await fetch(`${API_BASE}/query?${params.toString()}`);
            const result = await response.json();

            if (result.success && result.data) {
                wizardState.previewData = result.data;
                renderPreview(result.data);
            } else {
                previewContent.innerHTML = '<div class="error-message">No se pudieron cargar los datos de vista previa</div>';
            }
        } catch (error) {
            console.error('Error loading preview:', error);
            previewContent.innerHTML = '<div class="error-message">Error al cargar la vista previa</div>';
        }
    }

    /**
     * Renderiza la vista previa según el tipo de componente
     */
    function renderPreview(data) {
        const previewContent = document.getElementById('previewContent');
        const type = wizardState.componentData.type;
        const config = wizardState.componentData.config;

        if (!type) {
            previewContent.innerHTML = '<div class="error-message">Selecciona un tipo de componente primero</div>';
            return;
        }

        if (data.length === 0) {
            previewContent.innerHTML = '<div style="text-align: center; color: #999; padding: 40px;">No hay datos disponibles con los filtros aplicados</div>';
            return;
        }

        switch (type) {
            case 'kpi':
                renderKPIPreview(data, config);
                break;
            case 'table':
                renderTablePreview(data, config);
                break;
            case 'line_chart':
            case 'bar_chart':
            case 'pie_chart':
                renderChartPreview(data, config, type);
                break;
            default:
                previewContent.innerHTML = '<div class="error-message">Tipo de componente no soportado</div>';
        }
    }

    /**
     * Renderiza preview de KPI
     */
    function renderKPIPreview(data, config) {
        const previewContent = document.getElementById('previewContent');

        // Calcular valor agregado
        let value = 0;
        const measure = config.measures[0];

        if (!measure) {
            previewContent.innerHTML = '<div class="error-message">Selecciona al menos una métrica</div>';
            return;
        }

        data.forEach(row => {
            const val = parseFloat(row[measure]) || 0;
            switch (config.aggregation) {
                case 'sum':
                    value += val;
                    break;
                case 'avg':
                    value += val;
                    break;
                case 'count':
                    value++;
                    break;
                case 'min':
                    value = value === 0 ? val : Math.min(value, val);
                    break;
                case 'max':
                    value = Math.max(value, val);
                    break;
            }
        });

        if (config.aggregation === 'avg' && data.length > 0) {
            value = value / data.length;
        }

        previewContent.innerHTML = `
            <div style="text-align: center; padding: 40px; background: ${config.style.color}15; border-radius: 8px;">
                <div style="font-size: 48px; margin-bottom: 10px;">${config.style.icon || '📊'}</div>
                <div style="font-size: 42px; font-weight: bold; color: ${config.style.color}; margin: 20px 0;">
                    ${value.toLocaleString('es-ES', { maximumFractionDigits: 2 })}
                    ${config.style.unit ? ' ' + config.style.unit : ''}
                </div>
                <div style="font-size: 14px; color: #666; margin-top: 10px;">
                    ${getFieldLabel(measure)} (${config.aggregation.toUpperCase()})
                    ${config.style.suffix ? ' - ' + config.style.suffix : ''}
                </div>
                <div style="font-size: 12px; color: #999; margin-top: 5px;">
                    Basado en ${data.length} registros
                </div>
            </div>
        `;
    }

    /**
     * Renderiza preview de tabla
     */
    function renderTablePreview(data, config) {
        const previewContent = document.getElementById('previewContent');

        const columns = [...config.dimensions, ...config.measures];

        if (columns.length === 0) {
            previewContent.innerHTML = '<div class="error-message">Selecciona al menos una dimensión o métrica</div>';
            return;
        }

        let html = '<div style="overflow-x: auto;"><table style="width: 100%; border-collapse: collapse;">';

        // Headers
        html += '<thead><tr>';
        columns.forEach(col => {
            html += `<th style="background: ${config.style.color}; color: white; padding: 10px; text-align: left; border: 1px solid #ddd;">${getFieldLabel(col)}</th>`;
        });
        html += '</tr></thead>';

        // Rows
        html += '<tbody>';
        data.slice(0, 5).forEach((row, i) => {
            html += '<tr>';
            columns.forEach(col => {
                html += `<td style="padding: 10px; border: 1px solid #ddd; background: ${i % 2 === 0 ? 'white' : '#f9f9f9'};">${row[col] || 'N/A'}</td>`;
            });
            html += '</tr>';
        });
        html += '</tbody>';

        html += '</table></div>';
        html += `<div style="text-align: center; margin-top: 15px; color: #666; font-size: 12px;">Mostrando 5 de ${data.length} registros</div>`;

        previewContent.innerHTML = html;
    }

    /**
     * Renderiza preview de gráfico con Chart.js
     */
    function renderChartPreview(data, config, type) {
        const previewContent = document.getElementById('previewContent');

        // Validar configuración
        if (config.dimensions.length === 0 || config.measures.length === 0) {
            previewContent.innerHTML = '<div class="error-message">Selecciona al menos una dimensión y una métrica para el gráfico</div>';
            return;
        }

        const dimension = config.dimensions[0]; // Primera dimensión como eje X
        const measure = config.measures[0]; // Primera métrica como valor

        // Preparar datos agregados
        const aggregatedData = aggregateChartData(data, dimension, measure, config.aggregation);

        // Limitar a top 10 para mejor visualización
        const topData = Object.entries(aggregatedData)
            .sort((a, b) => b[1] - a[1])
            .slice(0, 10);

        const labels = topData.map(([label]) => getFieldLabel(dimension) !== dimension ? label : label);
        const values = topData.map(([, value]) => value);

        // Crear canvas
        const canvasId = 'chartPreviewCanvas';
        previewContent.innerHTML = `
            <div style="padding: 20px;">
                <h4 style="text-align: center; color: ${config.style.color}; margin-bottom: 20px;">
                    ${getFieldLabel(measure)} por ${getFieldLabel(dimension)}
                </h4>
                <div style="position: relative; height: 400px; max-width: 800px; margin: 0 auto;">
                    <canvas id="${canvasId}"></canvas>
                </div>
                <div style="text-align: center; margin-top: 15px; color: #666; font-size: 12px;">
                    Mostrando top 10 de ${Object.keys(aggregatedData).length} categorías (${data.length} registros totales)
                </div>
            </div>
        `;

        // Destruir gráfico anterior si existe
        if (window.chartPreviewInstance) {
            window.chartPreviewInstance.destroy();
        }

        // Crear gráfico
        const ctx = document.getElementById(canvasId).getContext('2d');

        const chartConfig = {
            type: type === 'line_chart' ? 'line' : type === 'bar_chart' ? 'bar' : 'pie',
            data: {
                labels: labels,
                datasets: [{
                    label: getFieldLabel(measure),
                    data: values,
                    backgroundColor: type === 'pie_chart'
                        ? generateColorPalette(values.length, config.style.color)
                        : config.style.color + 'CC',
                    borderColor: config.style.color,
                    borderWidth: 2,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: type === 'pie_chart',
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y !== undefined
                                    ? context.parsed.y.toLocaleString('es-ES')
                                    : context.parsed.toLocaleString('es-ES');

                                if (config.style.unit) {
                                    label += ' ' + config.style.unit;
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: type !== 'pie_chart' ? {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('es-ES') + (config.style.unit ? ' ' + config.style.unit : '');
                            }
                        }
                    }
                } : {}
            }
        };

        window.chartPreviewInstance = new Chart(ctx, chartConfig);
    }

    /**
     * Agrega datos para el gráfico
     */
    function aggregateChartData(data, dimension, measure, aggregation) {
        const aggregated = {};

        data.forEach(row => {
            const key = row[dimension] || 'Sin clasificar';
            const value = parseFloat(row[measure]) || 0;

            if (!aggregated[key]) {
                aggregated[key] = {
                    sum: 0,
                    count: 0,
                    min: Infinity,
                    max: -Infinity
                };
            }

            aggregated[key].sum += value;
            aggregated[key].count++;
            aggregated[key].min = Math.min(aggregated[key].min, value);
            aggregated[key].max = Math.max(aggregated[key].max, value);
        });

        // Calcular valor final según agregación
        const result = {};
        Object.keys(aggregated).forEach(key => {
            const agg = aggregated[key];
            switch (aggregation) {
                case 'sum':
                    result[key] = agg.sum;
                    break;
                case 'avg':
                    result[key] = agg.sum / agg.count;
                    break;
                case 'count':
                    result[key] = agg.count;
                    break;
                case 'min':
                    result[key] = agg.min;
                    break;
                case 'max':
                    result[key] = agg.max;
                    break;
                default:
                    result[key] = agg.sum;
            }
        });

        return result;
    }

    /**
     * Genera una paleta de colores basada en un color base
     */
    function generateColorPalette(count, baseColor) {
        const colors = [];
        const baseRGB = hexToRgb(baseColor);

        for (let i = 0; i < count; i++) {
            const factor = 1 - (i / count) * 0.6; // Varía entre 1 y 0.4
            const r = Math.round(baseRGB.r * factor);
            const g = Math.round(baseRGB.g * factor);
            const b = Math.round(baseRGB.b * factor);
            colors.push(`rgba(${r}, ${g}, ${b}, 0.8)`);
        }

        return colors;
    }

    /**
     * Convierte color hexadecimal a RGB
     */
    function hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : { r: 109, g: 12, b: 109 }; // Color por defecto
    }

    /**
     * Navegar al siguiente paso
     */
    function nextStep() {
        if (!validateCurrentStep()) {
            return;
        }

        if (wizardState.currentStep < wizardState.totalSteps) {
            wizardState.currentStep++;
            updateWizardView();

            // Si llegamos al paso 3 (campos), renderizar contenido específico del tipo
            if (wizardState.currentStep === 3) {
                renderStep3Content();
            }

            // Si llegamos al paso 4 (etiquetas), renderizar editor de etiquetas
            if (wizardState.currentStep === 4) {
                renderLabelsEditor();
            }

            // Si llegamos al paso 7 (preview), cargar automáticamente
            if (wizardState.currentStep === 7) {
                loadPreview();
            }

            // Si llegamos al paso 8 (guardar), mostrar resumen
            if (wizardState.currentStep === 8) {
                showConfigSummary();
            }
        }
    }

    /**
     * Navegar al paso anterior
     */
    function prevStep() {
        if (wizardState.currentStep > 1) {
            wizardState.currentStep--;
            updateWizardView();
        }
    }

    /**
     * Valida el paso actual
     */
    function validateCurrentStep() {
        const step = wizardState.currentStep;

        switch (step) {
            case 1:
                if (!wizardState.componentData.type) {
                    alert('Por favor selecciona un tipo de componente');
                    return false;
                }
                break;
            case 2:
                if (!wizardState.componentData.config.data_source) {
                    alert('Por favor selecciona una fuente de datos');
                    return false;
                }
                break;
            case 3:
                const type = wizardState.componentData.type;
                if (type === 'table') {
                    // Para tablas: al menos una columna
                    if (wizardState.componentData.config.dimensions.length === 0 &&
                        wizardState.componentData.config.measures.length === 0) {
                        alert('Por favor selecciona al menos una columna para la tabla');
                        return false;
                    }
                } else {
                    // Para KPIs y gráficos: al menos una métrica
                    if (wizardState.componentData.config.measures.length === 0) {
                        alert('Por favor selecciona al menos una métrica');
                        return false;
                    }
                }
                break;
        }

        return true;
    }

    /**
     * Actualiza la vista del wizard
     */
    function updateWizardView() {
        // Actualizar contenido de pasos
        document.querySelectorAll('.wizard-step-content').forEach(content => {
            content.classList.remove('active');
        });
        document.querySelector(`.wizard-step-content[data-step="${wizardState.currentStep}"]`).classList.add('active');

        // Actualizar indicadores
        updateStepIndicators();

        // Actualizar botones de navegación
        updateNavigationButtons();

        // Scroll al top
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /**
     * Actualiza los indicadores de paso
     */
    function updateStepIndicators() {
        document.querySelectorAll('.wizard-step').forEach(step => {
            const stepNum = parseInt(step.dataset.step);
            step.classList.remove('active', 'completed');

            if (stepNum === wizardState.currentStep) {
                step.classList.add('active');
            } else if (stepNum < wizardState.currentStep) {
                step.classList.add('completed');
            }
        });
    }

    /**
     * Actualiza los botones de navegación
     */
    function updateNavigationButtons() {
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const saveBtn = document.getElementById('saveBtn');

        // Botón anterior
        prevBtn.style.display = wizardState.currentStep > 1 ? 'inline-block' : 'none';

        // Botón siguiente
        nextBtn.style.display = wizardState.currentStep < wizardState.totalSteps ? 'inline-block' : 'none';

        // Botón guardar
        saveBtn.style.display = wizardState.currentStep === wizardState.totalSteps ? 'inline-block' : 'none';
    }

    /**
     * Muestra el resumen de configuración
     */
    function showConfigSummary() {
        const summaryDiv = document.getElementById('configSummary');
        const config = wizardState.componentData.config;

        const typeLabels = {
            'kpi': 'KPI',
            'table': 'Tabla',
            'line_chart': 'Gráfico de Líneas',
            'bar_chart': 'Gráfico de Barras',
            'pie_chart': 'Gráfico Circular'
        };

        summaryDiv.innerHTML = `
            <div class="summary-item">
                <span class="label">Tipo:</span>
                <span class="value">${typeLabels[wizardState.componentData.type]}</span>
            </div>
            <div class="summary-item">
                <span class="label">Fuente de Datos:</span>
                <span class="value">${config.data_source}</span>
            </div>
            <div class="summary-item">
                <span class="label">Dimensiones:</span>
                <span class="value">${config.dimensions.join(', ') || 'Ninguna'}</span>
            </div>
            <div class="summary-item">
                <span class="label">Métricas:</span>
                <span class="value">${config.measures.join(', ')}</span>
            </div>
            <div class="summary-item">
                <span class="label">Agregación:</span>
                <span class="value">${config.aggregation.toUpperCase()}</span>
            </div>
            <div class="summary-item">
                <span class="label">Filtros:</span>
                <span class="value">${config.filters.length} filtro(s)</span>
            </div>
            <div class="summary-item">
                <span class="label">Color:</span>
                <span class="value">
                    <span style="display: inline-block; width: 20px; height: 20px; background: ${config.style.color}; border-radius: 3px; vertical-align: middle;"></span>
                    ${config.style.color}
                </span>
            </div>
        `;
    }

    /**
     * Guarda el componente
     */
    async function saveComponent(e) {
        e.preventDefault();

        // Validación final
        if (!wizardState.componentData.name) {
            alert('Por favor ingresa un nombre para el componente');
            return;
        }

        console.log('Guardando componente:', wizardState.componentData);

        const saveBtn = document.getElementById('saveBtn');
        saveBtn.disabled = true;
        saveBtn.textContent = 'Guardando...';

        // Determinar si es creación o actualización
        const isEdit = !!wizardState.componentData.id;
        const url = isEdit
            ? `${ADMIN_BASE}/update/${wizardState.componentData.id}`
            : `${ADMIN_BASE}/store`;

        try {
            console.log('Enviando a:', url);
            console.log('Modo:', isEdit ? 'Edición' : 'Creación');

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(wizardState.componentData)
            });

            console.log('Response status:', response.status);
            const result = await response.json();
            console.log('Response data:', result);

            if (result.success) {
                showSuccess(isEdit ? 'Componente actualizado exitosamente' : 'Componente guardado exitosamente');
                setTimeout(() => {
                    window.location.href = `${ADMIN_BASE}`;
                }, 1500);
            } else {
                console.error('Error del servidor:', result);
                showError(result.message || 'Error al guardar el componente');
                saveBtn.disabled = false;
                saveBtn.textContent = '💾 Guardar Componente';
            }
        } catch (error) {
            console.error('Error saving component:', error);
            showError('Error al conectar con el servidor: ' + error.message);
            saveBtn.disabled = false;
            saveBtn.textContent = '💾 Guardar Componente';
        }
    }

    /**
     * Muestra un mensaje de error
     */
    function showError(message) {
        const wizardBody = document.querySelector('.wizard-body');
        const existingAlert = wizardBody.querySelector('.error-message');

        if (existingAlert) {
            existingAlert.remove();
        }

        const alert = document.createElement('div');
        alert.className = 'error-message';
        alert.textContent = message;

        wizardBody.insertBefore(alert, wizardBody.firstChild);

        setTimeout(() => alert.remove(), 5000);
    }

    /**
     * Muestra un mensaje de éxito
     */
    function showSuccess(message) {
        const wizardBody = document.querySelector('.wizard-body');
        const existingAlert = wizardBody.querySelector('.success-message');

        if (existingAlert) {
            existingAlert.remove();
        }

        const alert = document.createElement('div');
        alert.className = 'success-message';
        alert.textContent = message;

        wizardBody.insertBefore(alert, wizardBody.firstChild);

        setTimeout(() => alert.remove(), 5000);
    }

})();

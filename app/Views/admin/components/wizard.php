<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - Panel Administrativo BMP</title>
    <link rel="stylesheet" href="/wp/dashboard-dinamico-final/public/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .wizard-container {
            max-width: 1200px;
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .wizard-header {
            padding: 30px;
            border-bottom: 2px solid #e0e0e0;
        }

        .wizard-header h2 {
            margin: 0 0 10px 0;
            color: #6D0C6D;
        }

        .wizard-steps {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            position: relative;
        }

        .wizard-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e0e0e0;
            z-index: 0;
        }

        .wizard-step {
            flex: 1;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .wizard-step .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e0e0;
            color: #666;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 8px;
            transition: all 0.3s;
        }

        .wizard-step.active .step-number {
            background: #6D0C6D;
            color: white;
            transform: scale(1.1);
        }

        .wizard-step.completed .step-number {
            background: #4caf50;
            color: white;
        }

        .wizard-step .step-label {
            font-size: 12px;
            color: #666;
            font-weight: bold;
        }

        .wizard-step.active .step-label {
            color: #6D0C6D;
        }

        .wizard-body {
            padding: 40px;
            min-height: 400px;
        }

        .wizard-step-content {
            display: none;
        }

        .wizard-step-content.active {
            display: block;
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .wizard-footer {
            padding: 20px 40px;
            border-top: 2px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            background: #f8f9fa;
            border-radius: 0 0 8px 8px;
        }

        .component-type-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .type-option {
            background: white;
            border: 3px solid #e0e0e0;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .type-option:hover {
            border-color: #6D0C6D;
            box-shadow: 0 4px 12px rgba(109, 12, 109, 0.1);
        }

        .type-option.selected {
            border-color: #6D0C6D;
            background: #f3e5f5;
        }

        .type-option .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .type-option h3 {
            margin: 10px 0 5px 0;
            color: #010101;
        }

        .type-option p {
            font-size: 13px;
            color: #666;
            margin: 0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #6D0C6D;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }

        .field-selector {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            padding: 20px;
            margin: 15px 0;
        }

        .field-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .field-item {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            padding: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .field-item:hover {
            border-color: #6D0C6D;
        }

        .field-item.selected {
            border-color: #6D0C6D;
            background: #f3e5f5;
        }

        .field-item input[type="checkbox"] {
            margin-right: 8px;
        }

        .filter-builder {
            margin-top: 20px;
        }

        .filter-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            align-items: center;
        }

        .filter-row select,
        .filter-row input {
            flex: 1;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #6D0C6D;
            color: white;
        }

        .btn-primary:hover {
            background: #550A55;
        }

        .btn-secondary {
            background: #666;
            color: white;
        }

        .btn-secondary:hover {
            background: #444;
        }

        .btn-success {
            background: #4caf50;
            color: white;
        }

        .btn-success:hover {
            background: #45a049;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        .preview-box {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            min-height: 200px;
        }

        .preview-box h4 {
            margin: 0 0 15px 0;
            color: #6D0C6D;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .error-message {
            background: #ffebee;
            border: 2px solid #ef5350;
            border-radius: 5px;
            padding: 15px;
            color: #c62828;
            margin-bottom: 20px;
        }

        .success-message {
            background: #e8f5e9;
            border: 2px solid #4caf50;
            border-radius: 5px;
            padding: 15px;
            color: #2e7d32;
            margin-bottom: 20px;
        }

        .aggregation-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .aggregation-option {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .aggregation-option:hover {
            border-color: #6D0C6D;
        }

        .aggregation-option.selected {
            border-color: #6D0C6D;
            background: #f3e5f5;
        }

        .style-options {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .color-picker-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .color-picker-group input[type="color"] {
            width: 60px;
            height: 40px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            cursor: pointer;
        }

        .icon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
            gap: 10px;
            max-height: 400px;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
        }

        .icon-item {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            user-select: none;
        }

        .icon-item:hover {
            border-color: #6D0C6D;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(109, 12, 109, 0.2);
            z-index: 10;
        }

        .icon-item.selected {
            border-color: #6D0C6D;
            border-width: 3px;
            background: #f3e5f5;
            box-shadow: 0 4px 12px rgba(109, 12, 109, 0.3);
        }

        .summary-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .summary-section h4 {
            margin: 0 0 15px 0;
            color: #6D0C6D;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-item .label {
            font-weight: bold;
            color: #666;
        }

        .summary-item .value {
            color: #010101;
        }

        .label-editor {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .label-editor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }

        .label-editor-header h4 {
            margin: 0;
            color: #6D0C6D;
            font-size: 16px;
        }

        .label-editor-item {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            align-items: center;
            padding: 10px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .label-editor-item:last-child {
            margin-bottom: 0;
        }

        .label-original {
            font-weight: bold;
            color: #666;
        }

        .label-original code {
            background: #e0e0e0;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
            color: #010101;
        }

        .label-input-wrapper {
            position: relative;
        }

        .label-input-wrapper input {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .label-input-wrapper input:focus {
            border-color: #6D0C6D;
            outline: none;
        }

        .label-input-wrapper input::placeholder {
            color: #999;
        }
    </style>
</head>
<body>
    <?= view('layouts/header_admin') ?>

    <div class="container" style="padding: 20px;">
        <div class="wizard-container">
            <div class="wizard-header">
                <h2><?= isset($component) ? '✎ Editar Componente' : '+ Crear Nuevo Componente' ?></h2>
                <p style="color: #666; margin: 5px 0 0 0;">
                    <?= isset($component) ? 'Modifica la configuración de tu componente' : 'Sigue los pasos para configurar tu componente visual' ?>
                </p>

                <div class="wizard-steps">
                    <div class="wizard-step active" data-step="1">
                        <div class="step-number">1</div>
                        <div class="step-label">Tipo</div>
                    </div>
                    <div class="wizard-step" data-step="2">
                        <div class="step-number">2</div>
                        <div class="step-label">Datos</div>
                    </div>
                    <div class="wizard-step" data-step="3">
                        <div class="step-number">3</div>
                        <div class="step-label">Campos</div>
                    </div>
                    <div class="wizard-step" data-step="4">
                        <div class="step-number">4</div>
                        <div class="step-label">Etiquetas</div>
                    </div>
                    <div class="wizard-step" data-step="5">
                        <div class="step-number">5</div>
                        <div class="step-label">Filtros</div>
                    </div>
                    <div class="wizard-step" data-step="6">
                        <div class="step-number">6</div>
                        <div class="step-label">Estilo</div>
                    </div>
                    <div class="wizard-step" data-step="7">
                        <div class="step-number">7</div>
                        <div class="step-label">Vista Previa</div>
                    </div>
                    <div class="wizard-step" data-step="8">
                        <div class="step-number">8</div>
                        <div class="step-label">Guardar</div>
                    </div>
                </div>
            </div>

            <form id="componentForm">
                <div class="wizard-body">
                    <!-- Step 1: Tipo de Componente -->
                    <div class="wizard-step-content active" data-step="1">
                        <h3>Selecciona el tipo de componente</h3>
                        <p style="color: #666;">Elige qué tipo de visualización deseas crear</p>

                        <div class="component-type-grid">
                            <div class="type-option" data-type="kpi">
                                <div class="icon">📊</div>
                                <h3>KPI</h3>
                                <p>Indicador numérico con valor destacado</p>
                            </div>
                            <div class="type-option" data-type="table">
                                <div class="icon">📋</div>
                                <h3>Tabla</h3>
                                <p>Datos tabulares con columnas y filas</p>
                            </div>
                            <div class="type-option" data-type="line_chart">
                                <div class="icon">📈</div>
                                <h3>Gráfico de Líneas</h3>
                                <p>Evolución de datos en el tiempo</p>
                            </div>
                            <div class="type-option" data-type="bar_chart">
                                <div class="icon">📊</div>
                                <h3>Gráfico de Barras</h3>
                                <p>Comparación entre categorías</p>
                            </div>
                            <div class="type-option" data-type="pie_chart">
                                <div class="icon">🥧</div>
                                <h3>Gráfico Circular</h3>
                                <p>Distribución porcentual</p>
                            </div>
                        </div>

                        <input type="hidden" name="type" id="componentType" required>
                    </div>

                    <!-- Step 2: Fuente de Datos -->
                    <div class="wizard-step-content" data-step="2">
                        <h3>Selecciona la fuente de datos</h3>
                        <p style="color: #666;">Elige la tabla de la base de datos que contiene los datos</p>

                        <div class="form-group">
                            <label>Tabla de Datos</label>
                            <select name="data_source" id="dataSource" required>
                                <option value="">Cargando tablas...</option>
                            </select>
                            <small>Los datos se obtendrán de esta tabla de la base de datos SQLite</small>
                        </div>

                        <div id="tableInfo" style="display: none; margin-top: 20px;">
                            <div class="preview-box">
                                <h4>Información de la Tabla</h4>
                                <div id="tableInfoContent"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Configuración de Campos -->
                    <div class="wizard-step-content" data-step="3">
                        <div id="step3Content"></div>
                    </div>

                    <!-- Step 4: Etiquetas Personalizadas -->
                    <div class="wizard-step-content" data-step="4">
                        <h3>Personaliza las etiquetas</h3>
                        <p style="color: #666;">Cambia los nombres de los campos para que sean más descriptivos en la visualización</p>

                        <div id="labelsContainer" style="margin-top: 20px;">
                            <p style="color: #999; text-align: center; padding: 40px;">
                                Selecciona campos en el paso anterior para personalizar sus etiquetas aquí.
                            </p>
                        </div>

                        <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 5px; border-left: 4px solid #2196f3;">
                            <strong style="color: #1976d2;">💡 Consejo:</strong>
                            <p style="margin: 5px 0 0 0; color: #666; font-size: 13px;">
                                Las etiquetas personalizadas solo afectan cómo se muestran los campos en la visualización.
                                Los nombres originales de la base de datos no cambian.
                            </p>
                        </div>
                    </div>

                    <!-- Step 5: Filtros -->
                    <div class="wizard-step-content" data-step="5">
                        <h3>Configura los filtros (opcional)</h3>
                        <p style="color: #666;">Define filtros para limitar los datos mostrados</p>

                        <div class="filter-builder">
                            <div id="filtersList">
                                <p style="color: #999; text-align: center; padding: 20px;">
                                    No hay filtros configurados. Haz clic en "Agregar Filtro" para comenzar.
                                </p>
                            </div>
                            <button type="button" class="btn btn-secondary btn-small" id="addFilterBtn">
                                + Agregar Filtro
                            </button>
                        </div>
                    </div>

                    <!-- Step 6: Estilo -->
                    <div class="wizard-step-content" data-step="6">
                        <h3>Personaliza el estilo</h3>
                        <p style="color: #666;">Configura la apariencia visual del componente</p>

                        <div class="form-group" style="margin-bottom: 30px;">
                            <label>Color Principal</label>
                            <div class="color-picker-group">
                                <input type="color" name="style_color" id="styleColor" value="#6D0C6D">
                                <input type="text" id="styleColorText" value="#6D0C6D" readonly style="width: 100px;">
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 30px;">
                            <label>Icono (Selecciona uno)</label>
                            <p style="color: #666; font-size: 13px; margin: 5px 0 15px 0;">Haz clic en un icono para seleccionarlo</p>
                            <div id="iconGrid" class="icon-grid">
                                <!-- Iconos generales -->
                                <div class="icon-item" data-icon="📊">📊</div>
                                <div class="icon-item" data-icon="📈">📈</div>
                                <div class="icon-item" data-icon="📉">📉</div>
                                <div class="icon-item" data-icon="📋">📋</div>
                                <div class="icon-item" data-icon="📌">📌</div>
                                <div class="icon-item" data-icon="📍">📍</div>
                                <div class="icon-item" data-icon="💹">💹</div>
                                <div class="icon-item" data-icon="💰">💰</div>
                                <div class="icon-item" data-icon="💵">💵</div>
                                <div class="icon-item" data-icon="🎯">🎯</div>
                                <div class="icon-item" data-icon="⚡">⚡</div>
                                <div class="icon-item" data-icon="🔥">🔥</div>
                                <div class="icon-item" data-icon="⭐">⭐</div>
                                <div class="icon-item" data-icon="✨">✨</div>
                                <div class="icon-item" data-icon="🏆">🏆</div>
                                <div class="icon-item" data-icon="🎖️">🎖️</div>

                                <!-- Iconos forestales y naturaleza -->
                                <div class="icon-item" data-icon="🌲">🌲</div>
                                <div class="icon-item" data-icon="🌳">🌳</div>
                                <div class="icon-item" data-icon="🌴">🌴</div>
                                <div class="icon-item" data-icon="🌱">🌱</div>
                                <div class="icon-item" data-icon="🌿">🌿</div>
                                <div class="icon-item" data-icon="🍃">🍃</div>
                                <div class="icon-item" data-icon="🍂">🍂</div>
                                <div class="icon-item" data-icon="🌾">🌾</div>
                                <div class="icon-item" data-icon="🌻">🌻</div>
                                <div class="icon-item" data-icon="🌼">🌼</div>
                                <div class="icon-item" data-icon="🌺">🌺</div>
                                <div class="icon-item" data-icon="🌸">🌸</div>
                                <div class="icon-item" data-icon="🏔️">🏔️</div>
                                <div class="icon-item" data-icon="⛰️">⛰️</div>
                                <div class="icon-item" data-icon="🗻">🗻</div>
                                <div class="icon-item" data-icon="🏞️">🏞️</div>

                                <!-- Iconos de medición y datos -->
                                <div class="icon-item" data-icon="📏">📏</div>
                                <div class="icon-item" data-icon="📐">📐</div>
                                <div class="icon-item" data-icon="🔬">🔬</div>
                                <div class="icon-item" data-icon="🔭">🔭</div>
                                <div class="icon-item" data-icon="🧪">🧪</div>
                                <div class="icon-item" data-icon="⚗️">⚗️</div>
                                <div class="icon-item" data-icon="🧬">🧬</div>
                                <div class="icon-item" data-icon="🔍">🔍</div>
                                <div class="icon-item" data-icon="🔎">🔎</div>

                                <!-- Iconos de ubicación y geografía -->
                                <div class="icon-item" data-icon="🗺️">🗺️</div>
                                <div class="icon-item" data-icon="🧭">🧭</div>
                                <div class="icon-item" data-icon="🌍">🌍</div>
                                <div class="icon-item" data-icon="🌎">🌎</div>
                                <div class="icon-item" data-icon="🌏">🌏</div>
                                <div class="icon-item" data-icon="🏘️">🏘️</div>
                                <div class="icon-item" data-icon="🏙️">🏙️</div>
                                <div class="icon-item" data-icon="🏗️">🏗️</div>
                                <div class="icon-item" data-icon="🏛️">🏛️</div>

                                <!-- Iconos de clima y ambiente -->
                                <div class="icon-item" data-icon="☀️">☀️</div>
                                <div class="icon-item" data-icon="🌤️">🌤️</div>
                                <div class="icon-item" data-icon="⛅">⛅</div>
                                <div class="icon-item" data-icon="🌦️">🌦️</div>
                                <div class="icon-item" data-icon="🌧️">🌧️</div>
                                <div class="icon-item" data-icon="⛈️">⛈️</div>
                                <div class="icon-item" data-icon="🌩️">🌩️</div>
                                <div class="icon-item" data-icon="❄️">❄️</div>
                                <div class="icon-item" data-icon="💧">💧</div>
                                <div class="icon-item" data-icon="💦">💦</div>
                                <div class="icon-item" data-icon="🌊">🌊</div>

                                <!-- Iconos de animales y fauna -->
                                <div class="icon-item" data-icon="🦌">🦌</div>
                                <div class="icon-item" data-icon="🦊">🦊</div>
                                <div class="icon-item" data-icon="🐻">🐻</div>
                                <div class="icon-item" data-icon="🦅">🦅</div>
                                <div class="icon-item" data-icon="🦉">🦉</div>
                                <div class="icon-item" data-icon="🦋">🦋</div>
                                <div class="icon-item" data-icon="🐝">🐝</div>
                                <div class="icon-item" data-icon="🐛">🐛</div>

                                <!-- Iconos de herramientas y trabajo -->
                                <div class="icon-item" data-icon="🔨">🔨</div>
                                <div class="icon-item" data-icon="🔧">🔧</div>
                                <div class="icon-item" data-icon="⚙️">⚙️</div>
                                <div class="icon-item" data-icon="🛠️">🛠️</div>
                                <div class="icon-item" data-icon="⚒️">⚒️</div>
                                <div class="icon-item" data-icon="🪓">🪓</div>
                                <div class="icon-item" data-icon="🪚">🪚</div>

                                <!-- Iconos de números y símbolos -->
                                <div class="icon-item" data-icon="➕">➕</div>
                                <div class="icon-item" data-icon="➖">➖</div>
                                <div class="icon-item" data-icon="✖️">✖️</div>
                                <div class="icon-item" data-icon="➗">➗</div>
                                <div class="icon-item" data-icon="🔢">🔢</div>
                                <div class="icon-item" data-icon="#️⃣">#️⃣</div>
                                <div class="icon-item" data-icon="💯">💯</div>
                                <div class="icon-item" data-icon="🔺">🔺</div>
                                <div class="icon-item" data-icon="🔻">🔻</div>
                                <div class="icon-item" data-icon="⬆️">⬆️</div>
                                <div class="icon-item" data-icon="⬇️">⬇️</div>
                                <div class="icon-item" data-icon="↗️">↗️</div>
                                <div class="icon-item" data-icon="↘️">↘️</div>

                                <!-- Iconos de control y estado -->
                                <div class="icon-item" data-icon="✅">✅</div>
                                <div class="icon-item" data-icon="❌">❌</div>
                                <div class="icon-item" data-icon="⚠️">⚠️</div>
                                <div class="icon-item" data-icon="🚫">🚫</div>
                                <div class="icon-item" data-icon="ℹ️">ℹ️</div>
                                <div class="icon-item" data-icon="❓">❓</div>
                                <div class="icon-item" data-icon="❗">❗</div>
                            </div>
                            <input type="hidden" name="style_icon" id="styleIcon">
                            <div id="selectedIconPreview" style="margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 5px; display: none; text-align: center;">
                                <p style="margin: 0 0 10px 0; color: #666; font-size: 13px;">Icono seleccionado:</p>
                                <div style="font-size: 48px;"><span id="selectedIconDisplay"></span></div>
                            </div>
                        </div>

                        <div class="style-options" style="margin-top: 20px;">
                            <div class="form-group">
                                <label>Unidad de Medida</label>
                                <input type="text" name="style_unit" id="styleUnit" placeholder="ej: m³, ha, €, kg">
                                <small>Opcional: Se mostrará junto al valor (ej: "1,234 m³")</small>
                            </div>

                            <div class="form-group">
                                <label>Sufijo</label>
                                <input type="text" name="style_suffix" id="styleSuffix" placeholder="ej: árboles, parcelas, hectáreas">
                                <small>Opcional: Texto después del valor</small>
                            </div>
                        </div>
                    </div>

                    <!-- Step 7: Vista Previa -->
                    <div class="wizard-step-content" data-step="7">
                        <h3>Vista Previa</h3>
                        <p style="color: #666;">Revisa cómo se verá tu componente con datos reales</p>

                        <div class="preview-box">
                            <h4>Previsualización</h4>
                            <div id="previewContent">
                                <div class="loading">Cargando vista previa...</div>
                            </div>
                        </div>

                        <div style="margin-top: 20px;">
                            <button type="button" class="btn btn-secondary btn-small" id="refreshPreviewBtn">
                                🔄 Actualizar Vista Previa
                            </button>
                        </div>
                    </div>

                    <!-- Step 8: Guardar -->
                    <div class="wizard-step-content" data-step="8">
                        <h3>Información del Componente</h3>
                        <p style="color: #666;">Completa los detalles finales antes de guardar</p>

                        <div class="form-group">
                            <label>Nombre del Componente *</label>
                            <input type="text" name="name" id="componentName" required placeholder="ej: Volumen Total por Municipio">
                            <small>Nombre descriptivo que aparecerá en los dashboards</small>
                        </div>

                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea name="description" id="componentDescription" placeholder="Describe qué muestra este componente..."></textarea>
                            <small>Opcional: Ayuda a otros usuarios a entender el propósito del componente</small>
                        </div>

                        <div class="form-group">
                            <label>Tags (separados por coma)</label>
                            <input type="text" name="tags" id="componentTags" placeholder="ej: forestal, municipios, volumen">
                            <small>Opcional: Palabras clave para facilitar la búsqueda</small>
                        </div>

                        <div class="summary-section">
                            <h4>Resumen de Configuración</h4>
                            <div id="configSummary"></div>
                        </div>
                    </div>
                </div>

                <div class="wizard-footer">
                    <div>
                        <button type="button" class="btn btn-secondary" id="prevBtn" style="display: none;">
                            ← Anterior
                        </button>
                    </div>
                    <div>
                        <a href="<?= admin_url('components') ?>" class="btn btn-secondary" style="margin-right: 10px;">
                            Cancelar
                        </a>
                        <button type="button" class="btn btn-primary" id="nextBtn">
                            Siguiente →
                        </button>
                        <button type="submit" class="btn btn-success" id="saveBtn" style="display: none;">
                            💾 Guardar Componente
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Pasar datos del componente a editar si existe
        <?php if (isset($component)): ?>
        window.EDIT_COMPONENT = <?= json_encode($component) ?>;
        <?php endif; ?>
    </script>
    <script src="/wp/dashboard-dinamico-final/public/js/component-wizard.js"></script>
</body>
</html>

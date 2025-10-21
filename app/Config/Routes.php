<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Landing Page Pública
$routes->get('/', 'PublicController::index');
$routes->get('/dashboard', 'PublicController::dashboard');
$routes->get('/dashboard/(:segment)', 'PublicController::dashboard/$1');

// API REST (Hito 4)
$routes->group('api/data', function($routes) {
    $routes->get('tables', 'API\DataController::tables');
    $routes->get('columns/(:segment)', 'API\DataController::columns/$1');
    $routes->get('query', 'API\DataController::query');
    $routes->post('query', 'API\DataController::queryPost');
    $routes->post('aggregate', 'API\DataController::aggregate');
    $routes->get('municipalities', 'API\DataController::municipalities');
    $routes->get('species', 'API\DataController::species');
    $routes->get('formations', 'API\DataController::formations');
});

$routes->group('api/cache', function($routes) {
    $routes->delete('clear', 'API\DataController::clearCache');
    $routes->get('stats', 'API\DataController::cacheStats');
});

// API CKAN (Hito 5 - Integración CKAN API)
$routes->group('api/ckan', ['namespace' => 'App\Controllers\Api'], function($routes) {
    $routes->get('municipios', 'CkanController::municipios');
    $routes->get('poblacion', 'CkanController::poblacion');
    $routes->get('incendios', 'CkanController::incendios');
    $routes->post('clear-cache', 'CkanController::clearCache');
    $routes->get('cache/stats', 'CkanController::cacheStats');
});

// Autenticación (Login)
$routes->get('/admin-panel-bmp/login', 'Auth::login');
$routes->post('/admin-panel-bmp/authenticate', 'Auth::authenticate');
$routes->get('/admin-panel-bmp/logout', 'Auth::logout');

// Panel Administrativo (protegido con AuthFilter)
$routes->group('admin-panel-bmp', ['filter' => 'auth'], function($routes) {
    // Dashboard principal
    $routes->get('/', 'Admin\Dashboard::index');
    $routes->get('dashboard', 'Admin\Dashboard::index');

    // Gestión de usuarios
    $routes->get('users', 'Admin\Users::index');
    $routes->get('users/create', 'Admin\Users::create');
    $routes->post('users/store', 'Admin\Users::store');
    $routes->get('users/edit/(:num)', 'Admin\Users::edit/$1');
    $routes->post('users/update/(:num)', 'Admin\Users::update/$1');
    $routes->post('users/delete/(:num)', 'Admin\Users::delete/$1');

    // Gestión de datos ETL (Hito 3)
    $routes->get('data-manager', 'Admin\DataManager::index');
    $routes->post('data-manager/run-etl', 'Admin\DataManager::runETL');
    $routes->get('data-manager/logs', 'Admin\DataManager::viewLog');
    $routes->get('data-manager/logs/(:segment)', 'Admin\DataManager::viewLog/$1');
    $routes->get('data-manager/download-log/(:segment)', 'Admin\DataManager::downloadLog/$1');
    $routes->get('data-manager/download-db/(:segment)', 'Admin\DataManager::downloadDatabase/$1');
    $routes->post('data-manager/restore/(:segment)', 'Admin\DataManager::restoreVersion/$1');
    $routes->post('data-manager/delete/(:segment)', 'Admin\DataManager::deleteVersion/$1');

    // Gestión de componentes (Hito 4)
    $routes->get('components', 'Admin\Components::index');
    $routes->get('components/create', 'Admin\Components::create');
    $routes->post('components/store', 'Admin\Components::store');
    $routes->get('components/edit/(:segment)', 'Admin\Components::edit/$1');
    $routes->post('components/update/(:segment)', 'Admin\Components::update/$1');
    $routes->post('components/delete/(:segment)', 'Admin\Components::delete/$1');
    $routes->post('components/duplicate/(:segment)', 'Admin\Components::duplicate/$1');
    $routes->post('components/preview', 'Admin\Components::preview');
    $routes->get('components/show/(:segment)', 'Admin\Components::show/$1');

    // Gestión de dashboards (futuro - Hito 6)
    // $routes->get('dashboards', 'Admin\Dashboards::index');
});

// Testing de Componentes (Hito 5)
$routes->get('/test-components', 'TestComponents::index');
$routes->get('/test-components/render/(:segment)', 'TestComponents::render/$1');
$routes->get('/test-components/render-all', 'TestComponents::renderAll');

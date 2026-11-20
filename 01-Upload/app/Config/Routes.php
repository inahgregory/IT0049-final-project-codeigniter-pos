<?php
use CodeIgniter\Router\RouteCollection;
/** @var RouteCollection $routes */
$routes->setAutoRoute(false);
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attempt');
$routes->get('setup', 'Auth::setup');
$routes->post('setup', 'Auth::createFirst');
$routes->get('about', 'Dashboard::about');
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->post('logout', 'Auth::logout');
    foreach (['products' => 'Products', 'customers' => 'Customers', 'staff' => 'Staff'] as $path => $controller) {
        $routes->get($path, $controller.'::index');
        $routes->get($path.'/create', $controller.'::create');
        $routes->post($path, $controller.'::store');
        $routes->get($path.'/(:num)/edit', $controller.'::edit/$1');
        $routes->post($path.'/(:num)', $controller.'::update/$1');
        $routes->get($path.'/(:num)/remove', $controller.'::remove/$1');
        $routes->post($path.'/(:num)/delete', $controller.'::destroy/$1');
    }
    $routes->get('sales', 'Sales::index');
    $routes->get('sales/create', 'Sales::create');
    $routes->post('sales', 'Sales::store');
    $routes->get('sales/(:num)', 'Sales::show/$1');
    $routes->get('media/(:segment)/(:segment)', 'Media::show/$1/$2');
});

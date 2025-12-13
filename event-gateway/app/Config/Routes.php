<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Health check
$routes->get('/', 'v1\\HeartBeat::index');
$routes->get('v1/heartbeat', 'v1\\HeartBeat::index');

// Orders
$routes->post('v1/order', 'v1\\OrderController::create');



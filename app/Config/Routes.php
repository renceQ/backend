<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('/getData', 'MainController::getData');
$routes->get('/getDatas', 'MainController::getDatas');
$routes->get('/getcat', 'MainController::getcat');
$routes->post('/sav', 'MainController::sav');
$routes->post('/save', 'MainController::save');
$routes->post('/del', 'MainController::del');
$routes->post('/savecateg', 'MainController::savecateg');


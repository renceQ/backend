<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('/getData', 'MainController::getData');
$routes->get('/getDatas', 'MainController::getDatas');
$routes->get('/getevent', 'MainController::getevent');
$routes->get('/getcat', 'MainController::getcat');
$routes->post('/save', 'MainController::save');
$routes->get('/getUserData/(:any)', 'MainController::getUserData/$1');


$routes->post('/saveBooking', 'MainController::saveBooking');
$routes->post('/del', 'MainController::del');
$routes->post('/savecateg', 'MainController::savecateg');
$routes->match(['post', 'get'],'/getsize', 'MainController::getsize');
$routes->post('/editcateg', 'MainController::editcateg');
$routes->match(['post', 'get'],'/api/login', 'MainController::login');
$routes->match(['post', 'get'],'/api/register', 'MainController::register');


// get products by category
$routes->get('getProductsByCategory/(:num)', 'MainController::getProductsByCategory/$1');





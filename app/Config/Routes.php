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
$routes->post('/sav', 'MainController::sav');
$routes->post('/save', 'MainController::save');
$routes->post('/saveBooking', 'MainController::saveBooking');
$routes->post('/del', 'MainController::del');
$routes->post('/savecateg', 'MainController::savecateg');
$routes->match(['post', 'get'],'/getsize', 'MainController::getsize');
$routes->post('/editcateg', 'MainController::editcateg');

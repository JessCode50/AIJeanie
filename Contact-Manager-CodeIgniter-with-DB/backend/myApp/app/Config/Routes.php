<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// $routes->get('/', 'Home::index');
$routes->get('/', 'Home::getContacts'); 
$routes->get('contacts', 'Home::getContacts');
$routes->post('contacts/edit', 'Home::editContacts');
$routes->post('contacts/new', 'Home::newContacts');
$routes->post('contacts/delete', 'Home::deleteContacts');

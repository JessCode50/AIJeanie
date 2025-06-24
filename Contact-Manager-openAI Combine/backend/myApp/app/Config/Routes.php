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

$routes->post('contacts/ai', 'AiController::chat'); 
$routes->post('contacts/ai/clear', 'AiController::clear'); 
$routes->post('contacts/ai/view', 'AiController::session_view'); 
$routes->post('contacts/ai/log', 'AiController::history_log'); 
$routes->post('contacts/ai/proceed', 'AiController::proceed'); 
$routes->post('contacts/ai/ticketID', 'AiController::sum_cat_AI'); 
$routes->post('contacts/ai/client', 'AiController::enterClientInfo'); 
$routes->post('contacts/ai/reject', 'AiController::rejected');



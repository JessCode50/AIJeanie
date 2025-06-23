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

// OPTIONS routes for CORS preflight
$routes->options('contacts', function() { return ''; });
$routes->options('contacts/edit', function() { return ''; });
$routes->options('contacts/new', function() { return ''; });
$routes->options('contacts/delete', function() { return ''; });
$routes->options('ai', function() { return ''; });
$routes->options('ai/chat', function() { return ''; });
$routes->options('ai/clear', function() { return ''; });
$routes->options('ai/session_view', function() { return ''; });
$routes->options('ai/history_log', function() { return ''; });
$routes->options('ai/proceed', function() { return ''; });
$routes->options('ai/rejected', function() { return ''; });
$routes->options('ai/debug_session', function() { return ''; });
$routes->options('server/info', function() { return ''; });
$routes->options('server/disk', function() { return ''; });
$routes->options('server/status', function() { return ''; });
$routes->options('server/services', function() { return ''; });

// HEAD routes for connection status checks
$routes->head('ai', 'AiController::index');

// AI Routes
$routes->get('ai', 'AiController::index');
$routes->post('ai/chat', 'AiController::chat'); 
$routes->post('ai/clear', 'AiController::clear'); 
$routes->post('ai/session_view', 'AiController::session_view'); 
$routes->post('ai/history_log', 'AiController::history_log'); 
$routes->post('ai/proceed', 'AiController::proceed');
$routes->post('ai/rejected', 'AiController::rejected');
$routes->get('ai/debug_session', 'AiController::debug_session');

// Server Monitoring Routes (for AI integration)
$routes->options('server/info', function() { return ''; });
$routes->options('server/disk', function() { return ''; });
$routes->options('server/status', function() { return ''; });
$routes->get('server/info', 'ServerMonitorController::getServerInfo');
$routes->get('server/disk', 'ServerMonitorController::getDiskUsage');
$routes->get('server/status', 'ServerMonitorController::getServerStatus');
$routes->get('server/services', 'ServerMonitorController::getServerServices');

// Debug route for API testing
$routes->get('ai/debug/load', 'AiController::debug_load_api');
$routes->get('ai/debug/status', 'AiController::debug_server_status');



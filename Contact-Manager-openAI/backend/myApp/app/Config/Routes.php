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

// AI Assistant Routes
// $routes->post('contacts/ai', 'AiController::chat');
// $routes->options('contacts/ai', 'AiController::options');
// $routes->post('contacts/ai/clear', 'AiController::clear');
// $routes->options('contacts/ai/clear', 'AiController::options');
// $routes->post('contacts/ai/view', 'AiController::session_view');
// $routes->options('contacts/ai/view', 'AiController::options');
// $routes->post('contacts/ai/log', 'AiController::history_log');
// $routes->options('contacts/ai/log', 'AiController::options');
// $routes->post('contacts/ai/proceed', 'AiController::proceed');
// $routes->options('contacts/ai/proceed', 'AiController::options');
// $routes->post('contacts/ai/ticketID', 'AiController::sum_cat_AI');
// $routes->options('contacts/ai/ticketID', 'AiController::options');
// $routes->post('contacts/ai/client', 'AiController::enterClientInfo');
// $routes->options('contacts/ai/client', 'AiController::options');
// $routes->post('contacts/ai/reject', 'AiController::rejected');
// $routes->options('contacts/ai/reject', 'AiController::options');

$routes->post('contacts/ai', 'AiController::chat'); 
$routes->post('contacts/ai/clear', 'AiController::clear'); 
$routes->post('contacts/ai/view', 'AiController::session_view'); 
$routes->post('contacts/ai/log', 'AiController::history_log'); 
$routes->post('contacts/ai/proceed', 'AiController::proceed'); 
$routes->post('contacts/ai/ticketID', 'AiController::sum_cat_AI'); 
$routes->post('contacts/ai/client', 'AiController::enterClientInfo'); 
$routes->post('contacts/ai/reject', 'AiController::rejected');

// NEW ENHANCED API ROUTES

// Ticket Management Routes
$routes->post('contacts/tickets/create', 'AiController::createTicket');
$routes->post('contacts/tickets/update', 'AiController::updateTicket');
$routes->post('contacts/tickets/delete', 'AiController::deleteTicket');
$routes->get('contacts/tickets/list', 'AiController::listTickets');
$routes->options('contacts/tickets/list', 'AiController::listTickets');

// Client Management Routes
$routes->get('contacts/clients/list', 'AiController::listClients');
$routes->options('contacts/clients/list', 'AiController::listClients');
$routes->post('contacts/clients/create', 'AiController::createClient');
$routes->post('contacts/clients/update', 'AiController::updateClient');
$routes->post('contacts/clients/delete', 'AiController::deleteClient');

// Enhanced Server Management Routes
$routes->get('contacts/server/load', 'AiController::getSystemLoad');
$routes->options('contacts/server/load', 'AiController::getSystemLoad');
$routes->get('contacts/server/accounts', 'AiController::getAccountsList');
$routes->options('contacts/server/accounts', 'AiController::getAccountsList');
$routes->get('contacts/server/email', 'AiController::getEmailAccounts');
$routes->get('contacts/server/ssl', 'AiController::getSSLStatus');
$routes->get('contacts/server/backup', 'AiController::getBackupStatus');

// Database Management Routes
$routes->get('contacts/database/list', 'AiController::listDatabases');
$routes->post('contacts/database/create', 'AiController::createDatabase');
$routes->post('contacts/database/delete', 'AiController::deleteDatabase');

// Email Management Routes
$routes->post('contacts/email/create', 'AiController::createEmailAccount');
$routes->post('contacts/email/delete', 'AiController::deleteEmailAccount');
$routes->get('contacts/email/list', 'AiController::getEmailAccounts');

// Domain Management Routes
$routes->get('contacts/domains/list', 'AiController::listDomains');
$routes->post('contacts/domains/create', 'AiController::createDomain');

// Analytics and Reporting Routes
$routes->get('contacts/analytics', 'AiController::getAnalytics');
$routes->get('contacts/reports/usage', 'AiController::getUsageReport');
$routes->get('contacts/reports/revenue', 'AiController::getRevenueReport');
$routes->options('contacts/reports/revenue', 'AiController::getRevenueReport');

// NEW REAL HOSTING DATA ROUTES
$routes->get('contacts/hosting/accounts', 'AiController::getRealCpanelAccounts');
$routes->options('contacts/hosting/accounts', 'AiController::getRealCpanelAccounts');
$routes->get('contacts/hosting/domains', 'AiController::getRealDomains');
$routes->options('contacts/hosting/domains', 'AiController::getRealDomains');
$routes->get('contacts/hosting/disk-usage', 'AiController::getRealDiskUsage');
$routes->options('contacts/hosting/disk-usage', 'AiController::getRealDiskUsage');
$routes->get('contacts/hosting/system-info', 'AiController::getRealSystemInfo');
$routes->options('contacts/hosting/system-info', 'AiController::getRealSystemInfo');
$routes->get('contacts/hosting/summary', 'AiController::getHostingSummary');
$routes->options('contacts/hosting/summary', 'AiController::getHostingSummary');
$routes->get('contacts/hosting/summary', 'AiController::getHostingSummary');
$routes->options('contacts/hosting/summary', 'AiController::getHostingSummary');

// Backup Management Routes
$routes->post('contacts/backup/create', 'AiController::createBackup');
$routes->get('contacts/backup/status', 'AiController::getBackupStatus');
$routes->post('contacts/backup/restore', 'AiController::restoreBackup');

// Security and SSL Routes
$routes->get('contacts/ssl/check', 'AiController::checkSSL');
$routes->post('contacts/ssl/install', 'AiController::installSSL');
$routes->post('contacts/ssl/renew', 'AiController::renewSSL');

// Advanced cPanel Functions
$routes->post('contacts/cpanel/create-account', 'AiController::createCpanelAccount');
$routes->post('contacts/cpanel/suspend-account', 'AiController::suspendAccount');
$routes->post('contacts/cpanel/unsuspend-account', 'AiController::unsuspendAccount');
$routes->post('contacts/cpanel/terminate-account', 'AiController::terminateAccount');

// File Management Routes
$routes->get('contacts/files/list', 'AiController::listFiles');
$routes->post('contacts/files/upload', 'AiController::uploadFile');
$routes->post('contacts/files/delete', 'AiController::deleteFile');

// Monitoring and Alerts Routes
$routes->get('contacts/monitoring/status', 'AiController::getMonitoringStatus');
$routes->options('contacts/monitoring/status', 'AiController::getMonitoringStatus');
$routes->post('contacts/alerts/create', 'AiController::createAlert');
$routes->get('contacts/alerts/list', 'AiController::listAlerts');



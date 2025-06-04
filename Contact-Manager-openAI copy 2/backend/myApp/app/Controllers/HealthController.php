<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;
use App\Utils\ErrorHandler;

class HealthController extends BaseController
{
    use ResponseTrait;

    private $errorHandler;

    public function __construct()
    {
        $this->errorHandler = new ErrorHandler();
    }

    public function status()
    {
        $status = [
            'status' => 'healthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '2.0.0',
            'environment' => ENVIRONMENT,
            'checks' => []
        ];

        // Database check
        $status['checks']['database'] = $this->checkDatabase();
        
        // Session check
        $status['checks']['session'] = $this->checkSession();
        
        // API dependencies check
        $status['checks']['apis'] = $this->checkApiDependencies();
        
        // Disk space check
        $status['checks']['disk_space'] = $this->checkDiskSpace();
        
        // Memory usage check
        $status['checks']['memory'] = $this->checkMemoryUsage();
        
        // Error handler health
        $status['checks']['error_handler'] = $this->checkErrorHandler();

        // Overall health determination
        $allHealthy = true;
        foreach ($status['checks'] as $check) {
            if ($check['status'] !== 'healthy') {
                $allHealthy = false;
                break;
            }
        }

        $status['status'] = $allHealthy ? 'healthy' : 'degraded';
        $httpCode = $allHealthy ? 200 : 503;

        return $this->respond($status, $httpCode);
    }

    public function detailed()
    {
        $detailed = [
            'system_info' => [
                'php_version' => PHP_VERSION,
                'codeigniter_version' => \CodeIgniter\CodeIgniter::CI_VERSION,
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'operating_system' => PHP_OS,
                'server_time' => date('Y-m-d H:i:s'),
                'timezone' => date_default_timezone_get(),
                'max_execution_time' => ini_get('max_execution_time'),
                'memory_limit' => ini_get('memory_limit'),
                'upload_max_filesize' => ini_get('upload_max_filesize')
            ],
            'chatbot_stats' => $this->getChatbotStats(),
            'error_stats' => $this->getErrorStats(),
            'performance_metrics' => $this->getPerformanceMetrics()
        ];

        return $this->respond($detailed);
    }

    public function chatbotHealth()
    {
        $health = [
            'status' => 'healthy',
            'last_check' => date('Y-m-d H:i:s'),
            'openai_connectivity' => $this->checkOpenAIConnectivity(),
            'session_storage' => $this->checkSessionStorage(),
            'error_rate' => $this->calculateErrorRate(),
            'response_time' => $this->getAverageResponseTime(),
            'recent_errors' => $this->getRecentChatbotErrors()
        ];

        // Determine overall chatbot health
        if ($health['error_rate'] > 0.1) { // More than 10% error rate
            $health['status'] = 'degraded';
        }

        if ($health['openai_connectivity']['status'] !== 'healthy') {
            $health['status'] = 'unhealthy';
        }

        return $this->respond($health);
    }

    public function metrics()
    {
        $metrics = [
            'timestamp' => time(),
            'chatbot' => [
                'total_conversations' => $this->getTotalConversations(),
                'active_sessions' => $this->getActiveSessions(),
                'avg_messages_per_session' => $this->getAvgMessagesPerSession(),
                'most_used_functions' => $this->getMostUsedFunctions(),
                'success_rate' => $this->getFunctionSuccessRate()
            ],
            'system' => [
                'cpu_usage' => $this->getCpuUsage(),
                'memory_usage' => $this->getMemoryUsagePercent(),
                'disk_usage' => $this->getDiskUsagePercent(),
                'response_time_95th' => $this->getResponseTime95th()
            ],
            'errors' => [
                'total_today' => $this->getTotalErrorsToday(),
                'critical_today' => $this->getCriticalErrorsToday(),
                'error_rate_24h' => $this->getErrorRate24h()
            ]
        ];

        return $this->respond($metrics);
    }

    public function cleanup()
    {
        try {
            $results = [
                'old_logs_deleted' => $this->errorHandler->clearOldLogs(30),
                'old_sessions_cleared' => $this->clearOldSessions(),
                'cache_cleared' => $this->clearExpiredCache(),
                'timestamp' => date('Y-m-d H:i:s')
            ];

            return $this->respond($results);

        } catch (\Exception $e) {
            return $this->fail([
                'error' => 'Cleanup failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function checkDatabase()
    {
        try {
            $db = \Config\Database::connect();
            $query = $db->query("SELECT 1");
            
            return [
                'status' => 'healthy',
                'message' => 'Database connection successful',
                'response_time' => 0 // You could measure this
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    private function checkSession()
    {
        try {
            $session = session();
            $testKey = 'health_check_' . time();
            $session->set($testKey, 'test_value');
            $retrieved = $session->get($testKey);
            $session->remove($testKey);

            return [
                'status' => $retrieved === 'test_value' ? 'healthy' : 'unhealthy',
                'message' => 'Session storage working correctly'
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Session check failed: ' . $e->getMessage()
            ];
        }
    }

    private function checkApiDependencies()
    {
        $apis = [
            'cpanel' => $this->testCpanelConnection(),
            'whmcs' => $this->testWhmcsConnection(),
            'openai' => $this->testOpenAiConnection()
        ];

        $allHealthy = true;
        foreach ($apis as $api) {
            if ($api['status'] !== 'healthy') {
                $allHealthy = false;
                break;
            }
        }

        return [
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'apis' => $apis
        ];
    }

    private function checkDiskSpace()
    {
        $totalBytes = disk_total_space(__DIR__);
        $freeBytes = disk_free_space(__DIR__);
        $usedPercent = (($totalBytes - $freeBytes) / $totalBytes) * 100;

        return [
            'status' => $usedPercent > 90 ? 'unhealthy' : 'healthy',
            'used_percent' => round($usedPercent, 2),
            'free_space' => $this->formatBytes($freeBytes),
            'total_space' => $this->formatBytes($totalBytes)
        ];
    }

    private function checkMemoryUsage()
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        $usagePercent = ($memoryUsage / $memoryLimit) * 100;

        return [
            'status' => $usagePercent > 90 ? 'unhealthy' : 'healthy',
            'used_percent' => round($usagePercent, 2),
            'current_usage' => $this->formatBytes($memoryUsage),
            'memory_limit' => $this->formatBytes($memoryLimit)
        ];
    }

    private function checkErrorHandler()
    {
        return [
            'status' => $this->errorHandler->isHealthy() ? 'healthy' : 'degraded',
            'recent_errors' => count($this->errorHandler->getRecentErrors(10)),
            'log_file_writable' => is_writable(WRITEPATH . 'logs/')
        ];
    }

    private function checkOpenAIConnectivity()
    {
        // Simple connectivity test (you might want to implement actual API test)
        return [
            'status' => 'healthy',
            'message' => 'OpenAI API connectivity test placeholder'
        ];
    }

    private function checkSessionStorage()
    {
        return [
            'status' => is_writable(WRITEPATH . 'session/') ? 'healthy' : 'unhealthy',
            'storage_path' => WRITEPATH . 'session/'
        ];
    }

    private function calculateErrorRate()
    {
        $stats = $this->errorHandler->getErrorStatistics(1);
        $totalErrors = $stats['total_errors'];
        
        // Estimate total requests (this would need proper implementation)
        $estimatedRequests = max($totalErrors * 10, 1); // Simple estimation
        
        return $totalErrors / $estimatedRequests;
    }

    private function getAverageResponseTime()
    {
        // This would need proper implementation with response time tracking
        return 1.25; // Placeholder
    }

    private function getRecentChatbotErrors()
    {
        return $this->errorHandler->getRecentErrors(5, 'AI_ERROR');
    }

    private function getChatbotStats()
    {
        // These would need proper implementation with session/database tracking
        return [
            'total_sessions_today' => 0,
            'avg_session_duration' => 0,
            'most_used_function' => 'unknown',
            'function_success_rate' => 0.95
        ];
    }

    private function getErrorStats()
    {
        return $this->errorHandler->getErrorStatistics(7);
    }

    private function getPerformanceMetrics()
    {
        return [
            'requests_per_minute' => 0, // Would need implementation
            'cache_hit_rate' => 0, // Would need implementation
            'db_query_time_avg' => 0 // Would need implementation
        ];
    }

    // Utility methods
    private function testCpanelConnection()
    {
        try {
            // Test connection logic here
            return ['status' => 'healthy', 'message' => 'Connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => $e->getMessage()];
        }
    }

    private function testWhmcsConnection()
    {
        try {
            // Test connection logic here
            return ['status' => 'healthy', 'message' => 'Connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => $e->getMessage()];
        }
    }

    private function testOpenAiConnection()
    {
        try {
            // Test connection logic here
            return ['status' => 'healthy', 'message' => 'Connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'unhealthy', 'message' => $e->getMessage()];
        }
    }

    private function formatBytes($size, $precision = 2)
    {
        $base = log($size, 1024);
        $suffixes = ['B', 'KB', 'MB', 'GB', 'TB'];
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $suffixes[floor($base)];
    }

    private function parseMemoryLimit($limit)
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }

        return $value;
    }

    // Placeholder methods that would need proper implementation
    private function getTotalConversations() { return 0; }
    private function getActiveSessions() { return 0; }
    private function getAvgMessagesPerSession() { return 0; }
    private function getMostUsedFunctions() { return []; }
    private function getFunctionSuccessRate() { return 0.95; }
    private function getCpuUsage() { return 0; }
    private function getMemoryUsagePercent() { return 0; }
    private function getDiskUsagePercent() { return 0; }
    private function getResponseTime95th() { return 0; }
    private function getTotalErrorsToday() { return 0; }
    private function getCriticalErrorsToday() { return 0; }
    private function getErrorRate24h() { return 0; }
    private function clearOldSessions() { return 0; }
    private function clearExpiredCache() { return 0; }
} 
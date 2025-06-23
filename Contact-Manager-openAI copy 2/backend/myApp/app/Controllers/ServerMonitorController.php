<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class ServerMonitorController extends ResourceController
{
    use ResponseTrait;
    
    private $cpanelApi;
    
    public function __construct()
    {
        // Initialize cPanel API with same credentials as AiController
        require_once APPPATH . 'Controllers/cpanelController.php';
        
        // Use your actual cPanel server credentials
        $this->cpanelApi = new \Cpanel\APILib(
            'janus13.easyonnet.io',                // host
            'root',                                // user  
            'GGWKWFGGCBIGJZ9RJV8Z7H47TM8YA1EG',   // token (same as AiController)
            2087                                   // port
        );
    }
    
    /**
     * Get cPanel server information  
     * GET /server/info
     */
    public function getServerInfo()
    {
        try {
            // Get server load average using WHM API (should be accessible)
            $serverInfo = $this->cpanelApi->whm_api_call('systemloadavg');
            
            if (!$serverInfo || isset($serverInfo['errors'])) {
                return $this->respond([
                    'status' => 'error',
                    'message' => 'Unable to retrieve cPanel server information',
                    'error_details' => $serverInfo['errors'] ?? 'API call failed',
                    'host_attempted' => 'janus13.easyonnet.io:2087'
                ]);
            }
            
            return $this->respond([
                'status' => 'success',
                'data' => $serverInfo,
                'timestamp' => date('Y-m-d H:i:s'),
                'source' => 'cPanel WHM API - systemloadavg',
                'server' => 'janus13.easyonnet.io'
            ]);
            
        } catch (\Exception $e) {
            return $this->fail('Error retrieving cPanel server info: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Get comprehensive cPanel server status
     * GET /server/status  
     */
    public function getServerStatus()
    {
        try {
            $serverData = [];
            
            // 1. System Load Average (WHM API)
            $loadAvg = $this->cpanelApi->whm_api_call('systemloadavg');
            $serverData['load_average'] = $loadAvg;
            
            // 2. Server Version Info (WHM API)
            $serverInfo = $this->cpanelApi->whm_api_call('version');
            $serverData['server_version'] = $serverInfo;
            
            // 3. Account Statistics (WHM API) 
            $accounts = $this->cpanelApi->whm_api_call('listaccts');
            $serverData['accounts'] = $accounts;
            
            // 4. Server Statistics (WHM API)
            $stats = $this->cpanelApi->whm_api_call('showbw');
            $serverData['bandwidth_stats'] = $stats;
            
            return $this->respond([
                'status' => 'success',
                'data' => $serverData,
                'timestamp' => date('Y-m-d H:i:s'),
                'source' => 'cPanel WHM API - Server Level',
                'server' => 'janus13.easyonnet.io',
                'api_type' => 'WHM (Server Level)'
            ]);
            
        } catch (\Exception $e) {
            return $this->fail('Error retrieving cPanel server status: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Analyze server health and provide AI-powered recommendations
     */
    private function analyzeServerHealth($serverData)
    {
        $analysis = [
            'overall_status' => 'healthy',
            'warnings' => [],
            'recommendations' => [],
            'metrics' => []
        ];
        
        // Analyze load average
        if (isset($serverData['load_average']['data'])) {
            $load = $serverData['load_average']['data'];
            $oneMin = floatval($load['one'] ?? 0);
            $fiveMin = floatval($load['five'] ?? 0);
            $fifteenMin = floatval($load['fifteen'] ?? 0);
            
            $analysis['metrics']['load_average'] = [
                '1min' => $oneMin,
                '5min' => $fiveMin, 
                '15min' => $fifteenMin
            ];
            
            // Load average analysis (assuming 4 core server)
            if ($oneMin > 3.0) {
                $analysis['warnings'][] = 'High CPU load detected (1min: ' . $oneMin . ')';
                $analysis['recommendations'][] = 'Consider reducing server load or investigating high CPU processes';
                $analysis['overall_status'] = 'warning';
            }
            
            if ($fiveMin > 2.5) {
                $analysis['warnings'][] = 'Sustained high load (5min: ' . $fiveMin . ')';
                $analysis['recommendations'][] = 'Monitor server processes and consider optimization';
            }
        }
        
        // Analyze account count
        if (isset($serverData['accounts_count'])) {
            $accountCount = $serverData['accounts_count'];
            $analysis['metrics']['hosted_accounts'] = $accountCount;
            
            if ($accountCount > 100) {
                $analysis['warnings'][] = 'High number of hosted accounts (' . $accountCount . ')';
                $analysis['recommendations'][] = 'Monitor resource usage per account';
            }
        }
        
        // General recommendations
        if (empty($analysis['warnings'])) {
            $analysis['recommendations'][] = 'Server appears healthy - continue regular monitoring';
        }
        
        return $analysis;
    }

    /**
     * Get specific disk usage information
     * GET /server/disk
     */
    public function getDiskUsage()
    {
        try {
            // Get account list which contains disk usage data
            $accounts = $this->cpanelApi->whm_api_call('listaccts');
            
            $diskSummary = [
                'total_accounts' => 0,
                'disk_usage_by_account' => [],
                'summary' => [
                    'total_disk_used_mb' => 0,
                    'accounts_with_limits' => 0,
                    'accounts_unlimited' => 0,
                    'heaviest_user' => null
                ],
                'partition_info' => []
            ];
            
            if (isset($accounts['data']['acct'])) {
                $diskSummary['total_accounts'] = count($accounts['data']['acct']);
                $maxUsage = 0;
                $heaviestUser = null;
                
                foreach ($accounts['data']['acct'] as $account) {
                    $diskUsed = $account['diskused'] ?? '0M';
                    $diskLimit = $account['disklimit'] ?? 'unlimited';
                    $partition = $account['partition'] ?? 'unknown';
                    $inodesUsed = $account['inodesused'] ?? 0;
                    
                    // Convert disk usage to MB (remove M suffix)
                    $diskUsedMB = (float)str_replace('M', '', $diskUsed);
                    $diskSummary['summary']['total_disk_used_mb'] += $diskUsedMB;
                    
                    // Track heaviest user
                    if ($diskUsedMB > $maxUsage) {
                        $maxUsage = $diskUsedMB;
                        $heaviestUser = [
                            'user' => $account['user'],
                            'domain' => $account['domain'],
                            'disk_used' => $diskUsed,
                            'disk_limit' => $diskLimit,
                            'inodes_used' => $inodesUsed
                        ];
                    }
                    
                    // Count limit types
                    if ($diskLimit === 'unlimited') {
                        $diskSummary['summary']['accounts_unlimited']++;
                    } else {
                        $diskSummary['summary']['accounts_with_limits']++;
                    }
                    
                    // Add to account list
                    $diskSummary['disk_usage_by_account'][] = [
                        'user' => $account['user'],
                        'domain' => $account['domain'],
                        'disk_used' => $diskUsed,
                        'disk_limit' => $diskLimit,
                        'partition' => $partition,
                        'inodes_used' => $inodesUsed
                    ];
                    
                    // Collect partition info
                    if (!in_array($partition, $diskSummary['partition_info'])) {
                        $diskSummary['partition_info'][] = $partition;
                    }
                }
                
                $diskSummary['summary']['heaviest_user'] = $heaviestUser;
            }
            
            return $this->respond([
                'status' => 'success',
                'data' => $diskSummary,
                'timestamp' => date('Y-m-d H:i:s'),
                'source' => 'cPanel WHM API - Account disk usage analysis',
                'server' => 'janus13.easyonnet.io',
                'note' => 'Disk usage extracted from account data (DiskUsage module not available)',
                'health_analysis' => $this->analyzeDiskHealth($diskSummary)
            ]);
            
        } catch (\Exception $e) {
            return $this->fail('Error retrieving disk usage: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Analyze disk health from account data
     */
    private function analyzeDiskHealth($diskSummary)
    {
        $analysis = [
            'overall_status' => 'healthy',
            'warnings' => [],
            'recommendations' => []
        ];
        
        $totalDiskMB = $diskSummary['summary']['total_disk_used_mb'];
        $totalAccounts = $diskSummary['total_accounts'];
        
        // Convert to GB for better readability
        $totalDiskGB = round($totalDiskMB / 1024, 2);
        
        if ($totalDiskGB > 500) {
            $analysis['warnings'][] = "High total disk usage: {$totalDiskGB}GB across {$totalAccounts} accounts";
            $analysis['recommendations'][] = 'Monitor individual account usage and consider cleanup';
            $analysis['overall_status'] = 'warning';
        }
        
        if (isset($diskSummary['summary']['heaviest_user'])) {
            $heaviest = $diskSummary['summary']['heaviest_user'];
            $heaviestGB = round(str_replace('M', '', $heaviest['disk_used']) / 1024, 2);
            
            if ($heaviestGB > 100) {
                $analysis['warnings'][] = "Large account detected: {$heaviest['domain']} using {$heaviestGB}GB";
                $analysis['recommendations'][] = "Review {$heaviest['domain']} for optimization opportunities";
            }
        }
        
        if (empty($analysis['warnings'])) {
            $analysis['recommendations'][] = 'Disk usage appears normal across all accounts';
        }
        
        $analysis['summary'] = [
            'total_usage_gb' => $totalDiskGB,
            'total_accounts' => $totalAccounts,
            'average_per_account_mb' => $totalAccounts > 0 ? round($totalDiskMB / $totalAccounts, 2) : 0
        ];
        
        return $analysis;
    }

    /**
     * Get server service and device status information
     * GET /server/services
     */
    public function getServerServices()
    {
        try {
            $results = [];
            
            // Try ServerInformation/get_information with specific users
            $users_to_try = ['root', 'dev477'];
            
            foreach ($users_to_try as $user) {
                try {
                    $serverInfo = $this->cpanelApi->cpanel_api_call($user, 'ServerInformation', 'get_information');
                    if ($serverInfo && !isset($serverInfo['error'])) {
                        $results["server_info_for_$user"] = $serverInfo;
                        break; // If successful, no need to try other users
                    }
                } catch (\Exception $e) {
                    $results["error_for_$user"] = $e->getMessage();
                }
            }
            
            // Also get the working WHM service status calls as backup
            $apache = $this->cpanelApi->whm_api_call('servicestatus', ['service' => 'httpd']);
            $results['apache_status'] = $apache;
            
            $mysql = $this->cpanelApi->whm_api_call('servicestatus', ['service' => 'mysql']);
            $results['mysql_status'] = $mysql;
            
            $ftp = $this->cpanelApi->whm_api_call('servicestatus', ['service' => 'ftpd']);
            $results['ftp_status'] = $ftp;
            
            $mail = $this->cpanelApi->whm_api_call('servicestatus', ['service' => 'exim']);
            $results['mail_status'] = $mail;
            
            $dns = $this->cpanelApi->whm_api_call('servicestatus', ['service' => 'named']);
            $results['dns_status'] = $dns;
            
            return $this->respond([
                'status' => 'success',
                'data' => $results,
                'timestamp' => date('Y-m-d H:i:s'),
                'source' => 'cPanel API - ServerInformation + WHM Service calls',
                'server' => 'janus13.easyonnet.io',
                'note' => 'Trying ServerInformation with specific users + service status',
                'users_attempted' => $users_to_try,
                'services_checked' => ['httpd', 'mysql', 'ftpd', 'exim', 'named']
            ]);
            
        } catch (\Exception $e) {
            return $this->fail('Error retrieving server services: ' . $e->getMessage(), 500);
        }
    }
} 
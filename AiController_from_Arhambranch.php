<?php

namespace App\Utils;

class ErrorHandler
{
    private $logFile;
    private $maxLogSize = 10485760; // 10MB
    
    public function __construct()
    {
        $this->logFile = WRITEPATH . 'logs/ai_chatbot_' . date('Y-m-d') . '.log';
        $this->ensureLogDirectory();
    }

    public function logError($errorType, $message, $trace = null, $context = [])
    {
        $timestamp = date('Y-m-d H:i:s');
        $sessionId = session_id() ?? 'no-session';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'session_id' => $sessionId,
            'error_type' => $errorType,
            'message' => $message,
            'trace' => $trace,
            'context' => $context,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];

        $logLine = $timestamp . " [{$errorType}] [{$sessionId}] {$message}";
        if ($trace) {
            $logLine .= " | Trace: " . json_encode($trace);
        }
        if (!empty($context)) {
            $logLine .= " | Context: " . json_encode($context);
        }
        $logLine .= PHP_EOL;

        // Write to file
        $this->writeToFile($logLine);
        
        // Also log to system error log for critical errors
        if (in_array($errorType, ['CRITICAL', 'API_FAILURE', 'SECURITY_VIOLATION'])) {
            error_log("AI Chatbot Critical Error: {$message}");
        }

        return $logEntry;
    }

    public function logInteraction($type, $userInput, $response, $sessionId, $executionTime = null)
    {
        $timestamp = date('Y-m-d H:i:s');
        
        $logEntry = [
            'timestamp' => $timestamp,
            'session_id' => $sessionId,
            'type' => $type,
            'user_input' => $this->sanitizeForLog($userInput),
            'response_type' => isset($response['status']) ? $response['status'] : 'unknown',
            'execution_time' => $executionTime,
            'token_usage' => $response['tokens_used'] ?? null
        ];

        $logLine = $timestamp . " [INTERACTION] [{$sessionId}] {$type} - " . 
                   substr($this->sanitizeForLog($userInput), 0, 100) . 
                   ($executionTime ? " ({$executionTime})" : "") . PHP_EOL;

        $this->writeToFile($logLine);
        return $logEntry;
    }

    public function handleApiError($apiName, $errorResponse, $requestData = null)
    {
        $errorType = "API_ERROR_{$apiName}";
        $message = "API call to {$apiName} failed";
        
        $context = [
            'api_response' => $errorResponse,
            'request_data' => $requestData ? $this->sanitizeForLog($requestData) : null
        ];

        if (is_array($errorResponse) && isset($errorResponse['error'])) {
            $message .= ": " . $errorResponse['error'];
        } elseif (is_string($errorResponse)) {
            $message .= ": " . $errorResponse;
        }

        return $this->logError($errorType, $message, null, $context);
    }

    public function handleValidationError($validationErrors, $inputData = null)
    {
        $message = "Validation failed: " . implode(', ', $validationErrors);
        $context = [
            'validation_errors' => $validationErrors,
            'input_data' => $inputData ? $this->sanitizeForLog($inputData) : null
        ];

        return $this->logError('VALIDATION_ERROR', $message, null, $context);
    }

    public function handleSecurityViolation($violationType, $details, $userInput = null)
    {
        $message = "Security violation detected: {$violationType}";
        $context = [
            'violation_type' => $violationType,
            'details' => $details,
            'user_input' => $userInput ? $this->sanitizeForLog($userInput) : null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];

        // Log security violations as critical
        return $this->logError('SECURITY_VIOLATION', $message, null, $context);
    }

    public function getRecentErrors($limit = 50, $errorType = null)
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $lines = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) {
            return [];
        }

        $errors = [];
        $lines = array_reverse($lines); // Most recent first

        foreach ($lines as $line) {
            if (count($errors) >= $limit) {
                break;
            }

            if ($errorType && strpos($line, "[$errorType]") === false) {
                continue;
            }

            $errors[] = $this->parseLongLine($line);
        }

        return $errors;
    }

    public function getErrorStatistics($days = 7)
    {
        $stats = [
            'total_errors' => 0,
            'errors_by_type' => [],
            'errors_by_day' => [],
            'critical_errors' => 0
        ];

        $startDate = date('Y-m-d', strtotime("-{$days} days"));
        
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $logFile = WRITEPATH . 'logs/ai_chatbot_' . $date . '.log';
            
            if (file_exists($logFile)) {
                $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines) {
                    foreach ($lines as $line) {
                        if (preg_match('/\[(.*?)\]/', $line, $matches)) {
                            $errorType = $matches[1];
                            $stats['total_errors']++;
                            $stats['errors_by_type'][$errorType] = 
                                ($stats['errors_by_type'][$errorType] ?? 0) + 1;
                            
                            if (in_array($errorType, ['CRITICAL', 'SECURITY_VIOLATION', 'API_FAILURE'])) {
                                $stats['critical_errors']++;
                            }
                        }
                        
                        $stats['errors_by_day'][$date] = 
                            ($stats['errors_by_day'][$date] ?? 0) + 1;
                    }
                }
            }
        }

        return $stats;
    }

    public function clearOldLogs($daysToKeep = 30)
    {
        $logDir = WRITEPATH . 'logs/';
        $cutoffDate = date('Y-m-d', strtotime("-{$daysToKeep} days"));
        
        $files = glob($logDir . 'ai_chatbot_*.log');
        $deletedCount = 0;
        
        foreach ($files as $file) {
            if (preg_match('/ai_chatbot_(\d{4}-\d{2}-\d{2})\.log$/', $file, $matches)) {
                $fileDate = $matches[1];
                if ($fileDate < $cutoffDate) {
                    unlink($file);
                    $deletedCount++;
                }
            }
        }

        return $deletedCount;
    }

    public function exportErrorReport($startDate, $endDate, $format = 'json')
    {
        $errors = [];
        $current = strtotime($startDate);
        $end = strtotime($endDate);

        while ($current <= $end) {
            $date = date('Y-m-d', $current);
            $logFile = WRITEPATH . 'logs/ai_chatbot_' . $date . '.log';
            
            if (file_exists($logFile)) {
                $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines) {
                    foreach ($lines as $line) {
                        $errors[] = $this->parseLongLine($line);
                    }
                }
            }
            
            $current = strtotime('+1 day', $current);
        }

        switch ($format) {
            case 'csv':
                return $this->exportToCsv($errors);
            case 'json':
            default:
                return json_encode($errors, JSON_PRETTY_PRINT);
        }
    }

    private function writeToFile($logLine)
    {
        // Check if log file is too large and rotate if necessary
        if (file_exists($this->logFile) && filesize($this->logFile) > $this->maxLogSize) {
            $this->rotateLogFile();
        }

        file_put_contents($this->logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    private function rotateLogFile()
    {
        $rotatedFile = $this->logFile . '.old';
        if (file_exists($rotatedFile)) {
            unlink($rotatedFile);
        }
        rename($this->logFile, $rotatedFile);
    }

    private function ensureLogDirectory()
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    private function sanitizeForLog($data)
    {
        if (is_array($data)) {
            $sanitized = $data;
            // Remove sensitive information
            $sensitiveKeys = ['password', 'token', 'api_key', 'secret'];
            foreach ($sensitiveKeys as $key) {
                if (isset($sanitized[$key])) {
                    $sanitized[$key] = '[REDACTED]';
                }
            }
            return $sanitized;
        }
        
        if (is_string($data)) {
            // Limit string length and remove potential sensitive data
            $data = substr($data, 0, 1000);
            $data = preg_replace('/password[^a-zA-Z0-9]*[^\s]+/i', 'password=[REDACTED]', $data);
            $data = preg_replace('/token[^a-zA-Z0-9]*[^\s]+/i', 'token=[REDACTED]', $data);
            return $data;
        }
        
        return $data;
    }

    private function parseLongLine($line)
    {
        $parts = explode(' ', $line, 4);
        if (count($parts) >= 4) {
            return [
                'timestamp' => $parts[0] . ' ' . $parts[1],
                'type' => trim($parts[2], '[]'),
                'session' => trim($parts[3], '[]'),
                'message' => $parts[4] ?? ''
            ];
        }
        return ['raw' => $line];
    }

    private function exportToCsv($errors)
    {
        if (empty($errors)) {
            return "No errors found\n";
        }

        $csv = "Timestamp,Type,Session,Message\n";
        foreach ($errors as $error) {
            $csv .= '"' . ($error['timestamp'] ?? '') . '","' . 
                    ($error['type'] ?? '') . '","' . 
                    ($error['session'] ?? '') . '","' . 
                    str_replace('"', '""', $error['message'] ?? '') . '"' . "\n";
        }
        
        return $csv;
    }

    public function isHealthy()
    {
        $recentErrors = $this->getRecentErrors(10);
        $criticalErrors = array_filter($recentErrors, function($error) {
            return in_array($error['type'] ?? '', ['CRITICAL', 'SECURITY_VIOLATION', 'API_FAILURE']);
        });

        return count($criticalErrors) < 5; // Healthy if less than 5 critical errors in recent logs
    }
} 
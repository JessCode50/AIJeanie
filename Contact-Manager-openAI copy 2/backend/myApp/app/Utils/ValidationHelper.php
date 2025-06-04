<?php

namespace App\Utils;

class ValidationHelper
{
    private $validationRules;
    private $errorMessages;

    public function __construct()
    {
        $this->validationRules = [
            'cpanel_user' => '/^[a-zA-Z0-9][a-zA-Z0-9_-]*[a-zA-Z0-9]$/',
            'email' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            'domain' => '/^[a-zA-Z0-9][a-zA-Z0-9.-]*[a-zA-Z0-9]\.[a-zA-Z]{2,}$/',
            'username' => '/^[a-zA-Z0-9][a-zA-Z0-9_-]{2,15}$/',
            'client_id' => '/^[0-9]+$/',
            'password' => '/^.{6,}$/' // Minimum 6 characters
        ];

        $this->errorMessages = [
            'cpanel_user' => 'Invalid cPanel username format',
            'email' => 'Invalid email address format',
            'domain' => 'Invalid domain name format',
            'username' => 'Username must be 3-16 characters, alphanumeric with underscores/hyphens',
            'client_id' => 'Client ID must be a valid number',
            'password' => 'Password must be at least 6 characters long'
        ];
    }

    public function validateChatInput($userMessage)
    {
        $errors = [];

        // Check for empty or whitespace-only message
        if (empty(trim($userMessage))) {
            $errors[] = 'Message cannot be empty';
            return ['valid' => false, 'errors' => $errors];
        }

        // Check message length
        if (strlen($userMessage) > 5000) {
            $errors[] = 'Message too long (maximum 5000 characters)';
        }

        // Check for potentially harmful content
        if ($this->containsSuspiciousContent($userMessage)) {
            $errors[] = 'Message contains potentially harmful content';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    public function validateProceedRequest($data)
    {
        $errors = [];

        if (!is_array($data) || empty($data)) {
            $errors[] = 'Invalid request data format';
            return ['valid' => false, 'errors' => $errors];
        }

        if (!isset($data[0]['functionName'])) {
            $errors[] = 'Function name is required';
        }

        if (!isset($data[0]['parameters'])) {
            $errors[] = 'Parameters are required';
        } else {
            // Validate parameters based on function
            $functionName = $data[0]['functionName'] ?? '';
            $parameters = $data[0]['parameters'];
            $paramValidation = $this->validateFunctionParameters($functionName, $parameters);
            
            if (!$paramValidation['valid']) {
                $errors = array_merge($errors, $paramValidation['errors']);
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    public function validateFunctionParameters($functionName, $parameters)
    {
        $errors = [];

        switch ($functionName) {
            case 'count_pops':
            case 'list_pops':
                if (!$this->validateParameter('cpanel_user', $parameters['cpanel_user'] ?? '')) {
                    $errors[] = $this->errorMessages['cpanel_user'];
                }
                break;

            case 'createacct':
                if (!$this->validateParameter('username', $parameters['username'] ?? '')) {
                    $errors[] = $this->errorMessages['username'];
                }
                if (!$this->validateParameter('domain', $parameters['domain'] ?? '')) {
                    $errors[] = $this->errorMessages['domain'];
                }
                if (!$this->validateParameter('email', $parameters['contact_email'] ?? '')) {
                    $errors[] = $this->errorMessages['email'];
                }
                break;

            case 'add_pop':
                if (!$this->validateParameter('cpanel_user', $parameters['cpanel_user'] ?? '')) {
                    $errors[] = $this->errorMessages['cpanel_user'];
                }
                if (!$this->validateParameter('password', $parameters['password'] ?? '')) {
                    $errors[] = $this->errorMessages['password'];
                }
                break;

            case 'delete_pop':
                if (!$this->validateParameter('cpanel_user', $parameters['cpanel_user'] ?? '')) {
                    $errors[] = $this->errorMessages['cpanel_user'];
                }
                if (!$this->validateParameter('email', $parameters['email'] ?? '')) {
                    $errors[] = $this->errorMessages['email'];
                }
                break;

            case 'client':
            case 'invoices':
                if (!$this->validateParameter('client_id', $parameters['client_id'] ?? '')) {
                    $errors[] = $this->errorMessages['client_id'];
                }
                break;

            case 'list_forwarders':
                if (!$this->validateParameter('cpanel_user', $parameters['cpanel_user'] ?? '')) {
                    $errors[] = $this->errorMessages['cpanel_user'];
                }
                if (!$this->validateParameter('domain', $parameters['domain'] ?? '')) {
                    $errors[] = $this->errorMessages['domain'];
                }
                break;

            case 'add_forwarder':
                if (!$this->validateParameter('cpanel_user', $parameters['cpanel_user'] ?? '')) {
                    $errors[] = $this->errorMessages['cpanel_user'];
                }
                if (!$this->validateParameter('email', $parameters['email'] ?? '')) {
                    $errors[] = 'Invalid source email format';
                }
                if (!$this->validateParameter('email', $parameters['forward_to_email'] ?? '')) {
                    $errors[] = 'Invalid destination email format';
                }
                break;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    private function validateParameter($type, $value)
    {
        if (!isset($this->validationRules[$type])) {
            return true; // No rule defined, assume valid
        }

        return preg_match($this->validationRules[$type], $value);
    }

    private function containsSuspiciousContent($message)
    {
        $suspiciousPatterns = [
            '/script[^>]*>/i',           // Script tags
            '/javascript:/i',            // JavaScript protocols
            '/vbscript:/i',             // VBScript protocols
            '/data:text\/html/i',       // Data URLs with HTML
            '/<iframe/i',               // Iframe tags
            '/eval\s*\(/i',             // eval() calls
            '/exec\s*\(/i',             // exec() calls
            '/system\s*\(/i',           // system() calls
            '/base64_decode\s*\(/i',    // base64_decode calls
            '/file_get_contents\s*\(/i' // file_get_contents calls
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }

    public function sanitizeInput($input)
    {
        if (is_string($input)) {
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
        return $input;
    }

    public function validateApiResponse($response, $functionName)
    {
        if (empty($response)) {
            return ['valid' => false, 'error' => "Empty response from {$functionName}"];
        }

        switch ($functionName) {
            case 'listaccts':
                if (!isset($response['data']['acct']) || !is_array($response['data']['acct'])) {
                    return ['valid' => false, 'error' => 'Invalid account list format'];
                }
                break;

            case 'client':
                if (!isset($response['result'])) {
                    return ['valid' => false, 'error' => 'Missing result field in client response'];
                }
                if ($response['result'] === 'error') {
                    // Don't treat WHMCS API errors as validation failures - let the calling code handle them
                    return ['valid' => true]; 
                }
                if ($response['result'] !== 'success') {
                    return ['valid' => false, 'error' => 'Unexpected client response format'];
                }
                break;

            case 'count_pops':
                if (!isset($response['result']['data'])) {
                    return ['valid' => false, 'error' => 'Invalid email count response'];
                }
                break;
        }

        return ['valid' => true];
    }
} 
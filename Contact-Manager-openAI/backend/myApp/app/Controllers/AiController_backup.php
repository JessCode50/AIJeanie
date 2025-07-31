<?php
namespace App\Controllers;

require_once __DIR__ . '/../APILib.php';
require_once __DIR__ . '/../WHMCS.php';

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;
use Cpanel\APILib;
use WHMCS;
use Exception;

class AiController extends BaseController
{
    use ResponseTrait;

    private $cpanel;
    private $whmcs;
    private $openai_key;

    public function __construct()
    {
        $this->openai_key = getenv('app.openai_key');
        
        // Initialize API connections with error handling
        try {
            $this->cpanel = new APILib('janus13.easyonnet.io', 'root','GGWKWFGGCBIGJZ9RJV8Z7H47TM8YA1EG', '2087');
            $this->whmcs = new WHMCS('https://portal.easyonnet.io/includes/api.php',
                'm2ftQEEegLHpyLPD6fiUpAqA0mx9T1XL',
                'ooxF2HcGp1VjifSAB6bcHO6WfunujurY');
        } catch (Exception $e) {
            log_message('error', 'API initialization failed: ' . $e->getMessage());
        }
    }

    /**
     * Set CORS headers for all requests
     */
    private function setCorsHeaders()
    {
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    }

    /**
     * Validate input data
     */
    private function validateInput($data, $requiredFields = [])
    {
        if (empty($data)) {
            return ['valid' => false, 'message' => 'No data provided'];
        }

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return ['valid' => false, 'message' => "Missing required field: {$field}"];
            }
        }

        return ['valid' => true];
    }

    /**
     * Enhanced error handling with logging
     */
    private function handleError($message, $exception = null, $statusCode = 500)
    {
        $errorData = [
            'error' => true,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        if ($exception) {
            log_message('error', $message . ': ' . $exception->getMessage());
            $errorData['details'] = $exception->getMessage();
        }

        return $this->respond($errorData, $statusCode);
    }

    /**
     * Update conversation history in session
     */
    private function updateChatHistory($role, $content, $name = null)
    {
        $session = session();
        $chatHistory = $session->get('history') ?? [];
        
        $message = [
            'role' => $role,
            'content' => $content,
            'timestamp' => time()
        ];

        if ($name) {
            $message['name'] = $name;
        }

        $chatHistory[] = $message;
        $session->set('history', $chatHistory);
        
        return $chatHistory;
    }

    /**
     * Log function calls for tracking
     */
    private function logFunctionCall($functionName, $parameters, $sessionId = null)
    {
        $logData = [
            'function' => $functionName,
            'parameters' => $parameters,
            'session_id' => $sessionId ?? session_id(),
            'timestamp' => time(),
            'user_agent' => $this->request->getUserAgent()->getAgentString()
        ];

        log_message('info', 'Function called: ' . json_encode($logData));
    }

    public function index()
    {
        $this->setCorsHeaders();
        return $this->respond([
            'status' => 'AI Controller Active',
            'version' => '2.1.0',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    public function proceed()
    {
        $this->setCorsHeaders();
        
        try {
            $data = $this->request->getJSON(true);
            
            // Validate input
            $validation = $this->validateInput($data);
            if (!$validation['valid']) {
                return $this->handleError($validation['message'], null, 400);
            }

            if (!isset($data[0]['functionName'])) {
                return $this->handleError('Function name not provided', null, 400);
            }

            $functionName = $data[0]['functionName'];
            $parameters = $data[0]['parameters'] ?? [];
            $session = session();

            // Log the function call
            $this->logFunctionCall($functionName, $parameters, $session->session_id);

            // Execute the function
            $result = $this->executeFunction($functionName, $parameters);

            if ($result['success']) {
                return $this->respond([
                    'success' => true,
                    'data' => $result['data'],
                    'function' => $functionName,
                    'timestamp' => time()
                ]);
            } else {
                return $this->handleError($result['message'], null, 500);
            }

        } catch (Exception $e) {
            return $this->handleError('Failed to process request', $e, 500);
        }
    }

    /**
     * Execute function based on name with proper error handling
     */
    private function executeFunction($functionName, $parameters)
    {
        try {
            switch ($functionName) {
                case 'get_domain_info':
                    return $this->handleGetDomainInfo();
                
                case 'listaccts':
                    return $this->handleListAccounts();
                
                case 'listpkgs':
                    return $this->handleListPackages();
                
                case 'count_pops':
                    return $this->handleCountPops($parameters);
                
                case 'createacct':
                    return $this->handleCreateAccount($parameters);
                
                case 'list_pops':
                    return $this->handleListPops($parameters);
                
                case 'add_pop':
                    return $this->handleAddPop($parameters);
                
                case 'delete_pop':
                    return $this->handleDeletePop($parameters);
                
                case 'getClientDetails':
                    return $this->handleGetClientDetails($parameters);
                
                case 'getClientsProducts':
                    return $this->handleGetClientsProducts($parameters);
                
                case 'systemloadavg':
                    return $this->handleSystemLoadAvg();
                
                case 'RECOMMENDED TICKET RESPONSE':
                    return $this->handleTicketResponse($parameters);
                
                default:
                    return ['success' => false, 'message' => "Unknown function: {$functionName}"];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => "Function execution failed: " . $e->getMessage()];
        }
    }

    /**
     * Individual function handlers with proper validation and formatting
     */
    private function handleGetDomainInfo()
    {
        try {
            $response = $this->cpanel->get_domain_info();
            $formatted = $this->formatGet_domain_info($response);
            $this->updateChatHistory('assistant', json_encode($formatted), 'get_domain_info_call_response');
            return ['success' => true, 'data' => $formatted];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get domain info: ' . $e->getMessage()];
        }
    }

    private function handleListAccounts()
    {
        try {
            $response = $this->cpanel->listaccts();
            $formatted = $this->formatListaccts($response);
            $this->updateChatHistory('assistant', json_encode($formatted), 'listaccts_call_response');
            return ['success' => true, 'data' => $formatted];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to list accounts: ' . $e->getMessage()];
        }
    }

    private function handleListPackages()
    {
        try {
            $response = $this->cpanel->listpkgs();
            $formatted = $this->formatListpkgs($response);
            $this->updateChatHistory('assistant', json_encode($formatted), 'listpkgs_call_response');
            return ['success' => true, 'data' => $formatted];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to list packages: ' . $e->getMessage()];
        }
    }

    private function handleCountPops($parameters)
    {
        $validation = $this->validateInput($parameters, ['cpanel_user']);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        try {
            $response = $this->cpanel->count_pops($parameters['cpanel_user']);
            return ['success' => true, 'data' => $response];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to count POPs: ' . $e->getMessage()];
        }
    }

    private function handleCreateAccount($parameters)
    {
        $validation = $this->validateInput($parameters, ['username', 'domain', 'contact_email']);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        try {
            $response = $this->cpanel->createacct(
                $parameters['username'],
                $parameters['domain'],
                $parameters['contact_email']
            );
            return ['success' => true, 'data' => $response];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to create account: ' . $e->getMessage()];
        }
    }

    private function handleListPops($parameters)
    {
        $validation = $this->validateInput($parameters, ['cpanel_user']);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        try {
            $response = $this->cpanel->list_pops($parameters['cpanel_user']);
            $formatted = $this->formatList_pops($response);
            $this->updateChatHistory('assistant', json_encode($formatted), 'list_pops_call_response');
            return ['success' => true, 'data' => $formatted];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to list POPs: ' . $e->getMessage()];
        }
    }

    private function handleAddPop($parameters)
    {
        $validation = $this->validateInput($parameters, ['cpanel_user', 'email_user', 'password']);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        try {
            $response = $this->cpanel->add_pop(
                $parameters['cpanel_user'],
                $parameters['email_user'],
                $parameters['password']
            );
            return ['success' => true, 'data' => $response];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to add POP: ' . $e->getMessage()];
        }
    }

    private function handleDeletePop($parameters)
    {
        $validation = $this->validateInput($parameters, ['cpanel_user', 'email']);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        try {
            $response = $this->cpanel->delete_pop($parameters['cpanel_user'], $parameters['email']);
            return ['success' => true, 'data' => $response];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to delete POP: ' . $e->getMessage()];
        }
    }

    private function handleGetClientDetails($parameters)
    {
        $validation = $this->validateInput($parameters, ['client_id']);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        try {
            $response = $this->whmcs->getClientDetails($parameters['client_id']);
            $formatted = $this->formatClientDetails($response);
            $this->updateChatHistory('assistant', json_encode($formatted), 'getClientDetails_call_response');
            return ['success' => true, 'data' => $formatted];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get client details: ' . $e->getMessage()];
        }
    }

    private function handleGetClientsProducts($parameters)
    {
        $validation = $this->validateInput($parameters, ['client_id']);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        try {
            $response = $this->whmcs->getClientsProducts($parameters['client_id']);
            $formatted = $this->formatGetClientsProducts($response);
            $this->updateChatHistory('assistant', json_encode($formatted), 'getClientProducts_call_response');
            return ['success' => true, 'data' => $formatted];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get client products: ' . $e->getMessage()];
        }
    }

    private function handleSystemLoadAvg()
    {
        try {
            $response = $this->cpanel->systemloadavg();
            $formatted = $this->formatSystemloadavg($response);
            $this->updateChatHistory('assistant', json_encode($formatted), 'systemloadavg_call_response');
            return ['success' => true, 'data' => $formatted];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to get system load: ' . $e->getMessage()];
        }
    }

    private function handleTicketResponse($parameters)
    {
        if (!isset($parameters['description'])) {
            return ['success' => false, 'message' => 'Ticket response description required'];
        }

        try {
            $this->updateChatHistory('user', 'The recommended ticket response was sent to the customer: ' . $parameters['description'], 'ticket_response_sent');
            return ['success' => true, 'data' => 'Ticket response logged successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to log ticket response: ' . $e->getMessage()];
        }
    }

    public function chat()
    {
        $this->setCorsHeaders();
        
        try {
            $session = session();
            $json = $this->request->getJSON(true);
            
            // Validate input
            if (empty($json['message'])) {
                return $this->handleError('Message content is required', null, 400);
            }

            $userMessage = $json['message'];
            $chatHistory = $session->get('history') ?? [];
            
            // Add user message to history
            $chatHistory[] = [
                "role" => "user",
                "content" => $userMessage,
                "timestamp" => time()
            ];

            // Prepare OpenAI request
            $openAIData = [
                "model" => "gpt-4",
                "messages" => $this->prepareMessagesForOpenAI($chatHistory),
                "tools" => $this->getAvailableTools(),
                "tool_choice" => "auto",
                "max_tokens" => 1000,
                "temperature" => 0.1
            ];

            // Make OpenAI API call
            $response = $this->callOpenAI($openAIData);
            
            if (!$response['success']) {
                return $this->handleError('Failed to get AI response', null, 500);
            }

            $aiResponse = $response['data'];
            
            // Add AI response to history
            $chatHistory[] = [
                "role" => "assistant",
                "content" => $aiResponse['choices'][0]['message']['content'] ?? '',
                "tool_calls" => $aiResponse['choices'][0]['message']['tool_calls'] ?? null,
                "timestamp" => time()
            ];

            $session->set('history', $chatHistory);

            return $this->respond([
                'success' => true,
                'message' => $aiResponse['choices'][0]['message']['content'] ?? '',
                'tool_calls' => $aiResponse['choices'][0]['message']['tool_calls'] ?? null,
                'usage' => $aiResponse['usage'] ?? null
            ]);

        } catch (Exception $e) {
            return $this->handleError('Chat processing failed', $e, 500);
        }
    }

    /**
     * Make OpenAI API call with proper error handling
     */
    private function callOpenAI($data)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.openai.com/v1/chat/completions");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Content-Type: application/json",
                "Authorization: Bearer " . $this->openai_key
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception("cURL error: " . $error);
            }

            if ($httpCode !== 200) {
                throw new Exception("OpenAI API returned HTTP " . $httpCode . ": " . $response);
            }

            $decodedResponse = json_decode($response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON response from OpenAI");
            }

            return ['success' => true, 'data' => $decodedResponse];

        } catch (Exception $e) {
            log_message('error', 'OpenAI API call failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Prepare messages for OpenAI API format
     */
    private function prepareMessagesForOpenAI($chatHistory)
    {
        $messages = [];
        
        // Add system message
        $messages[] = [
            "role" => "system",
            "content" => "You are an AI assistant for customer support helping with hosting and domain management. You have access to cPanel and WHMCS functions to help customers with their accounts."
        ];

        // Add conversation history
        foreach ($chatHistory as $message) {
            $openAIMessage = [
                "role" => $message['role'],
                "content" => $message['content']
            ];

            if (isset($message['tool_calls'])) {
                $openAIMessage['tool_calls'] = $message['tool_calls'];
            }

            if (isset($message['name'])) {
                $openAIMessage['name'] = $message['name'];
            }

            $messages[] = $openAIMessage;
        }

        return $messages;
    }

    /**
     * Get available tools for OpenAI
     */
    private function getAvailableTools()
    {
        return [
            [
                "type" => "function",
                "function" => [
                    "name" => "get_domain_info",
                    "description" => "Get domain information from the server",
                    "parameters" => ["type" => "object", "properties" => []]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "listaccts",
                    "description" => "List all hosting accounts",
                    "parameters" => ["type" => "object", "properties" => []]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "getClientDetails",
                    "description" => "Get client details from WHMCS",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "client_id" => ["type" => "string", "description" => "Client ID to lookup"]
                        ],
                        "required" => ["client_id"]
                    ]
                ]
            ]
            // Add more tools as needed
        ];
    }

    public function clear()
    {
        $this->setCorsHeaders();
        
        try {
            $session = session();
            $session->remove('history');
            
            return $this->respond([
                'success' => true,
                'message' => 'Chat history cleared successfully'
            ]);
        } catch (Exception $e) {
            return $this->handleError('Failed to clear history', $e, 500);
        }
    }

    public function rejected()
    {
        $this->setCorsHeaders();
        
        try {
            $data = $this->request->getJSON(true);
            // Handle rejection logic here
            
            return $this->respond([
                'success' => true,
                'message' => 'Function rejection handled'
            ]);
        } catch (Exception $e) {
            return $this->handleError('Failed to handle rejection', $e, 500);
        }
    }

    public function hosting_servers_list()
    {
        $json = <<<EOT
{"hosting_server": [{"hosting_server_id" : 44,"name" : "davinci","hostname" : "davinci.easyonnet.io","description" : "Primary Hosting server we are exporting to as of Aug 31\/2023","type" : "cpanel","api_key" : "yHlx5PhISXamZxxvkAPGBaRoPVGMAtd62puAZeMn9GPADCmI7y91BMZOb53vcPtO0RdwofwGXcMJG\/3UILCE\/szv6ZY\/dYjt6QrfPVWHROc4FE1hqMK\/wSzgQW8G7xbOjZfB6vpLqyk5q4ZhMlenwg==","api_user" : "root","api_pass" : null,"created" : "2023-08-31T15:26:49.000Z","updated" : "2023-09-01T02:48:22.000Z"},{"hosting_server_id" : 45,"name" : "tesla1","hostname" : "tesla1.nerivon.ca","description" : "our oldest of the new shared hosting servers","type" : "cpanel","api_key" : "sDwXWjfb0LCqLcWiF601LMNKXKJFGidxtsTC\/r178BrKyHEgnHAcGtsRzLHH\/+F9kPagjGxIbOKr7H1WIUJa8ps6hx1lYqBCviFpzC28i\/S4JG0rbEu8pxv4HnrPSaJ38lkJnf4d1QN3wkrgQtPgiA==","api_user" : "root","api_pass" : null,"created" : "2023-08-31T15:28:02.000Z","updated" : "2023-09-01T02:48:28.000Z"},{"hosting_server_id" : 46,"name" : "edison","hostname" : "edison.easyonnet.io","description" : "Added Oct 10\/23","type" : "cpanel","api_key" : "feum\/kc52jL2bRmXZ7orS9TC+s5+T1l9kDdKbsdu3ZrPLgk3EcEYyTMyavSn2yvxxCp8CVHfY6cPS9iZW3H6EQDvYqFoedoFAS7gI9EQCrap9JhFWA6A7KmWKRgmN0JIdKDzCloC5u5qwfJ5JirMcQ==","api_user" : "root","api_pass" : null,"created" : "2023-10-10T15:32:17.000Z","updated" : "2023-10-10T15:32:29.000Z"},{"hosting_server_id" : 47,"name" : "columbus1","hostname" : "columbus1.easyonnet.io","description" : "Optimized for Drupal Site Hosting","type" : "cpanel","api_key" : "xLp1JTeZraLpaj2aS5hv7JW11TlqfKmEBxsmlUBggu1gWqHCMBN6rWHXRTzWbA28Z3T7wVSjl1N5j5KWc87B3U\/pNGhnjQhww3NmiqrDFq6Unapqvzwosu0ffhluL2CGjRm3znx6R1HSREaWkzJuqw==","api_user" : "root","api_pass" : null,"created" : "2023-12-21T20:04:56.000Z","updated" : "2023-12-21T20:05:06.000Z"},{"hosting_server_id" : 48,"name" : "janus4","hostname" : "janus4.easyonnet.io","description" : "Added on Jan 31, 2024","type" : "cpanel","api_key" : "mKvE3hddOoWa0dCEwTSltM5\/+\/V4jnDzSkN6D9zSn4gRbCbrcnAqsUhw3j5xVp21EnM1FULpfSwfTUUzXGi2Apm5RmBDabMkoH2QUnj0Zp4hJhUzg3iaNmJfH2\/BD2bl5EaGeGvDrO7viG7FB0reEQ==","api_user" : "root","api_pass" : null,"created" : "2024-04-02T13:22:28.000Z","updated" : "2024-04-02T13:22:28.000Z"},{"hosting_server_id" : 49,"name" : "janus2","hostname" : "janus2.easyonnet.io","description" : "Added February 16, 2024","type" : "cpanel","api_key" : "EIM9KwBRJGOXD5dMCxuxQNUytDsf0z67375AjbvKOBOTr6Lx2KJdT2akIGt8cVXrf390b1yKDSDVOXKecW5RVtlim+f\/UuGwIVRC3UanSos1ZdCkcH5SeEitmQu2lJiSufPUoAWiE96VrLRu2OYS+A==","api_user" : "root","api_pass" : null,"created" : "2024-02-17T04:03:15.000Z","updated" : "2024-02-17T04:04:25.000Z"},{"hosting_server_id" : 50,"name" : "janus1","hostname" : "janus1.easyonnet.io","description" : "added feb 16, 2024","type" : "cpanel","api_key" : "UsjuJLeRd\/zFMwJJ99qdq5k\/hxGW3iMUZYv9mvGI0huAeaTiDWtmvFXVn3eF8bwzNeqaCoN9Wcz\/I+vC8TNqFnOJ2prjcE3mnVZa8W\/osLCIJM7DTwQ9QXkGPjG0kWb6TsFPNxuOqpgrpm505F1fWA==","api_user" : "root","api_pass" : null,"created" : "2024-02-17T04:06:36.000Z","updated" : "2024-02-17T04:06:36.000Z"},{"hosting_server_id" : 51,"name" : "morse","hostname" : "morse.easyonnet.io","description" : "added feb 17, 2024","type" : "cpanel","api_key" : "i+nDEfvLWHCoe9M+YtcPo2+n1G9lJm1XrFe5Il4AQAV\/AZs8bXlGsizn4bZx77tz8A20EhaPygu\/mxZYPGyLdtLaM4devQ9G1+2PVrffc0YzcfRBBYeH8yBb0BQZbGAfSgg1EJLSYkkhmLkjCLJCNg==","api_user" : "root","api_pass" : null,"created" : "2024-02-17T17:46:53.000Z","updated" : "2024-02-17T17:47:35.000Z"},{"hosting_server_id" : 52,"name" : "torplesk-04","hostname" : "torplesk-04.dynamichosting.biz","description" : "RETIRED","type" : "plesk","api_key" : "jNWPkTSMvQi0\/XMwmFk8i9HEZJkKbHarNNqI6R4FlEFyLVbLZqg6NufXHO4rpAhz47YLApnHWjPG7DfEwkbI0X60yEMU\/fmVcXsF2afMIAZtvFEEmDt3h99IOE6NJzmMCCizNkEVdl2je1MYmjqB7WGMr\/I=","api_user" : "root","api_pass" : null,"created" : "2024-02-19T20:56:12.000Z","updated" : "2024-03-29T21:19:15.000Z"},{"hosting_server_id" : 53,"name" : "torplesk-02","hostname" : "torplesk-02.dynamichosting.biz","description" : "good","type" : "plesk","api_key" : "zEasQtcBtgvKuSK6o+ZNxCGKv5+iuBhXezSlGm4hawufYhIxV2plDjedKQ32uuVPImPhF4zsJVO795CQc1ggt64xwJEYcTvut8dJjZ6JlMUeRcXZwepj4vghJKBBLdl0PlZpmgbdgSAst4WfMoDZKx76hxI=","api_user" : "root","api_pass" : null,"created" : "2024-02-19T20:59:43.000Z","updated" : "2024-03-29T21:21:50.000Z"},{"hosting_server_id" : 54,"name" : "donatello","hostname" : "donatello.easyonnet.io","description" : "good","type" : "plesk","api_key" : "jeF8xiiuc27wTI+QcYbKc68W6VTbB9vUimlRwFeIbjj8T+R40RkwHonMPJtg9kI8r3mVZ+OjEyj8Qnfk9uBQw7gAtPA8xLXIpBxXeeCwNb\/p6PN6joVwE1r0X2MChP6TVEC8VkkZvCZc+9\/CKXemDWFm\/7Y=","api_user" : "admin","api_pass" : null,"created" : "2024-02-19T21:01:59.000Z","updated" : "2024-03-29T21:21:56.000Z"},{"hosting_server_id" : 55,"name" : "bullshark (Plesk)","hostname" : "bullshark.nerivon.cloud","description" : "not working","type" : "plesk","api_key" : "4fLkJSJYnUqt6EZYtEC\/7NQrrkT3kdJkdb08vFe\/BECL0IOIxzsz7cztDAhhYgQFtuNChcZaDgwXISEIurkEBQxQsjLgl4HjMVC2uUJVG8YG84CMgWgWIWjIfi0Y0KNVGywPp\/iZ86DQd1X\/A8tfJSZYiX8=","api_user" : "root","api_pass" : null,"created" : "2024-02-19T21:04:36.000Z","updated" : "2024-03-29T21:22:01.000Z"},{"hosting_server_id" : 56,"name" : "raphael","hostname" : "raphael.easyonnet.io","description" : "good","type" : "plesk","api_key" : "xorNEtJzktTAQF8XugvxIz6QwumQG8x+4+Qpikz\/x5DiojyYsIhf1rvjRP4UOQL8kkNJuAOET5CAeZMfaDEr8bUyesiRBWpmqwniGq37zQ6uIdcmuwWXlps8PizCjlSp1GeqSh0hLWvEue+HlLweFkNlnuw=","api_user" : "admin","api_pass" : null,"created" : "2024-02-19T21:05:32.000Z","updated" : "2024-03-29T21:23:07.000Z"},{"hosting_server_id" : 57,"name" : "tesla04 (plesk)","hostname" : "tesla04.nerivon.cloud","description" : "not working","type" : "plesk","api_key" : null,"api_user" : "admin","api_pass" : null,"created" : "2024-02-19T21:06:30.000Z","updated" : "2024-03-29T21:23:57.000Z"},{"hosting_server_id" : 58,"name" : "torplesk-01","hostname" : "torplesk-01.dynamichosting.biz","description" : "good","type" : "plesk","api_key" : "hwdCn7mV8qeFF14jJZWoV99G+Gdt2Bv8qkS7q4aaD2oSUpKh7pKyFxWOkhNEGcMCLTH4iaGeIh\/4jF9QhAgxc5av\/SYC7k3UJnvq7JthcRSL7lTJDkAN+2\/0bRKHa9gRYUUtoIFbP\/iY4zmYS4uMf4M6SGk=","api_user" : "root","api_pass" : null,"created" : "2024-02-19T21:08:28.000Z","updated" : "2024-03-29T21:24:53.000Z"},{"hosting_server_id" : 59,"name" : "torplesk-03","hostname" : "torplesk-03.dynamichosting.biz","description" : "not working","type" : "plesk","api_key" : null,"api_user" : "root","api_pass" : null,"created" : "2024-02-19T21:11:11.000Z","updated" : "2024-03-29T21:28:28.000Z"},{"hosting_server_id" : 60,"name" : "torplesk-05","hostname" : "torplesk-05.dynamichosting.biz","description" : "not working - appears to be dead","type" : "plesk","api_key" : "Q55eGYU\/KJQlEep144S0Iw0elC4BDse1M5GsQ735pqN1GUZU6pfM3q8wPmjKPWsOc\/RYK2l3fV17\/0b\/qPrq2jepd\/T34nFGCBwaUfYQCt45Lclq7b8Ibriuqlv1uqSKOxIb\/fnAo5A0ZSI114p+BocCQ\/8=","api_user" : "admin","api_pass" : null,"created" : "2024-02-19T21:12:16.000Z","updated" : "2024-03-29T21:28:37.000Z"},{"hosting_server_id" : 61,"name" : "torwinplesk","hostname" : "torwinplesk.dynamichosting.cloud","description" : "good","type" : "plesk","api_key" : "FwLPixbMixusnu567tBvR0V2bxZckGdetRydUcembqnVi1WfEaYuHaoNAoVgXOEkYTUqTkcWSTwCFQ+vMtgHVq4h1sjvwNG08ctTC\/2bPMKsdsUt+bpcD97ealB\/x4VFy4gk5tgu0P1+whEaktD9pm8EOYU=","api_user" : "Administrator","api_pass" : null,"created" : "2024-02-19T21:13:14.000Z","updated" : "2024-03-29T21:26:52.000Z"},{"hosting_server_id" : 62,"name" : "janus5","hostname" : "janus5.easyonnet.io","description" : null,"type" : "cpanel","api_key" : "mwoFWCFQW12T9BYksw0by1FC54qQiIQ0v827rE0MEKU1LBdK7qReSAwJIGv6k4ZEIUEY4eRGXdiDAPbHUp+FDQd2fL\/egrltLGS4k\/Jm4CTrlXUYN1bgqZQH4fLt0Q9i46ulDizO\/5rvGi7aMhJBtg==","api_user" : "root","api_pass" : null,"created" : "2024-05-14T19:26:33.000Z","updated" : "2024-05-14T19:26:33.000Z"},{"hosting_server_id" : 63,"name" : "charm.metisentry.net","hostname" : "charm.metisentry.net","description" : null,"type" : "cpanel","api_key" : null,"api_user" : "root","api_pass" : null,"created" : "2024-05-14T20:57:21.000Z","updated" : "2024-05-14T20:57:21.000Z"},{"hosting_server_id" : 64,"name" : "Nekkar","hostname" : "nekkar.cybrhost.com","description" : null,"type" : "plesk","api_key" : null,"api_user" : "root","api_pass" : null,"created" : "2024-05-14T21:01:12.000Z","updated" : "2024-05-14T21:02:17.000Z"},{"hosting_server_id" : 65,"name" : "Taste","hostname" : "taste.cloud.metisentry.net","description" : null,"type" : "plesk","api_key" : null,"api_user" : "root","api_pass" : null,"created" : "2024-05-14T21:03:36.000Z","updated" : "2024-05-14T21:03:52.000Z"},{"hosting_server_id" : 66,"name" : "Spock","hostname" : "spock.metisentry.net","description" : null,"type" : "plesk","api_key" : null,"api_user" : "root","api_pass" : null,"created" : "2024-05-14T21:09:15.000Z","updated" : "2024-05-14T21:09:15.000Z"},{"hosting_server_id" : 67,"name" : "Kirk","hostname" : "kirk.metisentry.net","description" : null,"type" : "plesk","api_key" : null,"api_user" : "root","api_pass" : null,"created" : "2024-05-14T21:10:24.000Z","updated" : "2024-05-14T21:10:24.000Z"},{"hosting_server_id" : 68,"name" : "janus7","hostname" : "janus7.easyonnet.io","description" : "","type" : "cpanel","api_key" : "Lf5RZWmTFHWWZSm3HGIbzhGPxYxRE5mECWlAQS5QKRD6ycXr5ZZ4+Zeg1vO5emu\/EcG+7\/IO4jUSVu4rf4evBI5AKRLc71yf4TnBJtlfUZ7R8AeI4sLbnVYLdQL+2lhhYoTSya84TvhU7ge1cNbPMQ==","api_user" : "root","api_pass" : null,"created" : "2024-09-26T21:13:34.000Z","updated" : "2024-09-26T21:13:34.000Z"},{"hosting_server_id" : 69,"name" : "hosting.picassofish.net","hostname" : "hosting.picassofish.net","description" : "KWIC","type" : "cpanel","api_key" : "rfl7m51C4uf6XU70LoT\/t13r+Ku+DjH6OGvxPEQOFwCyo2jS3nV4jMachMQsceKbviKrXtpuJDcYm1NYo00BsERrlSp9fsOunJehGmC1is+jVrqxnZrtIN+zMTqgpbSkYVDPvyHz4pPXe8FMVeXjzg==","api_user" : "root","api_pass" : null,"created" : "2024-10-15T15:17:29.000Z","updated" : "2024-10-15T15:17:29.000Z"},{"hosting_server_id" : 70,"name" : "neptune.kwic.com","hostname" : "neptune.kwic.com","description" : null,"type" : "cpanel","api_key" : "3qnc91kX\/B5QwpQrIpF9NfSBzy8CNLiXRt1DFOyIa31indIxyQ5eGdwbE7215PCv8nweRkqhOP28iUfHj7vepwRTbhMpeV4yy7n0ZaPSHxGMelZS3ob4AcOGhh\/7YQbe2gUyVsLyAHxHX9j1rdeAt\/o=","api_user" : "root","api_pass" : null,"created" : "2024-10-15T21:24:54.000Z","updated" : "2024-10-15T21:24:58.000Z"},{"hosting_server_id" : 71,"name" : "janus9","hostname" : "janus9.easyonnet.io","description" : null,"type" : "cpanel","api_key" : "qLP+VzrW36IeJfmr39cOe5gVfbPeNtjRCXbctaOAj0b4Bq4qaC3uKRTV4ZhcQzJKmfk8EeDBhL1WHclYBAUxMwOmMQYKnuxG9wpHTWRUQwgDr2JAtpDhEI+VZOUcHmW7m7oJS8+jCN2T7BGpxx1Btw==","api_user" : "root","api_pass" : null,"created" : "2024-12-05T18:16:46.000Z","updated" : "2024-12-05T18:16:46.000Z"},{"hosting_server_id" : 72,"name" : "janus10","hostname" : "janus10.easyonnet.io","description" : "ROG9QM5QJIAE4QMQDO09JKY4S30CMPCY","type" : "cpanel","api_key" : "lbx1\/Smik0sDlszPLz4umHR2j+9RwVCj92hRXICOa8PmoDXa+CQas976GiwyeXgqaOCy06AtvMSRm+iFYMQEj8P\/modBzljpDMay7OHBkYkj3IzAMeUCRBUDvyikMcFEEb\/BrtAZGZm62bDZsvgcQQ==","api_user" : "root","api_pass" : null,"created" : "2025-01-06T18:24:48.000Z","updated" : "2025-01-06T18:24:48.000Z"},{"hosting_server_id" : 73,"name" : "janus11","hostname" : "janus11.easyonnet.io","description" : "6AH16USHS1UYUJ8YX8GKCOCKSXBAJ4ED","type" : "cpanel","api_key" : "PMICFBhpgeXDr5k0WPqKcjXGtQwq9FptRxGPjrlS+4lFmsQCSs1PV0cY0Nb9AQOMvvixdhE\/jDvLQCZvQWdwrvp1KLk7SInGvEsNlhe2Z8bBuydNCxf4nP4KFgYWjgmbo1hxY43BBGOUPPvwlJ8oUw==","api_user" : "root","api_pass" : null,"created" : "2025-01-07T16:36:25.000Z","updated" : "2025-01-07T16:36:25.000Z"}]}
EOT;

        $data = json_decode($json);
        
        return $this->respond($data);
    }

    public function decode_ci_session($raw_data) {
        $return_data = [];
        $offset = 0;
    
        while ($offset < strlen($raw_data)) {
            // Find the position of the next pipe
            $pipe_pos = strpos($raw_data, '|', $offset);
            if ($pipe_pos === false) break;
    
            // Extract the variable name
            $varname = substr($raw_data, $offset, $pipe_pos - $offset);
            $offset = $pipe_pos + 1;
    
            // Try to extract a serialized value
            $serialized = '';
            $open = 0;
            $done = false;
    
            for ($i = $offset; $i < strlen($raw_data); $i++) {
                $serialized .= $raw_data[$i];
    
                // Count braces to detect end of serialization
                if ($raw_data[$i] === '{') $open++;
                if ($raw_data[$i] === '}') $open--;
    
                // Simple heuristic: if balanced and ends in `;` or `}`, try unserializing
                if ($open <= 0 && ($raw_data[$i] === ';' || $raw_data[$i] === '}')) {
                    $test = @unserialize($serialized);
                    if ($test !== false || $serialized === 'b:0;') {
                        $return_data[$varname] = $test;
                        $offset = $i + 1;
                        $done = true;
                        break;
                    }
                }
            }
    
            if (!$done) break; // Stop if we can't parse further
        }
    
        return $return_data;
    }

    public function session_view(){
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Credentials: true");
        $sessionId = $this->request->getJSON(true);
        // $sessionId = '1ddf41f07863c6898f211acde0abda73';
        $sessionFile = WRITEPATH . 'session/ci_session' . $sessionId;
        
        if (file_exists($sessionFile)) {
            $contents = file_get_contents($sessionFile);
            $session_data = $this->decode_ci_session($contents);

            return $this->response->setJSON($session_data);

        } else {
            return $this->respond("Session file not found.");
        }
    }

    public function history_log(){
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Credentials: true");
        $filePath = WRITEPATH . 'actions_log.json';
        $contents = file_get_contents($filePath);
        return $this->respond($contents);
    }

    public function description($func){
        if ($func === "get_domain_info"){
            return ["description" => "Return a list of all domains on a server.",
                    "tag" => "Information Request"];
        }
        else if ($func === "listaccts"){
            return ["description" => "Returns a list of accounts on the server",
                    "tag" => "Information Request"];
        }
        else if ($func === "listpkgs"){
            return ["description" => "Lists available Packages/Plans on the server",
                    "tag" => "Information Request"];
        }
        else if ($func === "count_pops"){
            return ["description" => "Returns a count of all email addresses on the account",
                    "tag" => "Information Request"];
        }
        else if ($func === "createacct"){
            return ["description" => "Creates a new CPanel Account on the server",
                    "tag" => "Changes Will be Made"];
        }
        else if ($func === "delete_pop"){
            return ["description" => "Removes an Email Address",
                    "tag" => "Changes Will be Made"];
        }
        else if ($func === "list_pops"){
            return ["description" => "Returns a list of all email addresses on the account",
                    "tag" => "Information Request"];
        }
        else if ($func === "add_pop"){
            return ["description" => "Adds a new Email Address to the Account",
                    "tag" => "Changes Will be Made"];
        }
        else if ($func === "list_forwarders"){
            return ["description" => "Lists all forwarders on the server for the provided domain name",
                    "tag" => "Information Request"];
        }
        else if ($func === "add_forwarder"){
            return ["description" => "Adds an Email Forwarder",
                    "tag" => "Changes Will be Made"];
        }
        else if ($func === "getClientDetails"){
            return ["description" => "Returns info about a specified client",
                    "tag" => "Information Request"];
        }
        else if ($func === "getInvoices"){
            return ["description" => "Returns invoices for a specifc client",
                    "tag" => "Information Request"];
        }
        else if ($func === "getClientsProducts"){
            return ["description" => "Returns info about a client's products",
                    "tag" => "Information Request"];
        }
        else if ($func === "getProducts"){
            return ["description" => "Returns info a specifc requested product",
                    "tag" => "Information Request"];
        }
        else if ($func === "systemloadavg"){
            return ["description" => "Retrieves the system's load average.",
                    "tag" => "Information Request"];
        }
        else if ($func === "get_disk_usage"){
            return ["description" => "Return all cPanel accounts disk usage.",
                    "tag" => "Information Request"];
        }
        else if ($func === "get_information"){
            return ["description" => "Returns service and device status",
                    "tag" => "Information Request"];
        }
    }

    public function formatListaccts($data) {
        $accounts = [];
        $data = $data['data'];
    
        foreach ($data['acct'] as $acct) {
            $accounts[] = [
                'Domain'     => $acct['domain'] ?? 'N/A',
                'Username'   => $acct['user'] ?? 'N/A',
                'Email'      => $acct['email'] ?? 'N/A',
                'Status'     => (isset($acct['suspended']) && $acct['suspended']) ? 'Suspended' : 'Active',
            ];
        }
    return $accounts;
    }

    public function formatClientDetails($data){
        $formattedClient = [];
        $formattedClient[] = [
            'Full Name'    => $data['fullname'],
            'Status'    => $data['status'],
            'Last Login'    => $data['lastlogin']
        ];
        return $formattedClient;
    }

    public function formatClient($data) {
        $formattedClients = [];
    
        // Support both direct array or array of objects
        foreach ($data as $client) {
            $formattedClients[] = [
                'Full Name'    => $data['fullname'] ?? ($client['firstname'] ?? '') . ' ' . ($client['lastname'] ?? ''),
                'Status'       => $data['status'] ?? 'Unknown',
                'Last Login'   => $data['lastlogin'] ?? 'N/A',
            ];
        }
    
        return $formattedClients;
    }

    public function formatGetClientsProducts($data) {
        $formattedProducts = [];

        if (empty($data['products'])){
            $formattedProducts[] = "No products found";
            return $formattedProducts;
        }
    
        // Support both direct array or array of objects
        foreach ($data['products']['product'] as $product) {
            $formattedProducts[] = [
                'Product ID'    => $product['pid'],
                'Product Name'  => $product['name'],
                'Domain'        => $product['domain'],
                'Server ID'      => $product['serverid'],
                'Server Name'  => $product['servername'],
                'Server IP'        => $product['serverip'] ?? "",
                'Server Hostname'      => $product['serverhostname'],
                'Disk Usage'      => $product['diskusage'],
                'Disk Limit'      => $product['disklimit'],
            ];
        }
    
        return $formattedProducts;
    }

    public function formatGetProducts($data) {
        $formattedProducts = [];
        $product = $data['products']['product'][0];

        $formattedProducts[] = [
            'Module'    => $product['module'],
            'Product Name'    => $product['name']
        ];

        return $formattedProducts;
    }
    

    public function formatToolCalls(array $assistantMessage): array
    {
        $formattedCalls = [];
    
        if (!isset($assistantMessage['tool_calls']) || !is_array($assistantMessage['tool_calls'])) {
            return $formattedCalls; // Return empty array if no tool calls
        }
    
        foreach ($assistantMessage['tool_calls'] as $toolCall) {
            $id = $toolCall['id'] ?? null;
            $name = $toolCall['function']['name'] ?? null;
            $args = $toolCall['function']['arguments'] ?? '{}';
    
            // Decode arguments safely
            $decodedArgs = json_decode($args, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $decodedArgs = ['_raw' => $args]; // Fallback in case of bad JSON
            }
    
            $formattedCalls[] = [
                'call_id'   => $id,
                'name'      => $name,
                'arguments' => $decodedArgs,
            ];
        }
    
        return $formattedCalls;
    }

    public function formatSystemloadavg($data) {
        $formattedLoading = [];
        $loadTime = $data['data'];

        $formattedLoading[] = [
            'Previous Minute Load Time'    => $loadTime['one'],
            'Previous Five Minutes Load Time'    => $loadTime['five'],
            'Previous Fifteen Minutes Load Time'    => $loadTime['fifteen']
        ];

        return $formattedLoading;
    }

    public function formatGet_domain_info($data) {
        $formattedDomains = [];
        $domains= $data['data']['domains'];

        foreach ($domains as $domain) {
            $formattedDomains[] = [
                'Domain'    => $domain['domain'],
                'Document Root'  => $domain['docroot'],
                'User'        => $domain['domain'],
                'Domain Type'      => $domain['domain_type'],
                'User Owner'  => $domain['user_owner'],
                'ipv4'        => $domain['ipv4'] ?? "",
                'Port'      => $domain['port']
            ];
        }

        return $formattedDomains;
    }

    public function formatListpkgs($data) {
        $formattedPkgs = [];
        $pkgs = $data['data']['pkg'];

        foreach ($pkgs as $pkg) {
            $formattedPkgs[] = [
                'Bandwidth Limit'    => $pkg['BWLIMIT'] ?? "",
                'Package Name'  => $pkg['name'] ?? "",
                'Quota'        => $pkg['QUOTA'] ?? "",
                'Max Emails Sending Limit Per Hour'      => $pkg['MAX_EMAIL_PER_HOUR'] ?? "",
                'Max Limit for Deferred/Failed Sent Per Hour'  => $pkg['[MAX_DEFER_FAIL_PERCENTAGE'] ?? "",
                'Max Number of Addon Domains'      => $pkg['MAXADDON'] ?? ""
            ];
        }

        return $formattedPkgs;
    }

    public function formatGet_disk_usage($data) {
        $formattedAccts = [];
        $accts = $data['data']['accounts'];

        foreach ($accts as $acct) {
            $formattedAccts[] = [
                'Disk Blocks Used'    => $acct['blocks_used'] ?? "",
                'Disk Blocks Limit'  => $acct['blocks_limit'] ?? "",
                'Inodes Limit'        => $acct['inodes_limit'] ?? "",
                'Inodes Used'      => $acct['inodes_used'] ?? "",
                'User'  => $acct['user'] ?? ""
            ];
        }

        return $formattedAccts;
    }

    public function formatList_pops($data) {
        $formattedEmails = [];
        $emails = $data['result']['data'];

        foreach ($emails as $email) {
            $formattedEmails[] = [
                'Email'    => $email['email'] ?? ""
            ];
        }

        return $formattedEmails;
    }

    public function formatGet_information($data) {
        $formattedServices = [];
        $services = $data['result']['data'];

        foreach ($services as $service) {
            $formattedServices[] = [
                'Name'    => $service['name'] ?? "",
                'Type'    => $service['type'] ?? "",
                'Status'    => $service['status'] ?? "",
                'Value'    => $service['value'] ?? "",
            ];
        }

        return $formattedServices;
    }
    
    
    public function sum_cat_AI(){

        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Credentials: true");
        
        $open_ai_key = getenv('app.openai_key');
        $json = $this->request->getJSON(true);
        $ticketId = $json['message'] ?? '';

        $whmcs = new WHMCS('https://portal.easyonnet.io/includes/api.php',
        'm2ftQEEegLHpyLPD6fiUpAqA0mx9T1XL',
        'ooxF2HcGp1VjifSAB6bcHO6WfunujurY');

        
        $APIresponse = $whmcs->getTicket($ticketId); 
        $userMessage = "TICKET: " . $APIresponse["replies"]["reply"][0]["message"];
        $priority = $APIresponse['priority'];


        $session = session();
        $chatHistory = $session->get('history') ?? [];
        $chatHistory[] = ["role" => "user", "name" => "agent_chat", "content" =>  "Client ID: " . $APIresponse['userid']];
        $session->set('history', $chatHistory);

        
        $url = "https://api.openai.com/v1/chat/completions";
        $headers = [
            "Authorization: Bearer $open_ai_key",
            "Content-Type: application/json"
        ];

        $toolsSum = [
            [
                "type" => "function",
                "function" => [
                    "name" => "summary_cat",
                    "description" => "Summerizes a user's message",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "summary" => [
                            "type" => "string",
                            "description" => "The summary of the user's message 
                            generated by you, as an AI assistant"
                        ],
                        "category" => [
                            "type" => "string",
                            "description" => "The category you, as an AI assistant
                            would put the user's message in where the categories are, 
                            Web Hosting, Email Account Help,
                            Billing and Account Help, DNS Problems, Website Problems,
                            General or Unknown Issue."
                        ]
                        ],
                        "required" => ["summary", "category"]
                    ]
                ]
            ]
        ];

        $aiSum = [
            //"model" => "gpt-4-turbo",
            "model" => "gpt-4o",
            "messages" => array_merge([
                [
                    "role" => "system",
                    "content" => "You are a helpful AI Assistant that summarizes a user's 
                    message in a short form as possible that only you understand while still 
                    including important details. The summary should not be longer than the
                    user's original message. You will then choose a category 
                    from the following categories: Web Hosting, Email Account Help,
                            Billing and Account Help, DNS Problems, Website Problems,
                            General or Unknown Issue, 
                    to classify the user's message."
                ]
            , ["role" => "user", "content" => $userMessage]]),
            "temperature" => 0.7,
            "tools" => $toolsSum,
            "tool_choice" => "required"
        ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1); // 👈 Force HTTP/1.1
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($aiSum));
    
            $responseSum = curl_exec($ch);
            
            curl_close($ch);
    
            $responseDataSum = json_decode($responseSum, true);
 
            $rawArguments = $responseDataSum['choices'][0]['message']['tool_calls'][0]['function']['arguments'];

            $arguments = json_decode($rawArguments, true);
            $userMessage = "TICKET: " . $arguments["summary"];
            $category = "CATEGORY: " . $arguments["category"];
            return $this->respond([
                "summary" => $userMessage,
                "category" => $category,
                "priority" => "Priority: " . $priority
            ]);
    }

    public function enterClientInfo(){
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Credentials: true");
        
        $open_ai_key = getenv('app.openai_key');
        $json = $this->request->getJSON(true);
        $clientID = $json['clientID'] ?? '';
        $product = $json['product'] ?? '';
        $server = $json['server'] ?? '';

        $session = session();
        $chatHistory = $session->get('history') ?? [];
        if ($clientID != ''){
            $chatHistory[] = ["role" => "user", "name" => "client_info", "content" =>  "Client ID: " . $clientID];
        }

        if ($product != ''){
            $chatHistory[] = ["role" => "user", "name" => "product_info", "content" =>  "Product: " . $product];
        }
        
        if ($server != ''){
            $chatHistory[] = ["role" => "user", "name" => "server_info", "content" =>  "Server: " . $server];
        }
        $session->set('history', $chatHistory);
    }
    
}

<?php
namespace App\Controllers;
require_once __DIR__ . '/../APILib.php';
require_once __DIR__ . '/../WHMCS.php';
require_once __DIR__ . '/../Utils/ValidationHelper.php';
require_once __DIR__ . '/../Utils/ResponseFormatter.php';
require_once __DIR__ . '/../Utils/ContextManager.php';
require_once __DIR__ . '/../Utils/ErrorHandler.php';

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;
use Cpanel\APILib;
use WHMCS;
use App\Utils\ValidationHelper;
use App\Utils\ResponseFormatter;
use App\Utils\ContextManager;
use App\Utils\ErrorHandler;

class AiController extends BaseController
{
    use ResponseTrait;

    private $validationHelper;
    private $responseFormatter;
    private $contextManager;
    private $errorHandler;
    private $cpanel;
    private $whmcs;

    public function __construct()
    {
        $this->validationHelper = new ValidationHelper();
        $this->responseFormatter = new ResponseFormatter();
        $this->contextManager = new ContextManager();
        $this->errorHandler = new ErrorHandler();
        
        // Initialize API connections with better error handling
        try {
            $this->cpanel = new APILib('janus13.easyonnet.io', 'root','GGWKWFGGCBIGJZ9RJV8Z7H47TM8YA1EG', '2087');
            $this->whmcs = new WHMCS('https://portal.easyonnet.io/includes/api.php',
                'm2ftQEEegLHpyLPD6fiUpAqA0mx9T1XL',
                'ooxF2HcGp1VjifSAB6bcHO6WfunujurY');
        } catch (Exception $e) {
            $this->errorHandler->logError('API_INIT_FAILED', $e->getMessage());
        }
    }

    public function index()
    {
        return $this->respond(['status' => 'AI Controller Active', 'version' => '2.0.0']);
    }

    public function proceed()
    {
        try {
        $data = $this->request->getJSON(true);
            
            // Enhanced validation
            $validationResult = $this->validationHelper->validateProceedRequest($data);
            if (!$validationResult['valid']) {
                return $this->respondWithValidationError($validationResult['errors']);
            }

        $session = session();
            $func = $data[0]["functionName"];
            $parameters = $data[0]["parameters"];

            // Log the action attempt
            $this->logInteraction('proceed', $func, $parameters, $session->session_id);

            // Execute function with enhanced error handling
            $result = $this->executeFunction($func, $parameters);
            
            if ($result['success']) {
                // Update context and history
                $this->contextManager->updateContext($session, $func, $result['data']);
                
                // Store API response data for follow-up questions
                $recentApiData = $session->get('recent_api_data') ?? [];
                $recentApiData[] = [
                    'function' => $func,
                    'timestamp' => time(),
                    'response' => $result['data']
                ];
                
                // Keep only last 3 API responses to prevent memory bloat
                if (count($recentApiData) > 3) {
                    $recentApiData = array_slice($recentApiData, -3);
                }
                
                $session->set('recent_api_data', $recentApiData);
                
                // Generate follow-up ticket response
                $ticketResponse = $this->generateTicketResponse($func, $parameters, $result['data'], $session);
                
                return $this->respond([
                    "status" => "success",
                    "response" => $this->responseFormatter->formatSuccessMessage($func, $parameters),
                    "API_response" => [$result['data']],
                    "execution_time" => $result['execution_time'] ?? null,
                    "pending_functions" => $ticketResponse ? [$ticketResponse] : []
                ]);
            } else {
                return $this->respondWithError($result['error'], $result['code']);
            }

        } catch (Exception $e) {
            $this->errorHandler->logError('PROCEED_EXCEPTION', $e->getMessage(), $e->getTrace());
            return $this->respondWithError('An unexpected error occurred: ' . $e->getMessage(), 500);
        }
    }

    public function rejected(){
        $session = session();

        $chatHistory = $session->get('history') ?? [];

        $chatHistory[] = ["role" => "user", "content" => "User denied the use of those tools"];
        $session->set('history', $chatHistory);
        return $this->respond(["response" => "Action was rejected"]);
    }

    public function json_log($functionsCalled, $sessionID){
        $filePath = WRITEPATH . 'actions_log.json';
        if (filesize($filePath) > 0){
            $content = file_get_contents($filePath);
            $jsonObj = json_decode($content, true);
        }

        else {
            $jsonObj = [];
        }

        $jsonObj[] = [
            'date' => date('Y-m-d H:i:s'),
            'session_id' => $sessionID,
            'action' => $functionsCalled

        ];

        $jsonData = json_encode($jsonObj, JSON_PRETTY_PRINT);
        file_put_contents($filePath, $jsonData);
    }

    public function chat()
    {
        try {
            $startTime = microtime(true);
            $session = session();
            
            $config = config('App');
            $open_ai_key = $config->openai_key;
            $json = $this->request->getJSON(true);
            $userMessage = trim($json['message'] ?? '');

            // Enhanced input validation
            $validationResult = $this->validationHelper->validateChatInput($userMessage);
            if (!$validationResult['valid']) {
                return $this->respondWithValidationError($validationResult['errors']);
            }

            // Get and enhance chat history with context
            $chatHistory = $this->contextManager->getChatHistory($session);
            $conversationContext = $this->contextManager->analyzeConversationContext($chatHistory);
            
            // Get recent API response data from session for follow-up questions
            $recentApiData = $session->get('recent_api_data') ?? [];
            
            // Build enhanced message history with recent API data
            $historyString = $this->contextManager->buildEnhancedHistory($chatHistory, $userMessage, $conversationContext, $recentApiData);

            // Get improved system prompt
            $systemPrompt = $this->getEnhancedSystemPrompt($conversationContext);
            
            // Get enhanced tools definition
            $tools = $this->getEnhancedToolsDefinition();

            // Smart tool choice determination
            $toolChoice = $this->determineToolChoice($userMessage, $conversationContext);

            $data = [
                "model" => "gpt-4o",
                "messages" => array_merge([
                    ["role" => "system", "content" => $systemPrompt]
                ], $historyString),
                "temperature" => 0.3, // Lower for consistency
                "top_p" => 0.9,
                "frequency_penalty" => 0.1,
                "presence_penalty" => 0.1,
                "max_tokens" => 4000,
                "tools" => $tools,
                "tool_choice" => $toolChoice
            ];

            // Enhanced OpenAI API call with retry logic
            $responseData = $this->makeOpenAIRequest($data, $open_ai_key);
            
            if (!$responseData || !isset($responseData['choices'][0]['message'])) {
                return $this->respondWithError("Failed to get AI response", 500);
            }

            $aiMessage = $responseData['choices'][0]['message'];
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            // Enhanced response processing
            $processedResponse = $this->processAIResponse($aiMessage, $json, $session, $userMessage);
            $processedResponse['execution_time'] = $executionTime . 'ms';
            $processedResponse['tokens_used'] = $responseData['usage'] ?? null;

            // Enhanced logging
            $this->logInteraction('chat', $userMessage, $processedResponse, $session->session_id);

            return $this->respond($processedResponse);

        } catch (Exception $e) {
            $this->errorHandler->logError('CHAT_EXCEPTION', $e->getMessage(), $e->getTrace());
            return $this->respondWithError('Chat service temporarily unavailable', 503);
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

    public function clear(){
        $session = session();
        $session->remove('history');
        $session->remove('response');
        $session->remove('recent_api_data');
        
        return $this->respond([
            'status' => 'success',
            'message' => 'Chat history cleared successfully'
        ]);
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
        $sessionId = $this->request->getJSON(true);
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
        $filePath = WRITEPATH . 'actions_log.json';
        $contents = file_get_contents($filePath);
        return $this->respond($contents);
    }

    public function description($func){
        if ($func === "get_domain_info"){
            return "Return a list of all domains on a server.";
        }
        else if ($func === "listaccts"){
            return "Returns a list of accounts on the server";
        }
        else if ($func === "listpkgs"){
            return "Lists available Packages/Plans on the server";
        }
        else if ($func === "count_pops"){
            return "Returns a count of all email addresses on the account";
        }
        else if ($func === "createacct"){
            return "Creates a new CPanel Account on the server";
        }
        else if ($func === "delete_pop"){
            return "Removes an Email Address";
        }
        else if ($func === "list_pops"){
            return "Returns a list of all email addresses on the account";
        }
        else if ($func === "add_pop"){
            return "Adds a new Email Address to the Account";
        }
        else if ($func === "list_forwarders"){
            return "Lists all EMAIL FORWARDERS configured for a specific domain under a cPanel account. This is specifically for email forwarding rules, NOT hosting accounts.";
        }
        else if ($func === "add_forwarder"){
            return "Adds an Email Forwarder";
        }
        else if ($func === "client"){
            return "Returns info about a specified client";
        }
        else if ($func === "invoices"){
            return "Returns invoices for a specifc client";
        }
    }

    private function getEnhancedSystemPrompt($conversationContext)
    {
        $basePrompt = "You are an advanced AI Technical Support Assistant for a hosting company specializing in cPanel/WHM and WHMCS systems. ";
        $basePrompt .= "You provide accurate, efficient, and professional support with a focus on hosting, server management, and client billing.\n\n";
        
        $basePrompt .= "CORE CAPABILITIES:\n";
        $basePrompt .= "• Server & Account Management (cPanel/WHM)\n";
        $basePrompt .= "• Email Management (create, delete, list accounts)\n";
        $basePrompt .= "• Client Management (WHMCS integration)\n";
        $basePrompt .= "• Billing & Invoice Operations\n";
        $basePrompt .= "• Domain & Package Administration\n";
        $basePrompt .= "• Server Monitoring & Health Analysis (load, services, disk usage)\n";
        $basePrompt .= "• Real-time Server Troubleshooting & Performance Assessment\n";
        $basePrompt .= "• Bandwidth Analysis & Usage Monitoring\n\n";
        
        $basePrompt .= "CRITICAL FOLLOW-UP QUESTION HANDLING:\n";
        $basePrompt .= "When users ask follow-up questions about recently retrieved data (like 'is this normal?', 'what does this mean?', 'should I be worried?'):\n";
        $basePrompt .= "• DO NOT request the same data again\n";
        $basePrompt .= "• DO NOT call functions to retrieve data that was just shown\n";
        $basePrompt .= "• INSTEAD, analyze and interpret the existing data in your conversation context\n";
        $basePrompt .= "• Provide expert analysis, interpretation, and recommendations\n";
        $basePrompt .= "• Reference specific values from the previous response\n";
        $basePrompt .= "• Explain what the numbers mean in practical terms\n";
        $basePrompt .= "• Offer actionable troubleshooting steps if issues are found\n\n";
        
        $basePrompt .= "FOLLOW-UP QUESTION PATTERNS TO RECOGNIZE:\n";
        $basePrompt .= "• 'Is this normal?' → Analyze data against industry standards\n";
        $basePrompt .= "• 'What does this mean?' → Explain technical details in plain language\n";
        $basePrompt .= "• 'Should I be worried?' → Risk assessment with recommendations\n";
        $basePrompt .= "• 'How can I fix this?' → Specific troubleshooting steps\n";
        $basePrompt .= "• 'Is the server healthy?' → Health assessment based on shown data\n";
        $basePrompt .= "• 'What's causing high load?' → Root cause analysis\n";
        $basePrompt .= "• 'How much disk space is left?' → Analysis of shown disk data\n";
        $basePrompt .= "• 'Are any services down?' → Service status interpretation\n\n";
        
        $basePrompt .= "SERVER LOAD ANALYSIS EXPERTISE:\n";
        $basePrompt .= "Load Average Interpretation:\n";
        $basePrompt .= "• 0.0-0.7: Excellent - Server has plenty of spare capacity\n";
        $basePrompt .= "• 0.7-1.0: Good - Normal load, server handling requests well\n";
        $basePrompt .= "• 1.0-1.5: Moderate - Server is busy but managing fine\n";
        $basePrompt .= "• 1.5-2.0: High - Server approaching capacity limits\n";
        $basePrompt .= "• 2.0-4.0: Very High - Performance degradation likely\n";
        $basePrompt .= "• 4.0+: Critical - Immediate attention required\n\n";
        
        $basePrompt .= "Load Trend Analysis:\n";
        $basePrompt .= "• 1-min > 5-min > 15-min: Load is increasing (concerning)\n";
        $basePrompt .= "• 1-min < 5-min < 15-min: Load is decreasing (good sign)\n";
        $basePrompt .= "• All three similar: Stable load (normal pattern)\n";
        $basePrompt .= "• Spiky 1-min vs stable 5/15-min: Temporary load bursts\n\n";
        
        $basePrompt .= "DISK USAGE ANALYSIS EXPERTISE:\n";
        $basePrompt .= "Disk Space Health Thresholds:\n";
        $basePrompt .= "• 0-60% usage: Healthy - Plenty of space available\n";
        $basePrompt .= "• 60-75% usage: Monitor - Keep an eye on growth rate\n";
        $basePrompt .= "• 75-85% usage: Attention - Plan for cleanup or expansion\n";
        $basePrompt .= "• 85-95% usage: Warning - Immediate action recommended\n";
        $basePrompt .= "• 95%+ usage: Critical - System stability at risk\n\n";
        
        $basePrompt .= "ACCOUNT DISK USAGE ANALYSIS:\n";
        $basePrompt .= "• >5GB per account: Review for optimization opportunities\n";
        $basePrompt .= "• >10GB per account: Investigate for large files or logs\n";
        $basePrompt .= "• Rapid growth: Monitor for runaway processes or attacks\n";
        $basePrompt .= "• Multiple large accounts: Consider load balancing\n\n";
        
        $basePrompt .= "SERVICE STATUS ANALYSIS EXPERTISE:\n";
        $basePrompt .= "Critical Services for Web Hosting:\n";
        $basePrompt .= "• HTTPD (Apache): Essential - All websites down if stopped\n";
        $basePrompt .= "• MySQL: Critical - Database-driven sites affected\n";
        $basePrompt .= "• EXIM: Important - Email delivery impacted\n";
        $basePrompt .= "• NAMED (DNS): Vital - Domain resolution issues\n";
        $basePrompt .= "• FTPD: Moderate - File upload/download affected\n";
        $basePrompt .= "• cPaneld: Critical - Control panel access affected\n\n";
        
        $basePrompt .= "SERVICE TROUBLESHOOTING PRIORITIES:\n";
        $basePrompt .= "1. HTTPD down: Immediate priority - affects all websites\n";
        $basePrompt .= "2. MySQL issues: High priority - affects dynamic sites\n";
        $basePrompt .= "3. DNS problems: High priority - affects domain resolution\n";
        $basePrompt .= "4. Mail issues: Medium priority - affects email services\n";
        $basePrompt .= "5. FTP issues: Low priority - affects file management\n\n";
        
        $basePrompt .= "BANDWIDTH ANALYSIS EXPERTISE:\n";
        $basePrompt .= "Bandwidth Usage Interpretation:\n";
        $basePrompt .= "• Normal residential: 100-500GB/month per site\n";
        $basePrompt .= "• Business websites: 500GB-2TB/month\n";
        $basePrompt .= "• High-traffic sites: 2TB+ per month\n";
        $basePrompt .= "• Sudden spikes: Investigate for DDoS or viral content\n";
        $basePrompt .= "• Consistent high usage: Normal for media-heavy sites\n\n";
        
        $basePrompt .= "COMPREHENSIVE API KNOWLEDGE BASE:\n\n";
        $basePrompt .= "=== BANDWIDTH MONITORING ===\n";
        $basePrompt .= "showbw Function - Bandwidth Information Retrieval:\n";
        $basePrompt .= "Purpose: Retrieves bandwidth usage statistics for reseller accounts\n";
        $basePrompt .= "Response Structure:\n";
        $basePrompt .= "• object: Main response object containing all bandwidth data\n";
        $basePrompt .= "• acct: Array of objects - Bandwidth information for reseller's accounts\n";
        $basePrompt .= "  - Each account object contains usage statistics, limits, and consumption data\n";
        $basePrompt .= "• month: integer [1-12] - The queried month for bandwidth data\n";
        $basePrompt .= "• reseller: string - The reseller username or root user\n";
        $basePrompt .= "• totalused: integer ≥0 - Total bandwidth usage in bytes during queried period\n";
        $basePrompt .= "• year: integer - The queried year for bandwidth data\n\n";
        
        $basePrompt .= "=== SERVER MONITORING & TROUBLESHOOTING ===\n";
        $basePrompt .= "Server Load Analysis (get_server_load):\n";
        $basePrompt .= "• Purpose: Returns 1, 5, 15 minute load averages for performance assessment\n";
        $basePrompt .= "• Load Interpretation:\n";
        $basePrompt .= "  - 0-1.0: Excellent performance, CPU has spare capacity\n";
        $basePrompt .= "  - 1.0-2.0: Good performance, normal load for single-core systems\n";
        $basePrompt .= "  - 2.0+: High load, may indicate performance issues\n";
        $basePrompt .= "• Troubleshooting Tips:\n";
        $basePrompt .= "  - High 1-min load: Check for runaway processes or DDoS attacks\n";
        $basePrompt .= "  - High 5-min load: Investigate sustained high traffic or resource-intensive scripts\n";
        $basePrompt .= "  - High 15-min load: Server may be consistently overloaded, consider resource upgrades\n";
        $basePrompt .= "  - Load > CPU cores: Performance degradation expected\n\n";
        
        $basePrompt .= "Server Status Overview (get_server_status):\n";
        $basePrompt .= "• Purpose: Comprehensive health including load, version, accounts, bandwidth\n";
        $basePrompt .= "• Data Points: Load averages, cPanel version, account count, bandwidth statistics\n";
        $basePrompt .= "• Health Indicators:\n";
        $basePrompt .= "  - Load trends over time periods\n";
        $basePrompt .= "  - Account distribution and resource usage\n";
        $basePrompt .= "  - System version compatibility\n";
        $basePrompt .= "• Common Issues & Solutions:\n";
        $basePrompt .= "  - Outdated cPanel: Schedule maintenance window for updates\n";
        $basePrompt .= "  - High account density: Consider load balancing or server migration\n";
        $basePrompt .= "  - Bandwidth spikes: Investigate top consumers and potential abuse\n\n";
        
        $basePrompt .= "Disk Usage Analysis (get_disk_usage):\n";
        $basePrompt .= "• Purpose: Account-by-account disk usage analysis with optimization recommendations\n";
        $basePrompt .= "• Response includes: Total usage, per-account breakdown, heaviest users, partition info\n";
        $basePrompt .= "• Critical Thresholds:\n";
        $basePrompt .= "  - >80% partition usage: Immediate attention required\n";
        $basePrompt .= "  - >5GB per account: Review for optimization opportunities\n";
        $basePrompt .= "  - Rapid growth: Monitor for log file accumulation or uploads\n";
        $basePrompt .= "• Optimization Strategies:\n";
        $basePrompt .= "  - Log rotation and cleanup for high-usage accounts\n";
        $basePrompt .= "  - Database optimization and cleanup\n";
        $basePrompt .= "  - Backup compression and archival\n";
        $basePrompt .= "  - WordPress cache and plugin cleanup\n";
        $basePrompt .= "  - Email quota management and cleanup\n\n";
        
        $basePrompt .= "Server Services Status (get_server_services):\n";
        $basePrompt .= "• Purpose: Complete service status (Apache, MySQL, FTP, DNS, mail)\n";
        $basePrompt .= "• Monitored Services:\n";
        $basePrompt .= "  - HTTPD (Apache): Web server for hosting websites\n";
        $basePrompt .= "  - MySQL: Database server for applications\n";
        $basePrompt .= "  - FTPD: File transfer service\n";
        $basePrompt .= "  - EXIM: Mail transfer agent\n";
        $basePrompt .= "  - NAMED (BIND): DNS resolution service\n";
        $basePrompt .= "• Service Troubleshooting:\n";
        $basePrompt .= "  - Apache Down: Check error logs, configuration syntax, resource limits\n";
        $basePrompt .= "  - MySQL Issues: Investigate slow queries, connection limits, memory usage\n";
        $basePrompt .= "  - FTP Problems: Verify user permissions, passive mode settings, firewall rules\n";
        $basePrompt .= "  - Mail Issues: Check queue, DNS records (MX, SPF), blacklist status\n";
        $basePrompt .= "  - DNS Problems: Verify zone files, recursion settings, upstream resolvers\n";
        $basePrompt .= "• Performance Optimization:\n";
        $basePrompt .= "  - Apache: Tune MaxRequestWorkers, enable compression, optimize .htaccess\n";
        $basePrompt .= "  - MySQL: Optimize queries, tune my.cnf, implement caching\n";
        $basePrompt .= "  - Mail: Configure rate limiting, implement greylisting\n\n";
        
        $basePrompt .= "=== ACCOUNT MANAGEMENT ===\n";
        $basePrompt .= "Account Functions Response Structures:\n";
        $basePrompt .= "• listaccts: Returns array of hosting accounts with domain, user, package, disk usage, creation date\n";
        $basePrompt .= "• createacct: Creates new cPanel account, returns success/failure with account details\n";
        $basePrompt .= "• listpkgs: Returns hosting packages with quotas, bandwidth limits, feature restrictions\n\n";
        
        $basePrompt .= "=== EMAIL MANAGEMENT ===\n";
        $basePrompt .= "Email Functions Response Structures:\n";
        $basePrompt .= "• list_pops: Returns email accounts with quotas, usage, last login times\n";
        $basePrompt .= "• count_pops: Returns integer count of total email accounts\n";
        $basePrompt .= "• add_pop: Creates email account, returns success confirmation\n";
        $basePrompt .= "• delete_pop: Removes email account, returns deletion status\n";
        $basePrompt .= "• list_forwarders: Returns email forwarding rules for specified domain\n";
        $basePrompt .= "• add_forwarder: Creates email forwarder, returns configuration details\n\n";
        
        $basePrompt .= "=== CLIENT MANAGEMENT (WHMCS) ===\n";
        $basePrompt .= "Client Functions Response Structures:\n";
        $basePrompt .= "• client: Returns comprehensive client data including:\n";
        $basePrompt .= "  - Personal info (name, email, phone, address)\n";
        $basePrompt .= "  - Account status, currency, credit balance\n";
        $basePrompt .= "  - Registration date, last login, billing preferences\n";
        $basePrompt .= "• invoices: Returns invoice array with amounts, due dates, payment status, line items\n\n";
        
        $basePrompt .= "EXPERT TROUBLESHOOTING KNOWLEDGE:\n\n";
        $basePrompt .= "=== PERFORMANCE ISSUES ===\n";
        $basePrompt .= "High Load Diagnostics:\n";
        $basePrompt .= "• Immediate Actions: Check top processes, identify resource hogs, review recent changes\n";
        $basePrompt .= "• Analysis Steps: Review Apache access logs, MySQL slow query log, system logs\n";
        $basePrompt .= "• Common Causes: DDoS attacks, poorly optimized code, database locks, backup processes\n";
        $basePrompt .= "• Mitigation: Implement rate limiting, optimize queries, schedule intensive tasks\n\n";
        
        $basePrompt .= "Disk Space Issues:\n";
        $basePrompt .= "• Quick Wins: Clear log files, empty trash, compress backups\n";
        $basePrompt .= "• Investigation: Use 'du -sh' to find large directories, check for core dumps\n";
        $basePrompt .= "• Prevention: Implement log rotation, automated cleanup scripts, quota monitoring\n\n";
        
        $basePrompt .= "Service Failures:\n";
        $basePrompt .= "• Apache Won't Start: Check syntax (httpd -t), review error logs, verify ports\n";
        $basePrompt .= "• MySQL Crashes: Check error log, verify disk space, review my.cnf settings\n";
        $basePrompt .= "• Mail Issues: Test connectivity, check DNS records, review queue status\n\n";
        
        $basePrompt .= "=== SECURITY CONSIDERATIONS ===\n";
        $basePrompt .= "• Monitor for unusual bandwidth spikes (potential attacks)\n";
        $basePrompt .= "• Watch for accounts with excessive resource usage\n";
        $basePrompt .= "• Regular security updates and patches\n";
        $basePrompt .= "• Implement fail2ban and ModSecurity\n";
        $basePrompt .= "• Regular malware scanning and cleanup\n\n";
        
        $basePrompt .= "FUNCTION SELECTION RULES:\n";
        $basePrompt .= "• 'bandwidth usage' or 'showbw' = use showbw function (no parameters needed)\n";
        $basePrompt .= "• 'list email forwarders' = use list_forwarders function (requires cpanel_user and domain)\n";
        $basePrompt .= "• 'list accounts' or 'list hosting accounts' = use listaccts function (no parameters)\n";
        $basePrompt .= "• 'list emails' or 'list email accounts' = use list_pops function (requires cpanel_user)\n";
        $basePrompt .= "• 'count emails' = use count_pops function (requires cpanel_user)\n";
        $basePrompt .= "• 'create email' or 'add email' = use add_pop function (requires cpanel_user, email_user, password)\n";
        $basePrompt .= "• 'create account' = use createacct function (requires username, domain, contact_email)\n";
        $basePrompt .= "• 'client info' or 'show client' = use client function (requires client_id)\n";
        $basePrompt .= "• 'server load' or 'check load' = use get_server_load function (no parameters)\n";
        $basePrompt .= "• 'server status' or 'server health' = use get_server_status function (no parameters)\n";
        $basePrompt .= "• 'disk usage' or 'disk space' = use get_disk_usage function (no parameters)\n";
        $basePrompt .= "• 'server services' or 'service status' = use get_server_services function (no parameters)\n\n";
        
        $basePrompt .= "DOCUMENTATION EXPERTISE:\n";
        $basePrompt .= "You have complete knowledge of all API endpoints and their response structures. ";
        $basePrompt .= "When users ask about API functions, provide comprehensive documentation including:\n";
        $basePrompt .= "• Function purpose and use cases\n";
        $basePrompt .= "• Required parameters and their data types\n";
        $basePrompt .= "• Complete response structure with all fields\n";
        $basePrompt .= "• Data types and constraints for each field\n";
        $basePrompt .= "• Example use cases and practical applications\n";
        $basePrompt .= "• Troubleshooting tips and optimization suggestions\n";
        $basePrompt .= "• Performance benchmarks and thresholds\n";
        $basePrompt .= "• Security considerations and best practices\n\n";
        
        $basePrompt .= "FOLLOW-UP QUESTION HANDLING:\n";
        $basePrompt .= "When users ask follow-up questions about server health or performance:\n";
        $basePrompt .= "• Provide specific, actionable troubleshooting steps\n";
        $basePrompt .= "• Include command-line tools and diagnostic procedures\n";
        $basePrompt .= "• Explain the reasoning behind recommendations\n";
        $basePrompt .= "• Offer preventive measures and monitoring suggestions\n";
        $basePrompt .= "• Cite specific log files and configuration options\n";
        $basePrompt .= "• Provide performance optimization techniques\n";
        $basePrompt .= "• Include industry best practices and standards\n\n";
        
        $basePrompt .= "BEHAVIOR RULES:\n";
        $basePrompt .= "1. ALWAYS prioritize accuracy and data integrity\n";
        $basePrompt .= "2. Use tools efficiently - group related operations when possible\n";
        $basePrompt .= "3. Validate all user inputs before execution\n";
        $basePrompt .= "4. Provide clear, formatted responses with relevant details\n";
        $basePrompt .= "5. If unsure about parameters, ask for clarification\n";
        $basePrompt .= "6. Escalate complex issues that exceed your tool capabilities\n";
        $basePrompt .= "7. Pay careful attention to the EXACT wording of user requests\n";
        $basePrompt .= "8. For documentation questions, provide complete API details without function calls\n";
        $basePrompt .= "9. For troubleshooting questions, provide comprehensive guidance with examples\n";
        $basePrompt .= "10. Always include preventive measures and monitoring recommendations\n";
        $basePrompt .= "11. CRITICAL: For follow-up questions about recently shown data, analyze existing data instead of re-requesting\n";
        $basePrompt .= "12. Recognize when users are asking for interpretation rather than new data\n\n";
        
        $basePrompt .= "CRITICAL PARAMETER RULES:\n";
        $basePrompt .= "• NEVER use placeholder, example, or dummy values (like 'exampleuser', 'newemail', 'securepassword123')\n";
        $basePrompt .= "• When a user requests a function that requires parameters, DO NOT call the function\n";
        $basePrompt .= "• Instead, respond with ONLY text asking for the specific parameters - DO NOT generate function calls\n";
        $basePrompt .= "• For list_forwarders: Ask 'Please provide the cPanel username and domain name'\n";
        $basePrompt .= "• For add_pop: Ask 'Please provide the cPanel username, email username, and password'\n";
        $basePrompt .= "• For client functions: Ask 'Please provide the client ID number'\n";
        $basePrompt .= "• Only call functions when you have ALL required parameters with real values\n\n";
        
        // Add context-aware instructions
        if (!empty($conversationContext['recent_topics'])) {
            $topics = implode(', ', $conversationContext['recent_topics']);
            $basePrompt .= "CURRENT CONTEXT: This conversation involves {$topics}. ";
            $basePrompt .= "Continue building on this context when providing assistance.\n\n";
        }
        
        if ($conversationContext['conversation_flow'] === 'ongoing_task') {
            $basePrompt .= "TASK STATUS: You are currently helping with an ongoing task. ";
            $basePrompt .= "Check if the user needs follow-up actions or has completed their objective.\n\n";
        }
        
        // Enhanced context for recent data
        if (!empty($conversationContext['recent_data'])) {
            $basePrompt .= "RECENT DATA CONTEXT: You have recently provided data to the user. ";
            $basePrompt .= "If they ask follow-up questions about this data (like 'is this normal?', 'what does this mean?'), ";
            $basePrompt .= "provide analysis and interpretation WITHOUT calling functions again. ";
            $basePrompt .= "Use your expert knowledge to explain what the data means.\n\n";
        }
        
        $basePrompt .= "RESPONSE FORMAT:\n";
        $basePrompt .= "• Use clear, professional language\n";
        $basePrompt .= "• Include relevant details and status updates\n";
        $basePrompt .= "• Suggest logical next steps when appropriate\n";
        $basePrompt .= "• Format data outputs for easy reading\n";
        $basePrompt .= "• For API documentation requests, provide comprehensive details in structured format\n";
        $basePrompt .= "• For troubleshooting requests, provide step-by-step guidance with explanations\n";
        $basePrompt .= "• Include relevant tools, commands, and configuration examples\n";
        $basePrompt .= "• For follow-up questions, reference specific values from previous responses\n";
        
        return $basePrompt;
    }

    private function getEnhancedToolsDefinition()
    {
        return [
            // Server Management Tools
            [
                "type" => "function",
                "function" => [
                    "name" => "listaccts",
                    "description" => "Retrieves a comprehensive list of all HOSTING ACCOUNTS on the server with detailed information including domains, users, packages, and resource usage. NOT for email forwarders.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => new \stdClass(),
                        "required" => []
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "get_domain_info",
                    "description" => "Returns detailed information about all domains configured on the server including subdomains and addon domains",
                    "parameters" => [
                        "type" => "object",
                        "properties" => new \stdClass(),
                        "required" => []
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "listpkgs",
                    "description" => "Lists all available hosting packages/plans with their features, limits, and pricing information",
                    "parameters" => [
                        "type" => "object",
                        "properties" => new \stdClass(),
                        "required" => []
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "createacct",
                    "description" => "Creates a new cPanel hosting account with specified domain, username, and contact email. Requires valid domain and unique username",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "username" => [
                            "type" => "string",
                                "description" => "Unique username for the cPanel account (3-16 characters, alphanumeric)"
                        ], 
                        "domain" => [
                            "type" => "string",
                                "description" => "Primary domain name for the account (must be valid domain format)"
                        ],
                        "contact_email" => [
                            "type" => "string",
                                "description" => "Valid email address for account notifications and password resets"
                        ]
                        ],
                        "required" => ["username", "domain", "contact_email"]
                    ]
                ]
            ],
            
            // Email Management Tools
            [
                "type" => "function",
                "function" => [
                    "name" => "list_pops",
                    "description" => "Lists all email accounts for a specific cPanel user with detailed information including quotas and usage statistics",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "cpanel_user" => [
                            "type" => "string",
                                "description" => "The cPanel username to query email accounts for"
                            ]
                        ],
                        "required" => ["cpanel_user"]
                    ]
                ]
            ],  
            [
                "type" => "function",
                "function" => [
                    "name" => "count_pops",
                    "description" => "Returns the total count of email accounts for a specific cPanel user",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "cpanel_user" => [
                            "type" => "string",
                                "description" => "The cPanel username to count email accounts for"
                        ]
                        ],
                        "required" => ["cpanel_user"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "add_pop",
                    "description" => "Creates a new email account for a cPanel user with specified username and secure password",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "cpanel_user" => [
                            "type" => "string",
                                "description" => "The cPanel username that will own this email account"
                        ],
                        "email_user" => [
                            "type" => "string",
                                "description" => "The username part of the email address (before @ symbol)"
                        ],
                        "password" => [
                            "type" => "string",
                                "description" => "Secure password for the email account (minimum 6 characters)"
                        ]
                        ],
                        "required" => ["cpanel_user", "email_user", "password"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "delete_pop",
                    "description" => "Removes an email account permanently. This action cannot be undone and will delete all emails in the account",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "cpanel_user" => [
                                "type" => "string",
                                "description" => "The cPanel username that owns the email account"
                            ],
                            "email" => [
                                "type" => "string",
                                "description" => "Complete email address to delete (user@domain.com format)"
                            ]
                        ],
                        "required" => ["cpanel_user", "email"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "list_forwarders",
                    "description" => "Lists all EMAIL FORWARDERS configured for a specific domain under a cPanel account. This is specifically for email forwarding rules, NOT hosting accounts.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "cpanel_user" => [
                            "type" => "string",
                                "description" => "The cPanel username to query forwarders for"
                        ],
                        "domain" => [
                            "type" => "string",
                                "description" => "Domain name to list forwarders for"
                        ]
                        ],
                        "required" => ["cpanel_user", "domain"]
                    ]
                ]
            ], 
            [
                "type" => "function",
                "function" => [
                    "name" => "add_forwarder",
                    "description" => "Creates an email forwarder to redirect emails from one address to another",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "cpanel_user" => [
                            "type" => "string",
                                "description" => "The cPanel username that owns the domain"
                        ],
                        "email" => [
                            "type" => "string",
                                "description" => "Source email address to forward from"
                        ],
                        "forward_to_email" => [
                            "type" => "string",
                                "description" => "Destination email address to forward to"
                        ]
                        ],
                        "required" => ["cpanel_user", "email", "forward_to_email"]
                    ]
                ]
            ],
            
            // Client Management Tools (WHMCS)
            [
                "type" => "function",
                "function" => [
                    "name" => "client",
                    "description" => "Retrieves comprehensive client information from WHMCS including contact details, billing info, and account status",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "client_id" => [
                            "type" => "string",
                                "description" => "Numeric client ID from WHMCS system"
                        ]
                        ],
                        "required" => ["client_id"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "invoices",
                    "description" => "Retrieves all invoices for a specific client including payment status, amounts, and due dates",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "client_id" => [
                            "type" => "string",
                                "description" => "Numeric client ID to retrieve invoices for"
                        ]
                        ],
                        "required" => ["client_id"]
                    ]
                ]
            ],
            
            // Communication Tools
            [
                "type" => "function",
                "function" => [
                    "name" => "agentChat",
                    "description" => "Send an internal message to the support agent with updates, questions, or status information",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "agent_message" => [
                            "type" => "string",
                                "description" => "Professional message to send to the internal support agent"
                        ]
                        ],
                        "required" => ["agent_message"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "ticketResponse",
                    "description" => "Generate a professional response to send directly to the customer",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "ticket_message" => [
                            "type" => "string",
                                "description" => "Professional, helpful response to send to the customer"
                            ]
                        ],
                        "required" => ["ticket_message"]
                    ]
                ]
            ],
            
            // Server Monitoring & Health Tools
            [
                "type" => "function",
                "function" => [
                    "name" => "get_server_load",
                    "description" => "Retrieves current server load averages (1, 5, 15 minute intervals) from the cPanel server to assess system performance",
                    "parameters" => [
                        "type" => "object",
                        "properties" => new \stdClass(),
                        "required" => []
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "get_server_status",
                    "description" => "Comprehensive server health check including load averages, cPanel version, account statistics, and bandwidth usage. Ideal for general server health assessment",
                    "parameters" => [
                        "type" => "object",
                        "properties" => new \stdClass(),
                        "required" => []
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "get_disk_usage",
                    "description" => "Analyzes disk usage across all hosting accounts with AI-powered health recommendations. Shows which accounts are using the most space",
                    "parameters" => [
                        "type" => "object",
                        "properties" => new \stdClass(),
                        "required" => []
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "get_server_services",
                    "description" => "Complete server service status including Apache, MySQL, FTP, DNS, mail services, system metrics (CPU, memory, swap), and disk partition information. Best for troubleshooting service issues",
                    "parameters" => [
                        "type" => "object",
                        "properties" => new \stdClass(),
                        "required" => []
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "showbw",
                    "description" => "Retrieves comprehensive bandwidth usage statistics for reseller accounts. Returns detailed information including monthly usage, account-specific data, and total consumption metrics",
                    "parameters" => [
                        "type" => "object",
                        "properties" => new \stdClass(),
                        "required" => []
                    ]
                ]
            ],
        ];
    }

    private function determineToolChoice($userMessage, $conversationContext)
    {
        // Smart tool choice based on message content and context
        $message = strtolower($userMessage);
        
        // If this is a follow-up question about recently shown data, don't force function calls
        if ($conversationContext['user_intent'] === 'follow_up_question' || 
            $conversationContext['conversation_flow'] === 'follow_up_analysis') {
            return "auto"; // Let AI provide analysis without function calls
        }
        
        // Enhanced follow-up question patterns - specifically look for data analysis requests
        $followUpPatterns = [
            // Direct data interpretation questions
            'tell me about.*data.*normal', 'tell me about.*data.*good', 'tell me about.*data',
            'is this normal', 'is that normal', 'is it normal', 'are these normal',
            'what does this mean', 'what does that mean', 'what do these mean',
            'should i be worried', 'should i worry', 'should i be concerned',
            'how can i fix', 'how do i fix', 'how to fix',
            'is the server healthy', 'is my server healthy', 'server health',
            'what\'s causing', 'what is causing', 'what causes',
            'how much.*left', 'how much.*remaining', 'how much space',
            'are.*down', 'is.*down', 'what.*down',
            'is.*good', 'is.*bad', 'is.*ok', 'is.*fine',
            'what about', 'explain this', 'explain that', 'explain these',
            'interpret', 'analysis', 'analyze', 'review',
            'tell me about', 'help me understand', 'what should i know',
            'why is', 'why are', 'why does', 'why do',
            'should i be concerned', 'is this concerning',
            // Performance related follow-ups  
            'is performance.*good', 'is performance.*bad', 'performance.*normal',
            'load.*normal', 'load.*high', 'load.*good', 'load.*bad',
            'usage.*normal', 'usage.*high', 'usage.*good', 'usage.*bad',
            'status.*good', 'status.*ok', 'status.*normal'
        ];
        
        foreach ($followUpPatterns as $pattern) {
            if (preg_match("/$pattern/i", $message)) {
                return "auto"; // Analysis response, not data request
            }
        }
        
        // If recently retrieved data and asking about interpretation, use auto
        if ($conversationContext['recent_data'] && 
            (preg_match('/normal|good|bad|ok|healthy|concerning|worried|mean|interpret|explain|analysis|about/i', $message))) {
            return "auto"; // Likely asking about existing data
        }
        
        // If it's a greeting or thanks, allow natural response
        $conversationalPhrases = ['hello', 'hi', 'thank', 'thanks', 'appreciate'];
        foreach ($conversationalPhrases as $phrase) {
            if (strpos($message, $phrase) !== false) {
                return "auto"; // Let AI decide
            }
        }
        
        // If asking for specific data but might need parameters, use auto
        $parameterRequiredPhrases = ['list email forwarders', 'create email', 'add email', 'client info', 'show client'];
        foreach ($parameterRequiredPhrases as $phrase) {
            if (strpos($message, $phrase) !== false) {
                return "auto"; // Let AI ask for parameters if needed
            }
        }
        
        // If asking for data that doesn't need parameters, require function calls
        $noParameterPhrases = ['list accounts', 'list hosting accounts', 'list packages', 'server load', 'check load', 'server status', 'disk usage', 'server services'];
        foreach ($noParameterPhrases as $phrase) {
            if (strpos($message, $phrase) !== false) {
                // But only if it's not a follow-up question
                if ($conversationContext['recent_data'] && 
                    (strpos($message, 'current') === false && 
                     strpos($message, 'latest') === false && 
                     strpos($message, 'updated') === false)) {
                    return "auto"; // Might be asking about existing data
                }
                return "required";
            }
        }
        
        // Default to auto for flexibility
        return "auto";
    }

    private function makeOpenAIRequest($data, $apiKey, $retries = 3)
    {
        $url = "https://api.openai.com/v1/chat/completions";
        $headers = [
            "Authorization: Bearer $apiKey",
            "Content-Type: application/json"
        ];

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
        curl_close($ch);

            if ($response && $httpCode === 200) {
        $responseData = json_decode($response, true);
                if ($responseData && isset($responseData['choices'])) {
                    return $responseData;
                }
            }

            // Log the attempt
            $this->errorHandler->logError('OPENAI_REQUEST_FAILED', 
                "Attempt {$attempt}: HTTP {$httpCode}, Error: {$error}");

            if ($attempt < $retries) {
                sleep(1); // Wait before retry
            }
        }

        return false;
    }

    private function executeFunction($functionName, $parameters)
    {
        $startTime = microtime(true);
        
        try {
            $result = null;
            
            switch ($functionName) {
                case 'get_domain_info':
                    $result = $this->cpanel->get_domain_info();
                    if ($result) {
                        $result = $this->responseFormatter->formatDomainInfo($result);
                    }
                    break;
                    
                case 'listaccts':
                    $result = $this->cpanel->listaccts();
                    if ($result && is_array($result)) {
                        // Validate the raw response before formatting
                        $validation = $this->validationHelper->validateApiResponse($result, $functionName);
                        if (!$validation['valid']) {
                            throw new \Exception($validation['error']);
                        }
                        
                        if (isset($result['data']['acct'])) {
                            $result = $this->responseFormatter->formatListAccounts($result);
                        }
                    }
                    break;
                    
                case 'listpkgs':
                    $result = $this->cpanel->listpkgs();
                    if ($result) {
                        $result = $this->responseFormatter->formatPackageList($result);
                    }
                    break;
                    
                case 'count_pops':
                    $result = $this->cpanel->count_pops($parameters['cpanel_user']);
                    if ($result && is_array($result)) {
                        // Validate the raw response before formatting
                        $validation = $this->validationHelper->validateApiResponse($result, $functionName);
                        if (!$validation['valid']) {
                            throw new \Exception($validation['error']);
                        }
                        
                        $result = $this->responseFormatter->formatEmailCount($result, $parameters['cpanel_user']);
                    }
                    break;
                    
                case 'createacct':
                    $result = $this->cpanel->createacct(
                        $parameters['username'],
                        $parameters['domain'], 
                        $parameters['contact_email']
                    );
                    break;
                    
                case 'delete_pop':
                    $result = $this->cpanel->delete_pop(
                        $parameters['cpanel_user'],
                        $parameters['email']
                    );
                    break;
                    
                case 'list_pops':
                    $result = $this->cpanel->list_pops($parameters['cpanel_user']);
                    if ($result) {
                        // Add cpanel_user to the result data for formatting
                        $result['cpanel_user'] = $parameters['cpanel_user'];
                        $result = $this->responseFormatter->formatEmailList($result);
                    }
                    break;
                    
                case 'add_pop':
                    $result = $this->cpanel->add_pop(
                        $parameters['cpanel_user'],
                        $parameters['email_user'],
                        $parameters['password']
                    );
                    if ($result) {
                        $result = $this->responseFormatter->formatEmailCreation(
                            $result, 
                            $parameters['cpanel_user'], 
                            $parameters['email_user']
                        );
                    }
                    break;
                    
                case 'list_forwarders':
                    $result = $this->cpanel->list_forwarders(
                        $parameters['cpanel_user'],
                        $parameters['domain']
                    );
                    if ($result) {
                        $result = $this->responseFormatter->formatEmailForwarders(
                            $result, 
                            $parameters['cpanel_user'], 
                            $parameters['domain']
                        );
                    }
                    break;
                    
                case 'add_forwarder':
                    $result = $this->cpanel->add_forwarder(
                        $parameters['cpanel_user'],
                        $parameters['email'],
                        $parameters['forward_to_email']
                    );
                    break;
                    
                case 'client':
                    if (!isset($parameters['client_id'])) {
                        throw new \Exception("Missing required parameter: client_id");
                    }
                    
                    if (!$this->whmcs) {
                        throw new \Exception("WHMCS connection not initialized");
                    }
                    
                    $result = $this->whmcs->client($parameters['client_id']);
                    
                    if ($result) {
                        if (isset($result['result']) && $result['result'] === 'error') {
                            throw new \Exception("WHMCS Error: " . ($result['message'] ?? 'Unknown error'));
                        }
                        
                        // Validate the raw response before formatting
                        $validation = $this->validationHelper->validateApiResponse($result, $functionName);
                        if (!$validation['valid']) {
                            throw new \Exception($validation['error']);
                        }
                        
                        try {
                            $formattedResult = $this->responseFormatter->formatClientInfo($result);
                            $result = $formattedResult;
                        } catch (\Exception $e) {
                            throw new \Exception("Failed to format client information: " . $e->getMessage());
                        }
                    } else {
                        throw new \Exception("No client data returned from WHMCS");
                    }
                    break;
                    
                case 'invoices':
                    $result = $this->whmcs->invoices($parameters['client_id']);
                    if ($result) {
                        // Add client_id to the result data for formatting
                        $result['client_id'] = $parameters['client_id'];
                        $result = $this->responseFormatter->formatInvoices($result);
                    }
                    break;
                    
                // Server Monitoring Functions
                case 'get_server_load':
                    $result = $this->getServerLoadDirect();
                    break;
                    
                case 'get_server_status':
                    $result = $this->getServerStatusDirect();
                    break;
                    
                case 'get_disk_usage':
                    $result = $this->getDiskUsageDirect();
                    break;
                    
                case 'get_server_services':
                    $result = $this->getServerServicesDirect();
                    break;
                    
                case 'showbw':
                    $result = $this->cpanel->showbw();
                    if ($result) {
                        $result = $this->responseFormatter->formatBandwidthUsage($result);
                    }
                    break;
                    
                default:
                    throw new \Exception("Unknown function: {$functionName}");
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Validate the result (client, listaccts, count_pops, and list_forwarders functions validate before formatting)
            if ($functionName !== 'client' && $functionName !== 'listaccts' && $functionName !== 'count_pops' && $functionName !== 'list_forwarders') {
                $validation = $this->validationHelper->validateApiResponse($result, $functionName);
                if (!$validation['valid']) {
                    throw new \Exception($validation['error']);
                }
            }

            return [
                'success' => true,
                'data' => $result,
                'execution_time' => $executionTime . 'ms'
            ];

        } catch (\Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            $this->errorHandler->handleApiError($functionName, $e->getMessage(), $parameters);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 500,
                'execution_time' => $executionTime . 'ms'
            ];
        }
    }

    private function processAIResponse($aiMessage, $requestData, $session, $userMessage)
    {
        $chatHistory = $this->contextManager->getChatHistory($session);
        
        // Handle different response types
        if (isset($aiMessage['tool_calls']) && !empty($aiMessage['tool_calls'])) {
            return $this->processToolCalls($aiMessage, $requestData, $session, $userMessage);
        } elseif (isset($aiMessage['content']) && !empty($aiMessage['content'])) {
            return $this->processTextResponse($aiMessage, $session, $userMessage);
        } else {
            return $this->respondWithError('Invalid AI response format', 500);
        }
    }

    private function processToolCalls($aiMessage, $requestData, $session, $userMessage)
    {
        $pendingFunctions = $requestData['pendingFunctions'] ?? [];
        $agentChatMessage = "";
        $assistantMessage = "I will execute the following functions: ";
        $functionNames = [];

        foreach ($aiMessage['tool_calls'] as $toolCall) {
            $func = $toolCall['function']['name'];
            $arguments = json_decode($toolCall['function']['arguments'], true);

            // Skip if already pending
            $alreadyPending = false;
            foreach ($pendingFunctions as $pending) {
                if ($pending['functionName'] === $func) {
                    $alreadyPending = true;
                        break;
                }
            }

            if (!$alreadyPending) {
                if ($func === 'agentChat') {
                    $agentChatMessage = "AI: " . $arguments['agent_message'];
                } elseif ($func === 'ticketResponse') {
                    $pendingFunctions[] = [
                        "description" => $arguments['ticket_message'],
                        "functionName" => "RECOMMENDED TICKET RESPONSE",
                        "confirmation" => "pending",
                        "parameters" => []
                    ];
        } else {
                    $description = $this->getFunctionDescription($func);
                    $pendingFunctions[] = [
                        "description" => $description,
                        "functionName" => $func,
                        "confirmation" => "pending", 
                        "parameters" => $arguments
                    ];
                    $functionNames[] = $func;
                }
            }
        }

        // Update chat history
        if (!empty($functionNames)) {
            $assistantMessage .= implode(', ', $functionNames);
            $chatHistory[] = ["role" => "assistant", "content" => $assistantMessage];
            $chatHistory[] = ["role" => "user", "content" => $userMessage];
            $session->set('history', $chatHistory);
        }

        return [
            "status" => "success",
            "pending_functions" => $pendingFunctions,
            "response" => $agentChatMessage,
            "API_response" => $requestData['API_response'] ?? []
        ];
    }

    private function processTextResponse($aiMessage, $session, $userMessage)
    {
        $chatHistory = $this->contextManager->getChatHistory($session);
        $chatHistory[] = ["role" => "user", "content" => $userMessage];
        $chatHistory[] = ["role" => "assistant", "content" => $aiMessage['content']];
        $session->set('history', $chatHistory);

        return [
            "status" => "success",
            "response" => $aiMessage['content'],
            "API_response" => []
        ];
    }

    private function getFunctionDescription($functionName)
    {
        $descriptions = [
            'get_domain_info' => 'Retrieve domain information from server',
            'listaccts' => 'List all hosting accounts on server',
            'listpkgs' => 'List available hosting packages',
            'count_pops' => 'Count email accounts for user',
            'createacct' => 'Create new hosting account',
            'delete_pop' => 'Delete email account',
            'list_pops' => 'List email accounts for user',
            'add_pop' => 'Create new email account',
            'list_forwarders' => 'List email forwarders for domain',
            'add_forwarder' => 'Create email forwarder',
            'client' => 'Retrieve client information',
            'invoices' => 'Retrieve client invoices',
            'showbw' => 'Retrieve bandwidth usage statistics for reseller accounts',
            'get_server_load' => 'Get current server load averages',
            'get_server_status' => 'Get comprehensive server health status',
            'get_disk_usage' => 'Analyze disk usage across hosting accounts',
            'get_server_services' => 'Check status of all server services'
        ];

        return $descriptions[$functionName] ?? "Execute {$functionName} function";
    }

    private function logInteraction($type, $input, $response, $sessionId)
    {
        $this->errorHandler->logInteraction($type, $input, $response, $sessionId);
    }

    private function respondWithValidationError($errors)
    {
        $this->errorHandler->handleValidationError($errors);
        return $this->fail([
            'error' => 'Validation failed',
            'details' => $errors
        ], 400);
    }

    private function respondWithError($message, $code = 500)
    {
        return $this->fail([
            'error' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ], $code);
    }

    private function generateTicketResponse($func, $parameters, $resultData, $session)
    {
        try {
            $chatHistory = $this->contextManager->getChatHistory($session);
            $conversationContext = $this->contextManager->analyzeConversationContext($chatHistory);
            
            $ticketPrompt = "Based on the successful execution of the {$func} function, generate a BRIEF, concise summary for the customer ticket. ";
            $ticketPrompt .= "Keep it under 2-3 sentences. Focus on what was accomplished, not technical details. ";
            $ticketPrompt .= "Function executed: {$func} with parameters: " . json_encode($parameters) . " ";
            $ticketPrompt .= "Result summary: " . $this->extractKeySummary($resultData) . " ";
            $ticketPrompt .= "Write a short, professional summary that a customer would understand.";

            $data = [
                "model" => "gpt-4o", 
                "messages" => array_merge([
                    ["role" => "system", "content" => $this->getEnhancedSystemPrompt($conversationContext)]
                ], [
                    ["role" => "user", "content" => $ticketPrompt]
                ]),
                "temperature" => 0.3,
                "top_p" => 0.9,
                "frequency_penalty" => 0.1,
                "presence_penalty" => 0.1,
                "max_tokens" => 1000,
                "tools" => [
                    [
                        "type" => "function",
                        "function" => [
                            "name" => "ticketResponse",
                            "description" => "Generate a professional response to send directly to the customer",
                            "parameters" => [
                                "type" => "object",
                                "properties" => [
                                    "ticket_message" => [
                                        "type" => "string",
                                        "description" => "Professional, helpful response to send to the customer"
                                    ]
                                ],
                                "required" => ["ticket_message"]
                            ]
                        ]
                    ]
                ],
                "tool_choice" => ["type" => "function", "function" => ["name" => "ticketResponse"]]
            ];

            $config = config('App');
            $responseData = $this->makeOpenAIRequest($data, $config->openai_key);
            
            if ($responseData && isset($responseData['choices'][0]['message']['tool_calls'][0])) {
                $toolCall = $responseData['choices'][0]['message']['tool_calls'][0];
                $arguments = json_decode($toolCall['function']['arguments'], true);
                
                return [
                    "description" => $arguments['ticket_message'],
                    "functionName" => "RECOMMENDED TICKET RESPONSE",
                    "confirmation" => "pending",
                    "parameters" => []
                ];
            }
            
            return null;
            
        } catch (Exception $e) {
            $this->errorHandler->logError('TICKET_RESPONSE_GENERATION', $e->getMessage());
            return null;
        }
    }

    private function extractKeySummary($data)
    {
        if (empty($data)) {
            return "Operation completed successfully";
        }
        
        // Handle string responses
        if (is_string($data)) {
            return strlen($data) > 100 ? substr($data, 0, 100) . "..." : $data;
        }
        
        // Handle arrays/objects
        if (is_array($data)) {
            $count = count($data);
            
            // Check for common data patterns
            if (isset($data['result']) && $data['result'] === 'success') {
                return "Action completed successfully";
            }
            
            if (isset($data['data']) && is_array($data['data'])) {
                $dataCount = count($data['data']);
                return "Retrieved {$dataCount} items";
            }
            
            if (isset($data['accounts']) || isset($data['acct'])) {
                return "Account information retrieved";
            }
            
            if (isset($data['email']) || isset($data['emails'])) {
                return "Email operation completed";
            }
            
            if (isset($data['domains'])) {
                return "Domain information retrieved";
            }
            
            if (isset($data['packages'])) {
                return "Package information retrieved";
            }
            
            // Generic count-based summary
            if ($count > 0) {
                return "Retrieved {$count} results";
            }
        }
        
        return "Operation completed successfully";
    }

    /**
     * Helper method to call server monitoring endpoints
     */
    private function callServerMonitoringEndpoint($endpoint)
    {
        try {
            // Directly instantiate ServerMonitorController to avoid HTTP loops
            $serverMonitor = new \App\Controllers\ServerMonitorController();
            
            switch ($endpoint) {
                case '/server/info':
                    $response = $serverMonitor->getServerInfo();
                    break;
                case '/server/status':
                    $response = $serverMonitor->getServerStatus();
                    break;
                case '/server/disk':
                    $response = $serverMonitor->getDiskUsage();
                    break;
                case '/server/services':
                    $response = $serverMonitor->getServerServices();
                    break;
                default:
                    throw new \Exception('Unknown monitoring endpoint: ' . $endpoint);
            }
            
            // Get the response body data
            $data = $response->getBody();
            if (is_string($data)) {
                $data = json_decode($data, true);
            }
            
            // Add AI analysis context to the response
            if (isset($data['data'])) {
                $data['ai_context'] = $this->generateServerAnalysisContext($endpoint, $data['data']);
            }
            
            return $data;
            
        } catch (\Exception $e) {
            // Return a simplified response instead of failing completely
            return [
                'status' => 'error',
                'message' => 'Server monitoring temporarily unavailable: ' . $e->getMessage(),
                'data' => null,
                'ai_context' => [
                    'analysis' => 'Server monitoring endpoint could not be reached',
                    'recommendation' => 'Please try again or check server status manually'
                ]
            ];
        }
    }

    /**
     * Generate AI analysis context for server monitoring data
     */
    private function generateServerAnalysisContext($endpoint, $data)
    {
        $context = [];
        
        switch ($endpoint) {
            case '/server/info':
                if (isset($data['data'])) {
                    $load = $data['data'];
                    $context['analysis'] = 'Server load metrics for performance assessment';
                    $context['health_indicators'] = [
                        '1-minute load' => $load['one'] ?? 'unknown',
                        '5-minute load' => $load['five'] ?? 'unknown', 
                        '15-minute load' => $load['fifteen'] ?? 'unknown'
                    ];
                }
                break;
                
            case '/server/status':
                $context['analysis'] = 'Comprehensive server health overview';
                $context['scope'] = 'Load averages, server version, account statistics, bandwidth usage';
                break;
                
            case '/server/disk':
                if (isset($data['summary'])) {
                    $summary = $data['summary'];
                    $context['analysis'] = 'Disk usage analysis with optimization recommendations';
                    $context['key_metrics'] = [
                        'total_accounts' => $data['total_accounts'] ?? 'unknown',
                        'total_usage_gb' => isset($summary['total_disk_used_mb']) ? round($summary['total_disk_used_mb'] / 1024, 2) : 'unknown'
                    ];
                }
                break;
                
            case '/server/services':
                $context['analysis'] = 'Complete service status and system metrics';
                $context['scope'] = 'All server services, CPU/memory usage, disk partitions';
                break;
        }
        
        return $context;
    }

    private function getServerLoadDirect()
    {
        try {
            // Get server load average using WHM API
            $serverInfo = $this->cpanel->whm_api_call('systemloadavg');
            
            if (!$serverInfo || isset($serverInfo['errors'])) {
                return [
                    'status' => 'error',
                    'message' => 'Unable to retrieve server load information',
                    'data' => null
                ];
            }
            
            return [
                'status' => 'success',
                'data' => $serverInfo,
                'timestamp' => date('Y-m-d H:i:s'),
                'source' => 'cPanel WHM API - systemloadavg',
                'ai_context' => [
                    'analysis' => 'Server load metrics for performance assessment',
                    'scope' => '1, 5, and 15 minute load averages'
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error retrieving server load: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    private function getServerStatusDirect()
    {
        try {
            $serverData = [];
            
            // 1. System Load Average (WHM API)
            $loadAvg = $this->cpanel->whm_api_call('systemloadavg');
            $serverData['load_average'] = $loadAvg;
            
            // 2. Server Version Info (WHM API)
            $serverInfo = $this->cpanel->whm_api_call('version');
            $serverData['server_version'] = $serverInfo;
            
            // 3. Account Statistics (WHM API) 
            $accounts = $this->cpanel->whm_api_call('listaccts');
            $serverData['accounts'] = $accounts;
            
            // 4. Bandwidth Statistics (WHM API)
            $stats = $this->cpanel->whm_api_call('showbw');
            $serverData['bandwidth_stats'] = $stats;
            
            return [
                'status' => 'success',
                'data' => $serverData,
                'timestamp' => date('Y-m-d H:i:s'),
                'source' => 'cPanel WHM API - Comprehensive Status',
                'ai_context' => [
                    'analysis' => 'Complete server health overview',
                    'scope' => 'Load averages, version, accounts, bandwidth usage'
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error retrieving server status: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    private function getDiskUsageDirect()
    {
        try {
            // Get account list which contains disk usage data
            $accounts = $this->cpanel->whm_api_call('listaccts');
            
            $diskSummary = [
                'total_accounts' => 0,
                'disk_usage_by_account' => [],
                'summary' => [
                    'total_disk_used_mb' => 0,
                    'accounts_with_limits' => 0,
                    'accounts_unlimited' => 0,
                    'heaviest_user' => null
                ]
            ];
            
            if (isset($accounts['data']['acct'])) {
                $diskSummary['total_accounts'] = count($accounts['data']['acct']);
                $maxUsage = 0;
                $heaviestUser = null;
                
                foreach ($accounts['data']['acct'] as $account) {
                    $diskUsed = $account['diskused'] ?? '0M';
                    $diskLimit = $account['disklimit'] ?? 'unlimited';
                    
                    // Convert disk usage to MB
                    $diskUsedMB = (float)str_replace('M', '', $diskUsed);
                    $diskSummary['summary']['total_disk_used_mb'] += $diskUsedMB;
                    
                    // Track heaviest user
                    if ($diskUsedMB > $maxUsage) {
                        $maxUsage = $diskUsedMB;
                        $heaviestUser = [
                            'user' => $account['user'],
                            'domain' => $account['domain'],
                            'disk_used' => $diskUsed,
                            'disk_limit' => $diskLimit
                        ];
                    }
                    
                    $diskSummary['disk_usage_by_account'][] = [
                        'user' => $account['user'],
                        'domain' => $account['domain'],
                        'disk_used' => $diskUsed,
                        'disk_limit' => $diskLimit
                    ];
                    
                    if ($diskLimit === 'unlimited') {
                        $diskSummary['summary']['accounts_unlimited']++;
                    } else {
                        $diskSummary['summary']['accounts_with_limits']++;
                    }
                }
                
                $diskSummary['summary']['heaviest_user'] = $heaviestUser;
            }
            
            return [
                'status' => 'success',
                'data' => $diskSummary,
                'timestamp' => date('Y-m-d H:i:s'),
                'source' => 'cPanel WHM API - Account Disk Usage',
                'ai_context' => [
                    'analysis' => 'Disk usage analysis with optimization recommendations',
                    'scope' => 'All hosting accounts disk usage analysis'
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error retrieving disk usage: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    private function getServerServicesDirect()
    {
        try {
            $results = [];
            
            // Try ServerInformation/get_information with specific users (as we discovered works)
            $users_to_try = ['root', 'dev477'];
            
            foreach ($users_to_try as $user) {
                try {
                    $serverInfo = $this->cpanel->cpanel_api_call($user, 'ServerInformation', 'get_information');
                    if ($serverInfo && !isset($serverInfo['error'])) {
                        $results["server_info_for_$user"] = $serverInfo;
                        break; // If successful, no need to try other users
                    } else {
                        $results["attempt_$user"] = $serverInfo;
                    }
                } catch (\Exception $e) {
                    $results["error_$user"] = $e->getMessage();
                }
            }
            
            // Also get individual service statuses
            $services = ['httpd', 'mysql', 'ftpd', 'exim', 'named'];
            foreach ($services as $service) {
                try {
                    $serviceStatus = $this->cpanel->whm_api_call('servicestatus', ['service' => $service]);
                    $results["{$service}_status"] = $serviceStatus;
                } catch (\Exception $e) {
                    $results["{$service}_error"] = $e->getMessage();
                }
            }
            
            return [
                'status' => 'success',
                'data' => $results,
                'timestamp' => date('Y-m-d H:i:s'),
                'source' => 'cPanel API - Server Services & System Info',
                'ai_context' => [
                    'analysis' => 'Complete service status and system metrics',
                    'scope' => 'All server services, system information, and health data'
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Error retrieving server services: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    public function debug_session()
    {
        $session = session();
        $recentApiData = $session->get('recent_api_data') ?? [];
        $history = $session->get('history') ?? [];
        
        return $this->respond([
            'recent_api_data' => $recentApiData,
            'history_count' => count($history),
            'session_id' => $session->session_id
        ]);
    }
}

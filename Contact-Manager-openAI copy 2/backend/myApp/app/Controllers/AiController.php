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
                
                return $this->respond([
                    "status" => "success",
                    "response" => $this->responseFormatter->formatSuccessMessage($func, $parameters),
                    "API_response" => [$result['data']],
                    "execution_time" => $result['execution_time'] ?? null
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
            
            // Build enhanced message history
            $historyString = $this->contextManager->buildEnhancedHistory($chatHistory, $userMessage, $conversationContext);

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
        $basePrompt .= "• Domain & Package Administration\n\n";
        
        $basePrompt .= "FUNCTION SELECTION RULES:\n";
        $basePrompt .= "• 'list email forwarders' = use list_forwarders function (requires cpanel_user and domain)\n";
        $basePrompt .= "• 'list accounts' or 'list hosting accounts' = use listaccts function (no parameters)\n";
        $basePrompt .= "• 'list emails' or 'list email accounts' = use list_pops function (requires cpanel_user)\n";
        $basePrompt .= "• 'count emails' = use count_pops function (requires cpanel_user)\n";
        $basePrompt .= "• 'create email' or 'add email' = use add_pop function (requires cpanel_user, email_user, password)\n";
        $basePrompt .= "• 'create account' = use createacct function (requires username, domain, contact_email)\n";
        $basePrompt .= "• 'client info' or 'show client' = use client function (requires client_id)\n\n";
        
        $basePrompt .= "BEHAVIOR RULES:\n";
        $basePrompt .= "1. ALWAYS prioritize accuracy and data integrity\n";
        $basePrompt .= "2. Use tools efficiently - group related operations when possible\n";
        $basePrompt .= "3. Validate all user inputs before execution\n";
        $basePrompt .= "4. Provide clear, formatted responses with relevant details\n";
        $basePrompt .= "5. If unsure about parameters, ask for clarification\n";
        $basePrompt .= "6. Escalate complex issues that exceed your tool capabilities\n";
        $basePrompt .= "7. Pay careful attention to the EXACT wording of user requests\n\n";
        
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
        
        $basePrompt .= "RESPONSE FORMAT:\n";
        $basePrompt .= "• Use clear, professional language\n";
        $basePrompt .= "• Include relevant details and status updates\n";
        $basePrompt .= "• Suggest logical next steps when appropriate\n";
        $basePrompt .= "• Format data outputs for easy reading\n";
        
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
            ]
        ];
    }

    private function determineToolChoice($userMessage, $conversationContext)
    {
        // Smart tool choice based on message content and context
        $message = strtolower($userMessage);
        
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
        $noParameterPhrases = ['list accounts', 'list hosting accounts', 'list packages'];
        foreach ($noParameterPhrases as $phrase) {
            if (strpos($message, $phrase) !== false) {
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
            'invoices' => 'Retrieve client invoices'
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
}

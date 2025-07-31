<?php
namespace App\Controllers;
require_once __DIR__ . '/../APILib.php';
require_once __DIR__ . '/../WHMCS.php';
require_once __DIR__ . '/../plesk.php';


use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;
use Cpanel\APILib as CpanelAPI;
use Plesk\APILib as PleskAPI;
use WHMCS;

class AiController extends BaseController

{
    use ResponseTrait;

    public function index()
    {
        //
    }

    public function proceed(){
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Credentials: true");
        $data = $this->request->getJSON(true);

        $func = $data[0]["functionName"];
        $id = $data[0]["id"];
        $format = "";

        $serverType = $data[0]["serverType"] ?? "";

        $arguments = $data[0]["parameters"];

        $session = session();
        $serverName = $arguments["serverName"] ?? $session->get('information')["Server"] ?? "";
        // $serverName = "janus13.easyonnet.io";
    
        $APIToken = "";

        if ($serverType === "cPanel"){
            $configPath = __DIR__ . '/../config/cPanel_server.json'; 
            $configData = json_decode(file_get_contents($configPath), true);
    
            foreach ($configData['servers'] as $server) {
                if ($server["server"] === $serverName){
                    $APIToken = $server["APIToken"];
                }
            }
    
            $cpanel = new CpanelAPI($serverName, 'root', $APIToken, '2087');
        }

        else if ($serverType === "Plesk"){
            $configPath = __DIR__ . '/../config/Plesk_server.json';  
            $configData = json_decode(file_get_contents($configPath), true);
    
            foreach ($configData['servers'] as $server) {
                if ($server["server"] === $serverName){
                    $APIToken = $server["APIToken"];
                }
            }
    
            $plesk = new PleskAPI($serverName, $APIToken);
        }


        $session = session();
        $chatHistory = $session->get('history') ?? [];

        $whmcs = new WHMCS('https://portal.easyonnet.io/includes/api.php',
        'm2ftQEEegLHpyLPD6fiUpAqA0mx9T1XL',
        'ooxF2HcGp1VjifSAB6bcHO6WfunujurY');

        $functionsResponse = [];
        $functionsCalled = [];

        // foreach ($aiMessage['tool_calls'] as $toolCall){
            // $func = $aiMessage['tool_calls'][0]['function']['name'];
            $functionsCalled[] = $func;

            if (isset($func)){
                $arguments = $data[0]["parameters"];

                if ($func === "get_domain_info"){
                    $APIresponse = $cpanel->get_domain_info();

                    $format = $this->formatGet_domain_info($APIresponse);
                    $chatHistory = $session->get('history') ?? [];
                    $chatHistory[] = [
                        "role" => "assistant",
                        "name" => "get_domain_info_call_response",
                        "content" => json_encode($format)
                    ];
                    $session->set('history', $chatHistory);
                }
                else if ($func === "listaccts"){
                    $domain = $arguments["domain"] ?? "";
                   
                    $APIresponse = $cpanel->listaccts(); 

                    $format = $this->formatListaccts($APIresponse);
                    $chatHistory = $session->get('history') ?? [];
                    $chatHistory[] = [
                        "role" => "assistant",
                        "name" => "listaccts_call_response",
                        "content" => json_encode($format)
                    ];
                    $session->set('history', $chatHistory);
                    
                }
                else if ($func === "listpkgs"){
                    $APIresponse = $cpanel->listpkgs(); 

                    $format = $this->formatListpkgs($APIresponse);
                    $chatHistory = $session->get('history') ?? [];
                    $chatHistory[] = [
                        "role" => "assistant",
                        "name" => "listpkgs_call_response",
                        "content" => json_encode($format)
                    ];
                    $session->set('history', $chatHistory);
                }
                else if ($func === "count_pops"){
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $cpanel->count_pops($arguments['cpanel_user']); 
                }
                else if ($func === "createacct"){
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $cpanel->createacct($arguments['username'], $arguments['domain'], $arguments['contact_email']); 
                }
                else if ($func === "delete_pop"){
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $cpanel->delete_pop($arguments['cpanel_user'], $arguments['email']); 
                }
                else if ($func === "list_pops"){
                    $functionsCalled[$func] = $arguments;
                    
                    $APIresponse = $cpanel->list_pops($arguments['cpanel_user']); 

                    $format = $this->formatList_pops($APIresponse);
                    $chatHistory = $session->get('history') ?? [];
                    $chatHistory[] = [
                        "role" => "assistant",
                        "name" => "list_pops_call_response",
                        "content" => json_encode($format)
                    ];
                    $session->set('history', $chatHistory);
                }
                else if ($func === "add_pop"){
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $cpanel->add_pop($arguments['cpanel_user'], $arguments['email_user'], $arguments['password']); 
                }
                else if ($func === "list_forwarders"){
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $cpanel->list_forwarders($arguments['cpanel_user'], $arguments['domain']); 
                }
                else if ($func === "add_forwarder"){
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $cpanel->add_forwarder($arguments['cpanel_user'], $arguments['email'], $arguments['forward_to_email']); 
                }
                else if ($func === "RECOMMENDED TICKET RESPONSE"){
                    $chatHistory[] =  ["role" => "user", "name" => "ticket_response_sent", "content" => "The recommended ticket response was sent to the customer: " . $data[0]["description"]];
                    $session->set('history', $chatHistory);
                    $APIresponse = '';
                
                }
                else if ($func === "getClientDetails"){
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $whmcs->getClientDetails($arguments['client_id']); 

                    $format = $this->formatClientDetails($APIresponse);
                    $chatHistory = $session->get('history') ?? [];
                    $chatHistory[] = [
                        "role" => "assistant",
                        "name" => "getClientDetails_call_response",
                        "content" => json_encode($format)
                    ];
                    $session->set('history', $chatHistory);
                }
                else if ($func === "getInvoices"){
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $whmcs->getInvoices($arguments['client_id']); 

                    $format = $this->formatGetInvoices($APIresponse);
                    $chatHistory = $session->get('history') ?? [];
                    $chatHistory[] = [
                        "role" => "assistant",
                        "name" => "getInvoices_call_response",
                        "content" => json_encode($format)
                    ];
                    $session->set('history', $chatHistory);
                }

                else if ($func === "getClientsProducts"){
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $whmcs->getClientsProducts($arguments['client_id']); 

                    $format = $this->formatGetClientsProducts($APIresponse);
                    $chatHistory = $session->get('history') ?? [];
                    $chatHistory[] = [
                        "role" => "assistant",
                        "name" => "getClientProducts_call_response",
                        "content" => json_encode($format)
                    ];
                    $session->set('history', $chatHistory);
                }

                else if ($func === "getProducts"){
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $whmcs->getProducts($arguments['pid'] ?? ""); 

                    $format = $this->formatGetProducts($APIresponse);
                    $chatHistory = $session->get('history') ?? [];
                    $chatHistory[] = [
                        "role" => "assistant",
                        "name" => "getProducts_call_response",
                        "content" => json_encode($format)
                    ];
                    $session->set('history', $chatHistory);
                }

                else if ($func === "systemloadavg"){
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $cpanel->systemloadavg(); 

                    $format = $this->formatSystemloadavg($APIresponse);
                    $chatHistory = $session->get('history') ?? [];
                    $chatHistory[] = [
                        "role" => "assistant",
                        "name" => "systemloadavg_call_response",
                        "content" => json_encode($format)
                    ];
                    $session->set('history', $chatHistory);
                }

                else if ($func === "get_disk_usage"){
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $cpanel->get_disk_usage(); 

                    $format = $this->formatGet_disk_usage($APIresponse);
                    $chatHistory = $session->get('history') ?? [];
                    $chatHistory[] = [
                        "role" => "assistant",
                        "name" => "get_disk_usage_call_response",
                        "content" => json_encode($format)
                    ];
                    $session->set('history', $chatHistory);
                }

                else if ($func === "get_information"){
                    $functionsCalled[$func] = $arguments;
                    $APIresponse = $cpanel->get_information($arguments['cpanel_user']); 

                    $format = $this->formatGet_information($APIresponse);
                    $chatHistory = $session->get('history') ?? [];
                    $chatHistory[] = [
                        "role" => "assistant",
                        "name" => "get_information_call_response",
                        "content" => json_encode($format)
                    ];
                    $session->set('history', $chatHistory);
                }

                else if ($func === "get_usages"){
                    $functionsCalled[$func] = $arguments;
                    $APIresponse = $cpanel->get_usages($arguments['cpanel_user']); 

                    $format = $this->formatGet_usages($APIresponse);
                    $chatHistory = $session->get('history') ?? [];
                    $chatHistory[] = [
                        "role" => "assistant",
                        "name" => "get_usages_call_response",
                        "content" => json_encode($format)
                    ];
                    $session->set('history', $chatHistory);
                }

                else if ($func === "showbw"){
                    $functionsCalled[$func] = $arguments;
                    $APIresponse = $cpanel->showbw($arguments['cpanel_user']);

                    $format = $this->formatShowbw($APIresponse);
                    $chatHistory = $session->get('history') ?? [];
                    $chatHistory[] = [
                        "role" => "assistant",
                        "name" => "showbw_call_response",
                        "content" => json_encode($format)
                    ];
                    $session->set('history', $chatHistory);
                }

                else if ($func === "get_client_settings"){
                    $functionsCalled[$func] = $arguments;
                    
                    $APIresponse = $cpanel->get_client_settings($arguments['cpanel_user']); 

                    $format = $this->formatGet_client_settings($APIresponse);
                    $chatHistory = $session->get('history') ?? [];
                    $chatHistory[] = [
                        "role" => "assistant",
                        "name" => "get_client_settings_call_response",
                        "content" => json_encode($format)
                    ];
                    $session->set('history', $chatHistory);
                }

                //Plesk functionss
                else if ($func === "plesk_clients"){

                    $APIresponse = $plesk->clients(); 

                    $format = $this->formatPlesk_clients($APIresponse);
                    $chatHistory = $session->get('history') ?? [];
                    $chatHistory[] = [
                        "role" => "assistant",
                        "name" => "Plesk_clients_call_response",
                        "content" => json_encode($format)
                    ];
                    $session->set('history', $chatHistory);
                }

                else if ($func === "plesk_domains"){

                    $APIresponse = $plesk->domains(); 

                    $format = $this->formatPlesk_domains($APIresponse);
                    $chatHistory = $session->get('history') ?? [];
                    $chatHistory[] = [
                        "role" => "assistant",
                        "name" => "Plesk_domains_call_response",
                        "content" => json_encode($format)
                    ];
                    $session->set('history', $chatHistory);
                }
            }
    
            else {
                return $this->respond("alert");
            }
            $functionsResponse[] = $APIresponse;

        // Log actions in json
        $this->json_log($functionsCalled, $session->session_id);
        $session->remove('response');
        $parameters = $data[0]["parameters"];

        if (!empty($parameters)){
            $returnMessage = "The " . $func . " function with these parameters: ";

            foreach ($parameters as $p){
                $returnMessage .= $p . ", ";
            }
        }

        else {
            $returnMessage = "The " . $func . " function ";
        }

        $returnMessage .= "request was completed successfully.";

        // return $this->respond($format);
        if ($format === ""){
            $format = $functionsResponse;
        }

        if (isset($responseData['choices'][0]['message']['content'])) {

            // $chatHistory[] = ["role" => "user", "content" => $userMessage]; // use this for no simplification
            // $chatHistory[] = ["role" => "user", "content" => $matches[1]];
            // $chatHistory[] = ["role" => "assistant", "content" => $matches[2]];
            $chatHistory[] =  ["role" => "assistant", "content" => $responseData['choices'][0]['message']['content']];
            // $chatHistory[] =  ["role" => "user", "name" => "ticket_complete", "content" => $returnMessage];
            $session->set('history', $chatHistory);

            return $this->respond([
                "status" => "success",
                "response" => $responseData['choices'][0]['message']['content'], //*** 
                "tokens_used" => $responseData['usage'] ?? null,
                "API_response" => $format,
                "user_message" => $returnMessage,
                "function"   => $func
            ]);
        }

        else {
            // $chatHistory[] =  ["role" => "user", "name" => "ticket_complete", "content" => $returnMessage];
            // $session->set('history', $chatHistory);

            return $this->respond([
                "status" => "success",
                "response" => "No Response was Generated", //*** 
                "tokens_used" => $responseData['usage'] ?? null,
                "API_response" => $format,
                "user_message" => $returnMessage,
                "function"   => $func
            ]);
        }
        // return $this->fail("Failed to get a response from OpenAI.", 500);

    }

    public function rejected(){
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Credentials: true");
        $session = session();

        return $this->respond(["user_message" => "The agent denied the use of those tools"]);
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
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Credentials: true");

        
        // header("Access-Control-Allow-Origin: *");
        // header("Access-Control-Allow-Credentials: true");
        
        $session = session();
        
        $open_ai_key = getenv('app.openai_key');
        $json = $this->request->getJSON(true);
        $userMessage = $json['message'] ?? ''; // Default message

        // if ($json['historyAdd'] != ""){
        //     $chatHistory = $session->get('history') ?? [];
        //     $chatHistory[] = ["role" => "assistant", "name" => "agent_chat", "content" =>  "AI: This ticket was sent by a 'Guest' requestor. Please ensure this is a valid client before proceeding and submit their client ID to me."];
        //     $session->set('history', $chatHistory);
        // }
      
        $category = '';
        $satisfaction = '';
        $rating = '';

        
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

                            Web Hosting: Issues related to web hosting services, 
                            including server downtime, hosting plan upgrades, site deployment, 
                            storage limits, bandwidth overuse, FTP access, or server configuration 
                            (e.g., PHP settings, file permissions). 

                            Email Account Help: Problems with email services, 
                            such as sending or receiving emails, login issues, 
                            email setup in clients (Outlook, Gmail, Apple Mail), 
                            spam filtering, and password resets.

                            Billing and Account Help: Questions about payments, 
                            invoices, subscriptions, plan changes, refunds, 
                            billing errors, and account access or security.

                            DNS Problems: Issues with domain name settings, 
                            including DNS propagation, configuring A/CNAME/MX/TXT records, 
                            nameserver updates, and domain pointing.

                            Website Problems: Technical problems with the website itself—broken pages, 
                            error messages (e.g., 404, 500), slow loading, content display issues, 
                            and CMS problems (e.g., WordPress errors).

                            General or Unknown Issue: Fallback classification."
                        ],
                        "satisfaction" => [
                            "type" => "string",
                            "description" => "The evalutation you as the AI assistant give of the customer's 
                            satisfaction from the options:
                            neutral, dissatisfied, satisfied."
                        ],
                        "rating" => [
                            "type" => "string",
                            "description" => "The rating (a number from 1-5 where it can also be a decimal)
                            you as the AI assistant would give for the customer's satisfaction. Note that 1 is
                            really unsatisfied, where the customer is threatening to eave the company and 5 is when the customer
                            is very happy with the service."
                        ],
                        ],
                        "required" => ["summary", "category", "satisfaction", "rating"]
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
                    from the following categories:  
                            Web Hosting: Issues related to web hosting services, 
                            including server downtime, hosting plan upgrades, site deployment, 
                            storage limits, bandwidth overuse, FTP access, or server configuration 
                            (e.g., PHP settings, file permissions). 

                            Email Account Help: Problems with email services, 
                            such as sending or receiving emails, login issues, 
                            email setup in clients (Outlook, Gmail, Apple Mail), 
                            spam filtering, and password resets.

                            Billing and Account Help: Questions about payments, 
                            invoices, subscriptions, plan changes, refunds, 
                            billing errors, and account access or security.

                            DNS Problems: Issues with domain name settings, 
                            including DNS propagation, configuring A/CNAME/MX/TXT records, 
                            nameserver updates, and domain pointing.

                            Website Problems: Technical problems with the website itself—broken pages, 
                            error messages (e.g., 404, 500), slow loading, content display issues, 
                            and CMS problems (e.g., WordPress errors).

                            General or Unknown Issue: Fallback classification. 
                    to classify the user's message."
                ]
            , ["role" => "user", "content" => $userMessage]]),
            "temperature" => 0.7,
            "tools" => $toolsSum,
            "tool_choice" => "required"
        ];

        if (str_contains($userMessage, 'TICKET_TEST')){
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
            $satisfaction = $arguments["satisfaction"];
            $rating = $arguments["rating"];
        }
         

        $chatHistory = $session->get('history') ?? [];
        $historyString = [];

        
        foreach ($chatHistory as $entry) {
            if ($entry['role'] != 'tool'){
                $historyString[] = ["role" => $entry['role'], "name" => $entry['name'], "content" => $entry['content']];
            }
            else {
                $historyString[] = ["role" => $entry['role'], "content" => $entry['content']];
            }
            
        }
        
        if (str_contains($userMessage, 'AGENT')){
            $historyString[] = ["role" => "user", "name" => "agent_chat", "content" => $userMessage];
            $chatHistory[] = ["role" => "user", "name" => "agent_chat", "content" => $userMessage];
        }
        else{
            $historyString[] = ["role" => "user", "name" => "ticket_handling", "content" => $userMessage];
            $chatHistory[] = ["role" => "user", "name" => "ticket_handling", "content" => $userMessage];
        }

        $tools = [
            [
                "type" => "function",
                "function" => [
                    "name" => "get_domain_info",
                    "description" => "Return a list of all domains on a cPanel server.",
                    "parameters" => [
                        "type" => "object",
                        "properties" =>  [ 
                            "serverName" => [
                                "type" => "string",
                                "description" => "The name of the cPanel server you want to check"
                            ]
                            ],
                        "required" => ["serverName"] 
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "listaccts",
                    "description" => "Returns a list of accounts on the cPanel server",
                    "parameters" => [
                        "type" => "object",
                        "properties" =>  [ 
                            "serverName" => [
                                "type" => "string",
                                "description" => "The name of the cPanel server you want to check"
                            ],
                            "domain" => [
                                "type" => "string",
                                "description" => "The domain name to filter cPanel 
                                accounts by. This can be a full domain (e.g., 'example.com') 
                                or a partial string or regular expression. If specified, only accounts 
                                matching this domain will be included in the response. If provided with
                                domain information, you must use this parameter."
                            ]
                            ],
                        "required" => ["serverName"] 
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "listpkgs",
                    "description" => "Returns a list of all hosting packages configured 
                    on the cPanel server, including resource limits (e.g., disk quota), and features.",
                    "parameters" => [
                        "type" => "object",
                        "properties" =>  [ 
                            "serverName" => [
                                "type" => "string",
                                "description" => "The name of the cPanel server you want to check"
                            ]
                            ],
                        "required" => ["serverName"] 
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "count_pops",
                    "description" => "Returns a count of all email addresses on the cPanel account",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "cpanel_user" => [
                            "type" => "string",
                            "description" => "The username of the cPanel account to check that you MUST retrieve using the listaccts tool."
                        ],
                        "serverName" => [
                            "type" => "string",
                            "description" => "The name of the cPanel server you want to check"
                        ]
                        ],
                        "required" => ["cpanel_user", "serverName"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "createacct",
                    "description" => "Creates a new CPanel Account on the server",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "username" => [
                            "type" => "string",
                            "description" => "The username of the new cPanel account"
                        ], 
                        "domain" => [
                            "type" => "string",
                            "description" => "The domain of the new cPanel account"
                        ],
                        "contact_email" => [
                            "type" => "string",
                            "description" => "The email account of the new cPanel account"
                        ],
                        "serverName" => [
                            "type" => "string",
                            "description" => "The name of the cPanel server you want to check"
                        ]
                        ],
                        "required" => ["username", "domain", "contact_email", "serverName"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "delete_pop",
                    "description" => "Removes an Email Address on cPanel",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "cpanel_user" => [
                            "type" => "string",
                            "description" => "The username of the cPanel account to check that you MUST retrieve using the listaccts tool."
                        ], 
                        "email" => [
                            "type" => "string",
                            "description" => "The email account for the user"
                        ],
                        "serverName" => [
                            "type" => "string",
                            "description" => "The name of the cPanel server you want to check"
                        ]
                        ],
                        "required" => ["cpanel_user", "email", "serverName"]
                    ]
                ]
            ],  
            [
                "type" => "function",
                "function" => [
                    "name" => "list_pops",
                    "description" => "Retrieves a list of all email accounts under the specified cPanel user.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "cpanel_user" => [
                            "type" => "string",
                            "description" => "The username of the cPanel account to check that you MUST retrieve the using the listaccts tool."
                        ],
                        "serverName" => [
                            "type" => "string",
                            "description" => "The name of the cPanel server you want to check"
                        ]
                        ],
                        "required" => ["cpanel_user", "serverName"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "add_pop",
                    "description" => "Adds a new Email Address to the cPanel Account",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "cpanel_user" => [
                            "type" => "string",
                            "description" => "The username of the cPanel account to check that you MUST retrieve using the listaccts tool."
                        ],
                        "email_user" => [
                            "type" => "string",
                            "description" => "The first part of an email account before the '@' sign"
                        ],
                        "password" => [
                            "type" => "string",
                            "description" => "The password for the new email address"
                        ],
                        "serverName" => [
                            "type" => "string",
                            "description" => "The name of the cPanel server you want to check"
                        ]
                        ],
                        "required" => ["cpanel_user", "email_user", "password", "serverName"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "list_forwarders",
                    "description" => "Lists all forwarders on the cPanel server for the provided domain name",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "cpanel_user" => [
                            "type" => "string",
                            "description" => "The username of the cPanel account to check that you MUST retrieve using listaccts tool."
                        ],
                        "domain" => [
                            "type" => "string",
                            "description" => "The domain of the cPanel account"
                        ],
                        "serverName" => [
                            "type" => "string",
                            "description" => "The name of the cPanel server you want to check"
                        ]
                        ],
                        "required" => ["cpanel_user", "domain", "serverName"]
                    ]
                ]
            ], 
            [
                "type" => "function",
                "function" => [
                    "name" => "add_forwarder",
                    "description" => "Adds an Email Forwarder for the cPanel account",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "cpanel_user" => [
                            "type" => "string",
                            "description" => "The username of the cPanel account to check that you MUST retrieve using listaccts tool."
                        ],
                        "email" => [
                            "type" => "string",
                            "description" => "The email that is being forwarded from"
                        ],
                        "forward_to_email" => [
                            "type" => "string",
                            "description" => "The email that is being forwarded to"
                        ],
                        "serverName" => [
                            "type" => "string",
                            "description" => "The name of the cPanel server you want to check"
                        ]
                        ],
                        "required" => ["cpanel_user", "email", "forward_to_email", "serverName"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "systemloadavg",
                    "description" => "Retrieves the cPanel system's current load average 
                    values for the past 1, 5, and 15 minutes. Useful for monitoring 
                    system resource utilization.",
                    "parameters" => [
                        "type" => "object",
                        "properties" =>  [ 
                            "serverName" => [
                                "type" => "string",
                                "description" => "The name of the cPanel server you want to check"
                            ]
                            ],
                        "required" => ["serverName"] 
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "get_disk_usage",
                    "description" => "Returns disk usage statistics for all user 
                    accounts on the cPanel server. Includes total disk usage per account 
                    and inode (file object) usage if available.",
                    "parameters" => [
                        "type" => "object",
                        "properties" =>  [ 
                            "serverName" => [
                                "type" => "string",
                                "description" => "The name of the cPanel server you want to check"
                            ]
                            ],
                        "required" => ["serverName"] 
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "get_information",
                    "description" => "Returns the status of each cPanel service (daemon), device, and 
                    server health check point on your server.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "cpanel_user" => [
                            "type" => "string",
                            "description" => "The username of the cPanel account to check that you MUST retrieve using listaccts tool."
                        ],
                        "serverName" => [
                            "type" => "string",
                            "description" => "The name of the cPanel server you want to check"
                        ]
                        ],
                        "required" => ["cpanel_user", "serverName"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "get_usages",
                    "description" => "Retrieves resource usage and custom statistics for a cPanel user account. Including
                    Resource Description, Maximum Usage, and Current Usage.",
                    "parameters" => [
                        "type" => "object",
                        "properties" =>  [ 
                            "serverName" => [
                                "type" => "string",
                                "description" => "The name of the cPanel server you want to check"
                            ],
                            "cpanel_user" => [
                                "type" => "string",
                                "description" => "The username of the cPanel account to check that you MUST retrieve using listaccts tool."
                            ]
                            ],
                        "required" => ["cpanel_user", "serverName"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "showbw",
                    "description" => "Retrieves cPanel account bandwidth information.",
                    "parameters" => [
                        "type" => "object",
                        "properties" =>  [ 
                            "serverName" => [
                                "type" => "string",
                                "description" => "The name of the cPanel server you want to check"
                            ],
                            "cpanel_user" => [
                                "type" => "string",
                                "description" => "The username of the cPanel account to check that you MUST retrieve using listaccts tool."
                            ]
                            ],
                        "required" => ["cpanel_user", "serverName"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "get_client_settings",
                    "description" => "Retrieves a cPanel email account's client settings including
                    domain, smtp port, inbox port, and inbox service (imap or pop).",
                    "parameters" => [
                        "type" => "object",
                        "properties" =>  [ 
                            "serverName" => [
                                "type" => "string",
                                "description" => "The name of the cPanel server you want to check"
                            ],
                            "cpanel_user" => [
                                "type" => "string",
                                "description" => "The username of the cPanel account to check that you MUST retrieve using listaccts tool."
                            ]
                            ],
                        "required" => ["cpanel_user", "serverName"]
                    ]
                ]
            ],

            //**** Plesk functions */
            [
                "type" => "function",
                "function" => [
                    "name" => "plesk_clients",
                    "description" => "Retrieves all plesk clients on a specific Plesk server including
                    email, Plesk client ID, and status.",
                    "parameters" => [
                        "type" => "object",
                        "properties" =>  [ 
                            "serverName" => [
                                "type" => "string",
                                "description" => "The name of the Plesk server you want to check"
                            ]
                            ],
                        "required" => ["serverName"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "plesk_domains",
                    "description" => "Retrieves all plesk domains on a specific Plesk server including the domain ID.",
                    "parameters" => [
                        "type" => "object",
                        "properties" =>  [ 
                            "serverName" => [
                                "type" => "string",
                                "description" => "The name of the Plesk server you want to check"
                            ]
                            ],
                        "required" => ["serverName"]
                    ]
                ]
            ],

            //**** WHMCS functions */
            [
                "type" => "function",
                "function" => [
                    "name" => "getClientDetails",
                    "description" => "This function retrieves detailed information for a specific WHMCS client.
                    When used, it provides client information, including: 
                    status: Client’s account status (Active, Inactive, or Closed)
                    domain: Primary domain associated with the client (if set)
                    lastlogin: Timestamp of the client's most recent login
                    numtickets: Total number of support tickets the client has submitted
                    numactivetickets: Number of currently open or awaiting-reply tickets
                    This function is used to get deeper insight into a single client's status, activity, and support engagement.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "client_id" => [
                            "type" => "string",
                            "description" => "The ID of the requested client"
                        ]
                        ],
                        "required" => ["client_id"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "getInvoices",
                    "description" => "Returns invoices for a specifc client",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "client_id" => [
                            "type" => "string",
                            "description" => "The ID of the requested client"
                        ]
                        ],
                        "required" => ["client_id"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "getClientsProducts",
                    "description" => "This function retrieves hosting product/service information for a specific WHMCS client. The data includes details about each product the client owns:
                    pid: The unique ID for the specific product
                    domain: The domain name associated with the product (if set)
                    servername: Name of the server the product is hosted on
                    serverid: Unique ID of the assigned server
                    serverip: IP address of the assigned server
                    recurringamount: The recurring price charged for the product
                    billingcycle: The product's billing cycle (e.g., Monthly, Annually)
                    status: Status of the product (e.g., Active, Suspended, Terminated)
                    diskusage: Current disk usage for the product (in MB/GB depending on setup)
                    disklimit: Disk quota/limit for the product
                    This function is used to inspect a client’s services and hosting usage, 
                    including server assignments and billing info",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "client_id" => [
                            "type" => "string",
                            "description" => "The ID of the requested client"
                        ]
                        ],
                        "required" => ["client_id"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "getProducts",
                    "description" => "This function retrieves information about all the 
                    products configured in WHMCS. Each product entry includes:
                    module: The server module associated with the product 
                    (e.g., cpanel, plesk, etc.)
                    pricing: Pricing details, which may include amounts by 
                    currency and billing cycle
                    This function is useful for mapping a client's product to its 
                    technical backend (module type) and understanding the available 
                    pricing structure.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "pid" => [
                            "type" => "string",
                            "description" => "Optionally, the ID of the specific requested product"
                        ]
                        ],
                        "required" => []
                    ]
                ]
            ],
            //**** General AI Flow */
            [
                "type" => "function",
                "function" => [
                    "name" => "ticketResponse",
                    "description" => "Returns a generated ticket response to the client",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "ticket_message" => [
                            "type" => "string",
                            "description" => "The full proposed message to send to the client generated by you, as an AI assistant."
                        ]
                        ],
                        "required" => ["ticket_message"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "agentChat",
                    "description" => "Returns a generated response to an internal employee",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "agent_message" => [
                            "type" => "string",
                            "description" => "The full proposed message to send to the employee generated by you, as an AI assistant"
                        ]
                        ],
                        "required" => ["agent_message"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "beyondCapabilities",
                    "description" => "Notifies an employee when the ticket request is beyond the tools given",
                    "parameters" => [
                        "type" => "object",
                        "properties" => new \stdClass(), // No parameters
                        "required" => []
                    ]
                ]
            ]


        ];
        
        
        // $url = "https://api.openai.com/v1/chat/completions";
        // $headers = [
        //     "Authorization: Bearer $open_ai_key",
        //     "Content-Type: application/json"
        // ];


        $data = [
            //"model" => "gpt-4-turbo",
            "model" => "gpt-4o",
            "messages" => array_merge([
                ["role" => "system", "content" =>  "You are a helpful Technical support employee.
You will receive customer support requests via 'tickets' and your job is to resolve
them using the tools provided.
You always communicate directly with a knowledgeable internal employee (role: user)
who will monitor your actions, help redirect you
when needed and give you special information you don't have access to.
It is important to keep the internal employee updated on what you are doing by calling
the 'agentChat' tool.


  - You are communicating with an internal employee however, you may recieve
  prompts that are formated as 'TICKET: 'content'' indicating that is the customer's
  request that you must resolve
  - A prompt with 'AGENT : 'content'' indicates the internal employee's response
  to what you are doing. 

You must always follow this strict behavior when responding to user requests:
1. If a tool will provide you the AI any other necessary info to complete the request,
make a tool call and tell the agent what you are dong via the 'agentChat' tool.

2. If the user request approximately matches the general capabilities of a tool,
all required parameters are provided, and the request has not yet been completed in
the near past:
  - You must always include a call to the 'agentChat' tool
  as your first tool call to explain what
  you are about to do.
  - You must then include the actual tool call(s) in the same response, immediately
  after the 'agentChat' tool entry.
  - These must all be included in the same list of tool_calls, with 'agentChat' tool
   listed first and other tools listed following it.
  - Do not separate the 'agentChat' tool and the actual tool call into
  different responses.
  -Do not delay tool execution after 'agentChat' — both must be called together.


3. If the request matches a tool, but any required parameter is missing:
  - You must always think step by step, so if you can retrieve the parameter by making another function
  call first, you must do so and NEVER guess a parameter
  - If the user is the only way to get a parameter use the 'ticketResponse' tool to politely ask the user for the missing information.
  - Do not call any other tools or proceed until all required parameters are confirmed.


4. If the ticket message does not map to any available tool:
   - Note that if the 'AGENT' is inquiring information, you can respond with your broad knowledge using the agentChat
   tool
  - Only respond naturally and politely by calling 'ticketResponse' to:
    - Greetings
    - Thanks
    - Clarifying questions about tool-related messages
  - Do **not** answer questions or provide help unrelated to your toolset
  - If the message is outside your tool capabilities, respond by calling the
  'beyondCapabilities' tool to escalate to a human immediately.


5. If there is no user input or no new user message
  - Evaluate the chat history and determine if the user's request was met meaning the
  user has confirmed the tool was completed
  - Call the 'ticketResponse' tool to send a clear, friendly summary to the customer
  about what was done.
  - Also call the agentChat tool to send a brief internal summary of what you did to the
  internal agent. You must NEVER include the actual data from any API call in this summary.

A few notes on function parameters:
   - To get the cPanel username, you must first call the listaccts function
   - You will recieve information about corresponding servers, you must pay attention to the module to
   understand the type of server. Any tool that includes information about one server type is specific
   and unique to that kind of server and must NEVER be called with another server type.

You must not deviate from this logic under any circumstance."


                ]
            ], $historyString),
            "temperature" => 0.7,
            "tools" => $tools,
            "tool_choice" => "required"
        ];
        

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1); // 👈 Force HTTP/1.1
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);

        
        curl_close($ch);

        $responseData = json_decode($response, true);

        $whmcs = new WHMCS('https://portal.easyonnet.io/includes/api.php',
        'm2ftQEEegLHpyLPD6fiUpAqA0mx9T1XL',
        'ooxF2HcGp1VjifSAB6bcHO6WfunujurY');

        $functionsResponse = [];
        $functionsCalled = [];

        // return $this->respond($responseData);

        if (!isset($responseData['choices'][0]['message'])){
            return $this->respond($responseData);
            return $this->respond("Can't handle request");
        }

        $aiMessage = $responseData['choices'][0]['message']; 

        if (!isset($responseData['choices'][0]['message']['tool_calls'][0]['function']['name'])){
            if (isset($responseData['choices'][0]['message']['content'])) {
                if ($responseData['choices'][0]['message']['content'] === 'alert'){
                    return $this->respond("alert");
                }

                $chatHistory[] =  ["role" => "assistant", "content" => $responseData['choices'][0]['message']['content']];
                $session->set('history', $chatHistory);
    
                return $this->respond([
                    "status" => "success",
                    "response" => $responseData['choices'][0]['message']['content'], //*** 
                    "tokens_used" => $responseData['usage'] ?? null,
                    "API_response" => 'No call was made'
                ]);
            }
            return $this->respond($responseData);
        }

        $chatHistory = $session->get('history') ?? [];
        if (str_contains($userMessage, 'AGENT')){
            $chatHistory[] = ["role" => "user", "name" => "agent_chat", "content" => $userMessage];
        }
        else{
            $chatHistory[] = ["role" => "user", "name" => "ticket_handling", "content" => $userMessage];
        }

        $session->set('history', $chatHistory);

        $apiResponse = $json['API_response'] ?? '';
        $pending_functions = $json['pendingFunctions'] ?? [];

        if ($aiMessage['tool_calls'][0]['function']['name'] === "beyondCapabilities"){
            return $this->respond("alert");
        }

        $agentChatMessage = "";
        $alreadyPending = false;
        $assistantMessage = "I will call the following functions to complete your request: ";
        $assistantMessagefunc = "";

        foreach ($aiMessage['tool_calls'] as $toolCall){
            $func = $toolCall['function']['name'];

            $id = $toolCall['id'];

            if ($func != "agentChat" && $func != "ticketResponse"){
                $assistantMessagefunc .= $func . ", ";
            }
            

            $rawArguments = $toolCall['function']['arguments'];
            $arguments = json_decode($rawArguments, true);

            $alreadyPending = false;

            foreach ($pending_functions as $pending) {
                if ($pending['functionName'] === $func) {
                    $alreadyPending = true;
                    break;
                }
            }
 
            if (!$alreadyPending){
                $tag = "";
                if ($func === 'agentChat'){
                    $description = $arguments['agent_message'];
                    $agentChatMessage = "AI: ". $description;

                    $chatHistory = $session->get('history') ?? [];
                    $chatHistory[] = ["id" => '', "role" => "assistant", "name" => "agent_chat", "content" => $agentChatMessage];
                    $session->set('history', $chatHistory);
                }

                else if ($toolCall['function']['name'] === 'ticketResponse'){
                    $description = $arguments['ticket_message'];
                    $pending_functions[] = ["id" => '', "description" => $description, "functionName" => "RECOMMENDED TICKET RESPONSE", "confirmation" => "pending", "parameters" => ''];
                }

                else {
                    $data = $this->description($func, $arguments);
                    $description = $data['description'];
                    $tag = $data['tag'];
                    $serverType = $data['serverType'] ?? "";
                    $pending_functions[] = ["id" => $id, "description" => $description, "functionName" => $func, "confirmation" => "pending", "parameters" => $arguments, "tag" => $tag, "serverType" => $serverType];
                }
        
            }
        }

        if ($assistantMessagefunc != ""){
            $assistantMessage .= $assistantMessagefunc;

            $chatHistory = $session->get('history') ?? [];
            $chatHistory[] = ["role" => "assistant", "name" => "ticket_handling", "content" => $assistantMessage];
    
            $session->set('history', $chatHistory);

        }

        return $this->respond(["pending_functions" => $pending_functions, "status" => "success",
        "response" => $agentChatMessage,
        "tokens_used" => $responseData['usage'] ?? null,
        "API_response" => $apiResponse, "category" => $category,
        "satisfaction" => $satisfaction,
        "rating" => $rating]);
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
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Credentials: true");
        $session = session();
        $session->remove('history');
        $session->remove('response');
        $session->remove('information');
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

    public function description($func, $arguments){
        $server = "Server: " . ($arguments["serverName"] ?? "");
        $user = "cPanel User: " . ($arguments["cpanel_user"] ?? "");

        //cPanel Functions
        if ($func === "get_domain_info"){
            return ["description" => "Return a list of all domains on a server. $server",
                    "tag" => "Information Request", "serverType" => "cPanel"];
        }
        else if ($func === "listaccts"){
            return ["description" => "Returns a list of accounts on the server. $server",
                    "tag" => "Information Request", "serverType" => "cPanel"];
        }
        else if ($func === "listpkgs"){
            return ["description" => "Lists available Packages/Plans on the server. $server",
                    "tag" => "Information Request", "serverType" => "cPanel"];
        }
        else if ($func === "count_pops"){
            return ["description" => "Returns a count of all email addresses on the account. $server, $user",
                    "tag" => "Information Request", "serverType" => "cPanel"];
        }
        else if ($func === "createacct"){
            return ["description" => "Creates a new CPanel Account on the server. $server",
                    "tag" => "Changes Will be Made", "serverType" => "cPanel"];
        }
        else if ($func === "delete_pop"){
            return ["description" => "Removes an Email Address. $server",
                    "tag" => "Changes Will be Made", "serverType" => "cPanel"];
        }
        else if ($func === "list_pops"){
            return ["description" => "Returns a list of all email addresses on the account. $server, $user",
                    "tag" => "Information Request", "serverType" => "cPanel"];
        }
        else if ($func === "add_pop"){
            return ["description" => "Adds a new Email Address to the Account. $server, $user",
                    "tag" => "Changes Will be Made", "serverType" => "cPanel"];
        }
        else if ($func === "list_forwarders"){
            return ["description" => "Lists all forwarders on the server for the provided domain name. $server",
                    "tag" => "Information Request", "serverType" => "cPanel"];
        }
        else if ($func === "add_forwarder"){
            return ["description" => "Adds an Email Forwarder. $server",
                    "tag" => "Changes Will be Made", "serverType" => "cPanel"];
        }
        else if ($func === "getClientDetails"){
            return ["description" => "Returns info about a specified client.",
                    "tag" => "Information Request", "serverType" => "cPanel"];
        }
        else if ($func === "getInvoices"){
            return ["description" => "Returns invoices for a specifc client.",
                    "tag" => "Information Request", "serverType" => "cPanel"];
        }
        else if ($func === "getClientsProducts"){
            return ["description" => "Returns info about a client's products.",
                    "tag" => "Information Request", "serverType" => "cPanel"];
        }
        else if ($func === "getProducts"){
            return ["description" => "Returns info a specifc requested product.",
                    "tag" => "Information Request", "serverType" => "cPanel"];
        }
        else if ($func === "systemloadavg"){
            return ["description" => "Retrieves the system's load average. $server",
                    "tag" => "Information Request", "serverType" => "cPanel"];
        }
        else if ($func === "get_disk_usage"){
            return ["description" => "Return all cPanel accounts disk usage. $server",
                    "tag" => "Information Request", "serverType" => "cPanel"];
        }
        else if ($func === "get_information"){
            return ["description" => "Returns service and device status. $server, $user",
                    "tag" => "Information Request", "serverType" => "cPanel"];
        }
        else if ($func === "get_usages"){
            return ["description" => "Returns resource usage for a specific cPanel account. $server, $user",
                    "tag" => "Information Request", "serverType" => "cPanel"];
        }
        else if ($func === "showbw"){
            return ["description" => "Retrieves account bandwidth information. $server, $user",
                    "tag" => "Information Request", "serverType" => "cPanel"];
        }
        else if ($func === "get_client_settings"){
            return ["description" => "retrieves an email account's client settings. $server, $user",
                    "tag" => "Information Request", "serverType" => "cPanel"];
        }

        // Plesk Functions
        else if ($func === "plesk_clients"){
            return ["description" => "retrieves all clients on a plesk server. $server",
                    "tag" => "Information Request", "serverType" => "Plesk"];
        }

        else if ($func === "plesk_domains"){
            return ["description" => "retrieves all domains on a plesk server. $server",
                    "tag" => "Information Request", "serverType" => "Plesk"];
        }
    }

    // cPanel formatting
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

        $whmcs = new WHMCS('https://portal.easyonnet.io/includes/api.php',
        'm2ftQEEegLHpyLPD6fiUpAqA0mx9T1XL',
        'ooxF2HcGp1VjifSAB6bcHO6WfunujurY');
    
        // Support both direct array or array of objects
        foreach ($data['products']['product'] as $product) {
            $productInfo = $whmcs->getProducts($product['pid']);
            
            $formattedProducts[] = [
                'Product ID'    => $product['pid'],
                'Product Name'  => $product['name'],
                'Domain'        => $product['domain'],
                'Server ID'      => $product['serverid'],
                'Server Name'  => $product['servername'],
                'Server IP'        => $product['serverip'] ?? "",
                'Module'        => $productInfo['products']['product'][0]['module'],
                'Disk Usage'      => $product['diskusage'],
                'Disk Limit'      => $product['disklimit'],
            ];
        }
    
        return $formattedProducts;
    }

    public function formatGetProducts($data) {
        $formattedProducts = [];
        $products = $data['products']['product'];

        foreach ($products as $product){
            $formattedProducts[] = [
                'Module'    => $product['module'],
                'Product Name'    => $product['name'],
                'Monthly Pricing CAD' => $product['pricing']['CAD']['monthly']
            ];
        }

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
                'Email'    => $email['email'] ?? "",
                'Email for the email account is suspended'    => $email['suspended_incoming'] ?? "",
                'Logins for the email account are suspended'    => $email['suspended_login'] ?? ""
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

    public function formatGetInvoices($data) {
        $formattedInvoices = [];
        $invoices = $data['invoices']['invoice'];

        foreach ($invoices as $invoice) {
            $formattedInvoices[] = [
                'Due Date'    => $invoice['duedate'] ?? "",
                'Date Paid'    => $invoice['datepaid'] ?? "",
                'Total'    => $invoice['total'] ?? "",
                'Payment Method'    => $invoice['paymentmethod'] ?? "",
                'Status'    => $invoice['status'] ?? ""
            ];
        }

        return $formattedInvoices;
    }
    
    public function formatGet_usages($data) {
        $formattedUsages = [];
        $usages = $data['result']['data'];

        foreach ($usages as $usage) {
            $formattedUsages[] = [
                'Resource Description'    => $usage['description'] ?? "",
                'Maximum Usage'    => $usage['maximum'] ?? "",
                'Usage'    => $usage['usage'] ?? ""
            ];
        }

        return $formattedUsages;
    }

    public function formatShowbw($data) {
        $formattedBandwidth = [];
        $bandwidth = $data['data']["acct"];

        foreach ($bandwidth as $bw) {
            $formattedBandwidth[] = [
                'cPanel User'    => $bw['user'] ?? "",
                'BandWidth Usage in Bytes'    => $bw['bwusage'][0]['usage'] ?? "",
                'Domain'    => $bw['bwusage'][0]['domain'] ?? "",
                'Bandwidth Limit'    => $bw['limit'] ?? ""
            ];
        }

        return $formattedBandwidth;
    }

    public function formatGet_client_settings($data){
        $formattedSettings = [];
        $setting = $data['result']["data"];

        $formattedSettings[] = [
            'Domain'    => $setting['domain'] ?? "",
            'Inbox Service'    => $setting['inbox_service']?? "",
            'Inbox Port'    => $setting["inbox_port"] ?? "",
            'Inbox Host'    => $setting['inbox_host'] ?? "",
            'smtp Host'    => $setting['smtp_host'] ?? "",
            'smtp Port'    => $setting['smtp_port'] ?? ""
        ];
        
        return $formattedSettings; 
    }
    
    //Plesk formatting
    public function formatPlesk_clients($data) {
        $formattedClients = [];
        $clients = $data['data'];
    
        foreach ($clients as $client) {
            $formattedClients[] = [
                'Name'     => $client['name'] ?? "",
                'Client ID'   => $client['id'] ?? "",
                'Email'      => $client['email'] ?? "",
                'Client Type'     => $client['type'] ?? "",
                'Status'     => $client['status'] ?? ""
            ];
        }

        return $formattedClients;
    }

    public function formatPlesk_domains($data) {
        $formattedDomains = [];
        $domains = $data['data'];
    
        foreach ($domains as $domain) {
            $formattedDomains[] = [
                'Domain'     => $domain['name'] ?? "",
                'Domain ID'   => $domain['id'] ?? "",
                'Hosting Type'      => $domain['hosting_type'] ?? ""
            ];
        }

        return $formattedDomains;
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
        $clientID = $APIresponse['userid'];
        $requestor = $APIresponse['requestor_type'];

        $userMessage = "TICKET: " . $APIresponse["replies"]["reply"][0]["message"];
        $priority = $APIresponse['priority'];

        
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
            $category = $arguments["category"];


            if ($requestor === "Guest"){

                return $this->respond([
                    "summary" => $userMessage,
                    "category" => $category,
                    "priority" => $priority,
                    "response" => "AI: This ticket was sent by a 'Guest' requestor. Please ensure this is a valid client before proceeding and submit their client ID to me."
                ]);
            }
            
            $session = session();
            $chatHistory = $session->get('history') ?? [];
            $chatHistory[] = ["role" => "user", "name" => "agent_chat", "content" =>  "Client ID: " . $clientID];
            $session->set('history', $chatHistory);

            //Add all the servers and domains to history
            $APIresponse = $whmcs->getClientsProducts($clientID); 
    
            $format = $this->formatGetClientsProducts($APIresponse);

            $chatHistory = $session->get('history') ?? [];
            $chatHistory[] = [
                "role" => "assistant",
                "name" => "getClientProducts_call_response",
                "content" => json_encode($format)
                ];
            $session->set('history', $chatHistory);

            return $this->respond([
                "summary" => $userMessage,
                "category" => $category,
                "priority" => $priority
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

            $whmcs = new WHMCS('https://portal.easyonnet.io/includes/api.php',
            'm2ftQEEegLHpyLPD6fiUpAqA0mx9T1XL',
            'ooxF2HcGp1VjifSAB6bcHO6WfunujurY');

            $session = session();
            $chatHistory = $session->get('history') ?? [];
            $chatHistory[] = ["role" => "user", "name" => "agent_chat", "content" =>  "Client ID: " . $clientID];
            $session->set('history', $chatHistory);

            //Add all the servers and domains to history
            $APIresponse = $whmcs->getClientsProducts($clientID); 

            $format = $this->formatGetClientsProducts($APIresponse);
            $chatHistory = $session->get('history') ?? [];
            $chatHistory[] = [
                "role" => "assistant",
                "name" => "getClientProducts_call_response",
                "content" => json_encode($format)
                ];
            $session->set('history', $chatHistory);
        }

        if ($product != ''){
            $chatHistory[] = ["role" => "user", "name" => "product_info", "content" =>  "Product: " . $product];
        }
        
        if ($server != ''){
            $chatHistory[] = ["role" => "user", "name" => "server_info", "content" =>  "Server: " . $server];
        }
        $session->set('history', $chatHistory);

        $session = session();

        $chatHistory = [];
        
        if (!empty($clientID)) {
            $chatHistory['Client ID'] = $clientID;
        }
        
        if (!empty($product)) {
            $chatHistory['Product'] = $product;
        }
        
        if (!empty($server)) {
            $chatHistory['Server'] = $server;
        }
        
        $session->set('information', $chatHistory);

    }
    
}
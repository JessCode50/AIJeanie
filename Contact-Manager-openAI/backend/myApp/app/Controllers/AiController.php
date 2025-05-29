<?php
namespace App\Controllers;
require_once __DIR__ . '/../APILib.php';
require_once __DIR__ . '/../WHMCS.php';


use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;
use Cpanel\APILib;
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
        // return $this->respond($data);
        $func = $data[0]["functionName"];
        $id = $data[0]["id"];

        $session = session();
        $chatHistory = $session->get('history') ?? [];
        // $responseData = $session->get('response') ?? [];

        $cpanel = new APILib('janus13.easyonnet.io', 'root','GGWKWFGGCBIGJZ9RJV8Z7H47TM8YA1EG', '2087');
        $whmcs = new WHMCS('https://portal.easyonnet.io/includes/api.php',
        'm2ftQEEegLHpyLPD6fiUpAqA0mx9T1XL',
        'ooxF2HcGp1VjifSAB6bcHO6WfunujurY');
        // $aiMessage = $responseData['choices'][0]['message']; 
        // return $this->respond($aiMessage);

        $functionsResponse = [];
        $functionsCalled = [];

        // foreach ($aiMessage['tool_calls'] as $toolCall){
            // $func = $aiMessage['tool_calls'][0]['function']['name'];
            $functionsCalled[] = $func;

            if (isset($func)){
                $arguments = $data[0]["parameters"];
                // $arguments = json_decode($rawParams, true); 

                if ($func === "get_domain_info"){
                    $APIresponse = $cpanel->get_domain_info();
                }
                else if ($func === "listaccts"){
                    $APIresponse = $cpanel->listaccts(); 
                    $temp = $this->formatResponse($APIresponse);

                    $chatHistory = $session->get('history') ?? [];
                    $chatHistory[] = [
                        "role" => "assistant",
                        "name" => "ticket_handling",
                        "content" => json_encode($temp)
                    ];
                    $session->set('history', $chatHistory);
                    
                }
                else if ($func === "listpkgs"){
                    $APIresponse = $cpanel->listpkgs(); 
                }
                else if ($func === "count_pops"){
                    // $rawArguments = $toolCall['function']['arguments'];
                    // $arguments = json_decode($rawArguments, true);
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $cpanel->count_pops($arguments['cpanel_user']); 
                }
                else if ($func === "createacct"){
                    // $rawArguments = $toolCall['function']['arguments'];
                    // $arguments = json_decode($rawArguments, true);
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $cpanel->createacct($arguments['username'], $arguments['domain'], $arguments['contact_email']); 
                }
                else if ($func === "delete_pop"){
                    // $rawArguments = $toolCall['function']['arguments'];
                    // $arguments = json_decode($rawArguments, true);
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $cpanel->delete_pop($arguments['cpanel_user'], $arguments['email']); 
                }
                else if ($func === "list_pops"){
                    // $rawArguments = $toolCall['function']['arguments'];
                    // $arguments = json_decode($rawArguments, true);
                    $functionsCalled[$func] = $arguments;
                    
                    $APIresponse = $cpanel->list_pops($arguments['cpanel_user']); 
                }
                else if ($func === "add_pop"){
                    // $rawArguments = $toolCall['function']['arguments'];
                    // $arguments = json_decode($rawArguments, true);
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $cpanel->add_pop($arguments['cpanel_user'], $arguments['email_user'], $arguments['password']); 
                }
                else if ($func === "list_forwarders"){
                    // $rawArguments = $toolCall['function']['arguments'];
                    // $arguments = json_decode($rawArguments, true);
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $cpanel->list_forwarders($arguments['cpanel_user'], $arguments['domain']); 
                }
                else if ($func === "add_forwarder"){
                    // $rawArguments = $toolCall['function']['arguments'];
                    // $arguments = json_decode($rawArguments, true);
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $cpanel->add_forwarder($arguments['cpanel_user'], $arguments['email'], $arguments['forward_to_email']); 
                }
                else if ($func === "RECOMMENDED TICKET RESPONSE"){
                    $chatHistory[] =  ["role" => "assistant", "name" => "agent_chat", "content" => "The recommended ticket response was accepted by the user: " . $data[0]["description"]];
                    $session->set('history', $chatHistory);
                    $APIresponse = '';
                
                }
                else if ($func === "client"){
                    // $rawArguments = $toolCall['function']['arguments'];
                    // $arguments = json_decode($rawArguments, true);
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $whmcs->client($arguments['client_id']); 
                }
                else if ($func === "invoices"){
                    $functionsCalled[$func] = $arguments;

                    $APIresponse = $whmcs->invoices($arguments['client_id']); 
                }

            }
    
            else {
                return $this->respond("alert");
            }
            $functionsResponse[] = $APIresponse;
        //}
        // $func = $responseData['choices'][0]['message']['tool_calls'][0]['function']['name'];

        // return $this->respond($functionsCalled); /////////!!!!!!!!!!!!


        // return $this->respond($APIresponse);
        // $functionsCalledStr = "";

        // foreach ($functionsCalled as $f) {
        //     $functionsCalledStr .= $f . ", ";
        // }
        // $functionsCalledStr = rtrim($functionsCalledStr, ", ");
        
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

        if (isset($responseData['choices'][0]['message']['content'])) {
            //preg_match('/Summary:\s*(.*?)\s*Response:\s*(.*)/is', $responseData['choices'][0]['message']['content'], $matches);

            // $chatHistory[] = ["role" => "user", "content" => $userMessage]; // use this for no simplification
            // $chatHistory[] = ["role" => "user", "content" => $matches[1]];
            // $chatHistory[] = ["role" => "assistant", "content" => $matches[2]];
            $chatHistory[] =  ["role" => "assistant", "content" => $responseData['choices'][0]['message']['content']];
            $chatHistory[] =  ["role" => "user", "name" => "ticket_complete", "content" => $returnMessage];
            $session->set('history', $chatHistory);

            return $this->respond([
                "status" => "success",
                "response" => $responseData['choices'][0]['message']['content'], //*** 
                "tokens_used" => $responseData['usage'] ?? null,
                "API_response" => $functionsResponse
            ]);
        }

        else {
            $chatHistory[] =  ["role" => "user", "name" => "ticket_complete", "content" => $returnMessage];
            $session->set('history', $chatHistory);

            return $this->respond([
                "status" => "success",
                "response" => "No Response was Generated", //*** 
                "tokens_used" => $responseData['usage'] ?? null,
                "API_response" => $functionsResponse
            ]);
        }
        // return $this->fail("Failed to get a response from OpenAI.", 500);

    }

    public function rejected(){
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Credentials: true");
        $session = session();

        $chatHistory = $session->get('history') ?? [];

        $chatHistory[] = ["role" => "user", "name" => "agent_chat", "content" => "User denied the use of those tools"];
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
        header("Access-Control-Allow-Origin: http://localhost:5173");
        header("Access-Control-Allow-Credentials: true");
        // header("Access-Control-Allow-Origin: *");
        // header("Access-Control-Allow-Credentials: true");
        
        $session = session();
        
        $open_ai_key = getenv('app.openai_key');
        $json = $this->request->getJSON(true);
        $userMessage = $json['message'] ?? ''; // Default message

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

        
        // var_dump($historyString);
    
        // $chatHistoryString = implode("\n", $chatHistory);
        // $chatHistoryString = $chatHistoryString . "\n" . $userMessage;
        $tools = [
            [
                "type" => "function",
                "function" => [
                    "name" => "get_domain_info",
                    "description" => "Return a list of all domains on a server.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => new \stdClass(), // No parameters
                        "required" => []
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "listaccts",
                    "description" => "Returns a list of accounts on the server",
                    "parameters" => [
                        "type" => "object",
                        "properties" => new \stdClass(), // No parameters
                        "required" => []
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "listpkgs",
                    "description" => "Lists available Packages/Plans on the server",
                    "parameters" => [
                        "type" => "object",
                        "properties" => new \stdClass(), // No parameters
                        "required" => []
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "count_pops",
                    "description" => "Returns a count of all email addresses on the account",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "cpanel_user" => [
                            "type" => "string",
                            "description" => "The username of the cPanel account to check."
                        ]
                        ],
                        "required" => ["cpanel_user"]
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
                        ]
                        ],
                        "required" => ["username", "domain", "contact_email"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "delete_pop",
                    "description" => "Removes an Email Address",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "cpanel_user" => [
                            "type" => "string",
                            "description" => "The username of the cPanel account to check."
                        ], 
                        "email" => [
                            "type" => "string",
                            "description" => "The email account for the user"
                        ]
                        ],
                        "required" => ["cpanel_user", "email"]
                    ]
                ]
            ],  
            [
                "type" => "function",
                "function" => [
                    "name" => "list_pops",
                    "description" => "Returns a list of all email addresses on the account",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "cpanel_user" => [
                            "type" => "string",
                            "description" => "The username of the cPanel account to check."
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
                    "description" => "Adds a new Email Address to the Account",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "cpanel_user" => [
                            "type" => "string",
                            "description" => "The username of the cPanel account to check."
                        ],
                        "email_user" => [
                            "type" => "string",
                            "description" => "The first part of an email account before the '@' sign"
                        ],
                        "password" => [
                            "type" => "string",
                            "description" => "The password for the new email address"
                        ]
                        ],
                        "required" => ["cpanel_user", "email_user", "password"]
                    ]
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "list_forwarders",
                    "description" => "Lists all forwarders on the server for the provided domain name",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "cpanel_user" => [
                            "type" => "string",
                            "description" => "The username of the cPanel account to check."
                        ],
                        "domain" => [
                            "type" => "string",
                            "description" => "The domain of the new cPanel account"
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
                    "description" => "Adds an Email Forwarder",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                        "cpanel_user" => [
                            "type" => "string",
                            "description" => "The username of the cPanel account to check."
                        ],
                        "email" => [
                            "type" => "string",
                            "description" => "The email that is being forwarded from"
                        ],
                        "forward_to_email" => [
                            "type" => "string",
                            "description" => "The email that is being forwarded to"
                        ]
                        ],
                        "required" => ["cpanel_user", "email", "forward_to_email"]
                    ]
                ]
            ],
            //**** WHMCS functions */
            [
                "type" => "function",
                "function" => [
                    "name" => "client",
                    "description" => "Returns info about a specifc client",
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
                    "name" => "invoices",
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
                            "description" => "The full proposed message to send to the client generated by you, as an AI assistant"
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
            ]


        ];
        
        
        
        $url = "https://api.openai.com/v1/chat/completions";
        $headers = [
            "Authorization: Bearer $open_ai_key",
            "Content-Type: application/json"
        ];


        $data = [
            //"model" => "gpt-4-turbo",
            //"You are a helpful AI assistant that always responds using this format, \nSummary: <use a very short, cryptic way to track the core idea of the user input in a compressed form for only AI to use internally meant to take minimal tokens while being specific enough for memory reference. No full sentences.\nResponse: <your helpful response to the user>
           //"You are a helpful AI assistant.  First, MUST ALWAYS make a helpful response to the user explaining what you are doing unless if the user asks for something that doesn't align at all with any tools don't respond.  Then, make a tools call but if you don't have the enoguh parameters, in that case ask the user for clarification.
            "model" => "gpt-4o",
            "messages" => array_merge([
                ["role" => "system", "content" => "You are a helpful Technical Support AI.

                You will receive customer support requests formatted as 'TICKET: ...'. Your job is to resolve these using the available tools.
                
                You also communicate with an internal support employee (messages formatted as 'AGENT: ...'). This person may provide additional context, answer your questions, or guide your actions. You must keep this internal agent informed of what you're doing and ask for help if needed.
                
                ROLES
                
                - TICKET: A message from the customer. You respond to these only using the 'ticketResponse' tool.
                - AGENT: The internal support employee. You communicate with them only using the 'agentChat' tool.
                
                TOOL CALL RULES

                When a tool has been successfully executed and confirmed by the user, the user message contains a confirmation such as 'The [TOOL_NAME] function request was completed successfully.', consider the tool execution complete or when there is no new customer input:
- Do not only describe in 'agentChat' what you did, you must call 'ticketResponse in the same response to inform the customer. Describing what you did via 'agentChat' is not enough — you must notify the customer immediately.
- Do not call that tool again, instead if you are still waiting fo a tool execution, make a call to 'agentChat'
- Use 'ticketResponse' as your first tool call to send the customer a clear, friendly summary of what was done.
- Also use 'agentChat' to send a brief internal update to the agent.
- Both tools must be called in the same response, with 'agentChat' listed first.
                
                When the TICKET matches a tool and all required parameters are present:
                - You must call 'agentChat' and the relevant tool(s) in the same response. Describing your intent is not enough — you must take the action immediately.
                - Use 'agentChat' as your first tool call to explain what you are about to do and why.
                - Immediately after, use the relevant tool(s) with the appropriate parameters.
                - Both tool calls must be included in the same response, with 'agentChat' listed first.
                - If the 'agentChat' is not called, you are not following instructions.
                - If other tools are not called, you are not following instructions.

                
                Do not ask the agent for permission to proceed. Simply inform them.
                
                When information is missing:
                - Use the 'ticketResponse' tool to politely ask the customer for the missing details.
                - Do not call any other tools until this information is received.
                
                When no tool can address the TICKET:
                - Use 'agentChat' to inform the agent that the issue cannot be resolved automatically and may need their intervention.
                - Do not attempt to respond directly to the customer.
                
              
                BEHAVIOR RULES
                - Do not answer unrelated customer questions. Escalate them to the agent.
                - Do not invent information or act without justification.
                - Always follow these rules strictly.
                
                Do not describe future actions without executing them. If a tool should be called, call it immediately. All explanations must be accompanied by actual tool calls."

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

        // $func = $responseData['choices'][0]['message'];
        // return $this->respond($responseData);

        $cpanel = new APILib('janus13.easyonnet.io', 'root','GGWKWFGGCBIGJZ9RJV8Z7H47TM8YA1EG', '2087');
        $whmcs = new WHMCS('https://portal.easyonnet.io/includes/api.php',
        'm2ftQEEegLHpyLPD6fiUpAqA0mx9T1XL',
        'ooxF2HcGp1VjifSAB6bcHO6WfunujurY');

        $functionsResponse = [];
        $functionsCalled = [];

        if (!isset($responseData['choices'][0]['message'])){
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
            // return $this->respond("alert");
        }

        // $session->set('response', $responseData);

        $chatHistory = $session->get('history') ?? [];
        if (str_contains($userMessage, 'AGENT')){
            $chatHistory[] = ["role" => "user", "name" => "agent_chat", "content" => $userMessage];
        }
        else{
            $chatHistory[] = ["role" => "user", "name" => "ticket_handling", "content" => $userMessage];
        }

        $session->set('history', $chatHistory);

        // return $this->respond([
        //     "confirmation" => "Pending",
        //     "tokens_used" => $responseData['usage'] ?? null
        // ]);
        // return $this->respond($json);
        $apiResponse = $json['API_response'] ?? '';
        $pending_functions = $json['pendingFunctions'] ?? [];

        /////************************** */
        // $chatHistory = $session->get('history') ?? [];
        // $chatHistory[] = ["role" => "assistant", "name" => "ticket_handling", "content" => json_encode($aiMessage['tool_calls'])];

        // $session->set('history', $chatHistory);

        $agentChatMessage = "";
        
        // return $this->respond($pending_functions);
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
                    $description = $this->description($func);
                    $pending_functions[] = ["id" => $id, "description" => $description, "functionName" => $func, "confirmation" => "pending", "parameters" => $arguments];
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
        "response" => $agentChatMessage, //*** 
        "tokens_used" => $responseData['usage'] ?? null,
        "API_response" => $apiResponse]);
       
        // // $func = $responseData['choices'][0]['message']['tool_calls'][0]['function']['name'];

        // // return $this->respond($functionsResponse); /////////!!!!!!!!!!!!


        // // return $this->respond($APIresponse);

        // // Log actions in json
        // $this->json_log($functionsCalled, $session->session_id);

        // if (isset($responseData['choices'][0]['message']['content'])) {
        //     //preg_match('/Summary:\s*(.*?)\s*Response:\s*(.*)/is', $responseData['choices'][0]['message']['content'], $matches);

        //     // $chatHistory[] = ["role" => "user", "content" => $userMessage]; // use this for no simplification
        //     // $chatHistory[] = ["role" => "user", "content" => $matches[1]];
        //     // $chatHistory[] = ["role" => "assistant", "content" => $matches[2]];
        //     $chatHistory[] =  ["role" => "assistant", "content" => $responseData['choices'][0]['message']['content']];
        //     $chatHistory[] =  ["role" => "assistant", "content" => $func];
        //     $session->set('history', $chatHistory);

        //     return $this->respond([
        //         "status" => "success",
        //         "response" => $responseData['choices'][0]['message']['content'], //*** 
        //         "tokens_used" => $responseData['usage'] ?? null,
        //         "API_response" => $functionsResponse
        //     ]);
        // }

        // else {
        //     $chatHistory[] =  ["role" => "assistant", "content" => $func];
        //     $session->set('history', $chatHistory);
        //     return $this->respond([
        //         "status" => "success",
        //         "response" => "No Response was Generated", //*** 
        //         "tokens_used" => $responseData['usage'] ?? null,
        //         "API_response" => $functionsResponse
        //     ]);
        // }
        // // return $this->fail("Failed to get a response from OpenAI.", 500);
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
            return "Lists all forwarders on the server for the provided domain name";
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

    public function formatResponse($data) {
        $accounts = [];
        $data = $data['data'];
    
        // if (!isset($data['acct']) || !is_array($data['acct'])) {
        //     return []; // Just return empty list instead of throwing error
        // }
    
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
    
    

}

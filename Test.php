<?php
class WHMCS {
	private $response_code = 0;
	private $error = "";
	private $whmcs_api_id = "";
	private $whmcs_api_secret = "";
	private $whmcs_server = "";

	public function __construct($server, $api_id, $api_secret) {
		$this->whmcs_api_id = $api_id;
		$this->whmcs_api_secret = $api_secret;
		$this->whmcs_server = $server;
	}

	public function clients($status = "", $q = "") {
		return $this->_post(array('action' => 'GetClients', 'search' => $q, 'limitnum' => 50000, 'status' => $status));
	}

	public function getClient($client_id) {
		return $this->_post(array('action' => 'GetClientsDetails', 'clientid' => $client_id, 'limitnum' => 50000));
	}

	public function clientGroups() {
		return $this->_post(array('action' => 'GetClientGroups', 'limitnum' => 50000));
	}

	public function addClient($client) {
		$client["action"] = "AddClient";
		return $this->_post($client);
	}

	public function updateClient($client) {
		$client["action"] = "UpdateClient";
		$client["clientid"] = $client["ext_client_id"];
		return $this->_post($client);
	}

	public function closeClient($client_id) {
		$client = array();
		$client["action"] = "CloseClient";
		$client["clientid"] = $client_id;
		return $this->_post($client);
	}

	public function contacts($client_id) {
		return $this->_post(array('action' => 'GetContacts', 'userid' => $client_id, 'limitnum' => 50000));
	}

	public function addContact($client, $contact) {
		$contact["action"] = "AddContact";
		$contact["clientid"] = $client["ext_client_id"];
		return $this->_post($contact);
	}

	public function updateContact($contact) {
		$contact["action"] = "UpdateContact";
		$contact["contactid"] = $contact["ext_client_id"];
		return $this->_post($contact);
	}

	public function deleteContact($contact_id) {
		$contact = array();
		$contact["action"] = "DeleteContact";
		$contact["contactid"] = $contact_id;
		return $this->_post($contact);
	}

	public function getInvoices($client_id) {
		$data = array();
		$data["action"] = "GetInvoices";
		$data["userid"] = $client_id;
		$data["limitnum"] = 5000;
		return $this->_post($data);
	}

	public function domains($client_id) {
		$data = array();
		$data["action"] = "GetClientsDomains";
		$data["clientid"] = $client_id;
		$data["limitnum"] = 5000;
		return $this->_post($data);
	}

	public function allDomains() {
		$data = array();
		$data["action"] = "GetClientsDomains";
		$data["limitnum"] = 50000;
		return $this->_post($data);
	}

	public function services($client_id) {
		$data = array();
		$data["action"] = "GetClientsProducts";
		$data["clientid"] = $client_id;
		$data["limitnum"] = 5000;
		return $this->_post($data);
	}

	public function allServices() {
		$data = array();
		$data["action"] = "GetClientsProducts";
		$data["limitnum"] = 50000;
		return $this->_post($data);
	}

	public function addOrder($data) {
		$data["action"] = "AddOrder";
		return $this->_post($data);
	}

	public function validateLogin($email, $password) {
		$data = array();
		$data["action"] = "ValidateLogin";
		$data["email"] = $email;
		$data["password2"] = $password;
		$data = $this->_post($data);
		if (array_key_exists("userid", $data)) {
			return $this->client($data["userid"]);
		} else {
			return null;
		}
	}

	public function getSecurityQuestions() {
		$data = array();
		$data["action"] = "GetSecurityQuestions";
		return $this->_post($data);
	}

	public function genInvoices($client_id) {
		$data = array();
		$data["action"] = "GenInvoices";
		$data["clientid"] = $client_id;
		return $this->_post($data);
	}

	public function getInvoice($invoice_id) {
		$data = array();
		$data["action"] = "GetInvoice";
		$data["invoiceid"] = $invoice_id;
		return $this->_post($data);
	}

	// public function getInvoices($client_id) {
	// 	$data = array();
	// 	$data["action"] = "GetInvoices";
	// 	$data["userid"] = $client_id;
	// 	return $this->_post($data);
	// }

	public function updateClientProduct($service) {
		$service["action"] = "UpdateClientProduct";
		$x = $this->_post($service);
		if ($this->response_code == 200) {
			return $x;
		} elseif ($x === false) {
			return array("result" => "error", "response_code" => $this->response_code, "error" => $this->error);
		}
	}

	public function acceptOrder($order_id) {
		$data = array();
		$data["action"] = "AcceptOrder";
		$data["orderid"] = $order_id;
		return $this->_post($data);
	}

	public function addPayMethod($client_id, $card_number, $card_exp, $card_issue_number, $gateway_module_name) {
		$data = array();
		$data["action"] = "AddPayMethod";
		$data["type"] = "RemoteCreditCard";
		$data["clientid"] = $client_id;
		$data["card_number"] = $card_number;
		$data["card_expiry"] = $card_exp;
		$data["card_issue_number"] = $card_issue_number;
		$data["gateway_module_name"] = $gateway_module_name;
		$data["set_as_default"] = true;
		return $this->_post($data);
	}

	public function capturePayment($invoice_id) {
		$data = array();
		$data["action"] = "CapturePayment";
		$data["invoiceid"] = $invoice_id;
		return $this->_post($data);
	}

	public function decryptPassword($password) {
		$data = array();
		$data["action"] = "DecryptPassword";
		$data["password2"] = $password;
		return $this->_post($data);
	}

	public function addons($service_id = null) {
		$data = array();
		$data["action"] = "GetClientsAddons";
		if ($service_id !== null) {
			$data["serviceid"] = $service_id;
		}
		$data["limitnum"] = 50000;
		return $this->_post($data);
	}

	public function ticket($client_id, $department_id, $subject, $message, $priority = "Medium", $domain_id = null, $service_id = null) {
		$data = array();
		$data["action"] = "OpenTicket";
		$data["clientid"] = $client_id;
		$data["deptid"] = $department_id;
		$data["subject"] = $subject;
		$data["message"] = $message;
		$data["priority"] = $priority;
		if ($domain_id !== null) {
			$data["domainid"] = $domain_id;
		}
		if ($service_id !== null) {
			$data["serviceid"] = $service_id;
		}
		$data["admin"] = true;
		$data["markdown"] = true;
		return $this->_post($data);
	}

	public function tickets($data = null) {
		if(is_null($data)) {
			$data = array();
		}
		$data["action"] = "GetTickets";
		return $this->_post($data);
	}

	public function getTicket($ticket_num, $data = null) {
		if(is_null($data)) {
			$data = array();
		}
		$data["action"] = "GetTicket";
        $data["ticketnum"] = $ticket_num;
		return $this->_post($data);
	}

	public function updateTicket($ticket_id, $data = null) {
		if(is_null($data)) {
			$data = array();
		}
		$data["action"] = "UpdateTicket";
		$data["ticketid"] = $ticket_id;
		return $this->_post($data);
	}

	public function ticketReply($ticket_id, $data = null) {
		if(is_null($data)) {
			$data = array();
		}

		// Message is required.
		if(!isset($data["message"])) {
			return false;
		}

		$data["action"] = "AddTicketReply";
		$data["ticketid"] = $ticket_id;
		return $this->_post($data);
	}

	public function getPromo(){
		$data = array();
		$data["action"] = "GetPromotions";
		return $this->_post($data);
	}

	public function getProducts($pid = ""){
		$data = array();
		$data["action"] = "GetProducts";
        $data["pid"] = $pid;
		return $this->_post($data);
	}

	public function getAdmin(){
		$data = array();
		$data["action"] = "GetAdminUsers";
		return $this->_post($data);
	}

	public function getServers($fetchStatus = false){
		$data = array();
		$data["action"] = "GetServers";
		$data["fetchStatus"] = $fetchStatus;
		return $this->_post($data);
	}

	public function getPaymentMethods(){
		$data = array();
		$data["action"] = "GetPaymentMethods";
		return $this->_post($data);
	}


	private function _post($data) {
		$data['identifier'] = $this->whmcs_api_id;
		$data['secret'] = $this->whmcs_api_secret;
		$data['responsetype'] = 'json';

		$ch = curl_init($this->whmcs_server);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$response = curl_exec($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		$this->response_code = $http_code;

		if ($http_code == 200 || $http_code == 201) {
			$this->error = "";

			$content = json_decode($response, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				return $content;
			} else {
				$this->error = "JSON decoding error";
				return false;
			}
		} else {
			$this->error = "HTTP error";
			return false;
		}
	}
}

// // Include the WHMCS class
// // require_once 'test_whmcs.php'; // Reusing the class from the previous file
// API credentials
$server = 'https://portal.easyonnet.io/includes/api.php';
$identifier = 'm2ftQEEegLHpyLPD6fiUpAqA0mx9T1XL';
$secret = 'ooxF2HcGp1VjifSAB6bcHO6WfunujurY';
// Initialize WHMCS class
$whmcs = new WHMCS($server, $identifier, $secret);

$data = $whmcs->getTicket('NTS-935845');
print_r($data);
// // Get client ID from command line or use a default for testing
// $clientId = isset($argv[1]) ? $argv[1] : 24576; // Default to the first client ID we saw
// // Get client details
// echo "Getting details for client ID: $clientId\n";
// $clientDetails = $whmcs->client($clientId);
// // Check if we got a valid response
// if (isset($clientDetails['result']) && $clientDetails['result'] == 'success') {
//     // Display client information
//     echo "Client Information:\n";
//     echo "-------------------\n";
//     echo "ID: " . $clientDetails['client']['id'] . "\n";
//     echo "Name: " . $clientDetails['client']['firstname'] . " " . $clientDetails['client']['lastname'] . "\n";
//     echo "Email: " . $clientDetails['client']['email'] . "\n";
//     echo "Company: " . $clientDetails['client']['companyname'] . "\n";
//     echo "Status: " . $clientDetails['client']['status'] . "\n";

//     // Get client's services
//     echo "\nFetching services for this client...\n";
//     $services = $whmcs->services($clientId);

//     if (isset($services['result']) && $services['result'] == 'success') {
//         $serviceCount = isset($services['totalresults']) ? $services['totalresults'] : 0;
//         echo "Found $serviceCount services\n";

//         if ($serviceCount > 0 && isset($services['products']['product'])) {
//             echo "\nServices:\n";
//             echo "--------\n";

//             $products = $services['products']['product'];
//             // If only one product, ensure it's in array format
//             if (!isset($products[0])) {
//                 $products = [$products];
//             }

//             foreach ($products as $index => $product) {
//                 echo ($index + 1) . ". " . $product['name'] . "\n";
//                 echo "   Domain: " . $product['domain'] . "\n";
//                 echo "   Status: " . $product['status'] . "\n";
//                 echo "   Next Due Date: " . $product['nextduedate'] . "\n";
//                 echo "   Recurring Amount: " . $product['recurringamount'] . "\n";
//                 echo "\n";
//             }
//         }
//     } else {
//         echo "Error fetching services: " . ($services['message'] ?? 'Unknown error') . "\n";
//     }

//     // Get client's invoices
//     echo "\nFetching invoices for this client...\n";
//     $invoices = $whmcs->invoices($clientId);

//     if (isset($invoices['result']) && $invoices['result'] == 'success') {
//         $invoiceCount = isset($invoices['totalresults']) ? $invoices['totalresults'] : 0;
//         echo "Found $invoiceCount invoices\n";

//         if ($invoiceCount > 0 && isset($invoices['invoices']['invoice'])) {
//             echo "\nRecent Invoices:\n";
//             echo "---------------\n";

//             $allInvoices = $invoices['invoices']['invoice'];
//             // If only one invoice, ensure it's in array format
//             if (!isset($allInvoices[0])) {
//                 $allInvoices = [$allInvoices];
//             }

//             // Show only the 5 most recent invoices
//             $recentInvoices = array_slice($allInvoices, 0, 5);

//             foreach ($recentInvoices as $index => $invoice) {
//                 echo ($index + 1) . ". Invoice #" . $invoice['id'] . "\n";
//                 echo "   Date: " . $invoice['date'] . "\n";
//                 echo "   Due Date: " . $invoice['duedate'] . "\n";
//                 echo "   Total: " . $invoice['total'] . "\n";
//                 echo "   Status: " . $invoice['status'] . "\n";
//                 echo "\n";
//             }
//         }
//     } else {
//         echo "Error fetching invoices: " . ($invoices['message'] ?? 'Unknown error') . "\n";
//     }
// } else {
//     echo "Error: " . ($clientDetails['message'] ?? 'Could not get client details') . "\n";
// }
// echo "\nTest complete.\n"; 
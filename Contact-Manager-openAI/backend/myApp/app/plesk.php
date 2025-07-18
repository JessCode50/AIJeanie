<?php

namespace Plesk;

class APILib{

	protected $api;

	public function __construct($host=null,$api_key=null,$port=8443)
	{
		$this->api = new \stdClass;
		$this->api->host = $host;
		$this->api->port = $port;
		$this->api->key = $api_key;

		if(!$host && defined('PLESK_API_HOST'))
			$this->api->host = PLESK_API_HOST;
		if(!$port && defined('PLESK_API_PORT'))
			$this->api->port = PLESK_API_PORT;
		if(!$api_key && defined('PLESK_API_KEY'))
			$this->api->key = PLESK_API_KEY;
	}

	public function set_api_host($host,$port=8443)
	{
		$this->api->host = $host;
		$this->api->port = $port;
	}

	public function set_api_key($key)
	{
		$this->api->key = $key;
	}

	/**
	 * Creates an API Key for future authentication on the target host Plesk Server
	 */
	function create_api_key($auth_user,$auth_password,$plesk_user=NULL,$ip=NULL,$description='plesklib')
	{
		$params = [
			'login' => $plesk_user,
			'ip' => $ip,
			'description' => $description,
			'basic_auth' => true,
			'auth_user' => $auth_user,
			'auth_password' => $auth_password,
		];
		if( !$ip )
			unset($params['ip']);
		if( !$plesk_user )
			unset($params['login']);
		$response = $this->plesk_api_call('auth/keys',$params,[]);
		$result = new \stdClass;

		return $response;

		if( $response === false )
		{
			$result->error = curl_error($ch);
			$result->result = false;
			return $result;
		}
		else
		{
			$result->data = json_decode($response);
			$result->result = true;
			return $result;
		}
	}

    public function generate_plesk_api_key()
{
    // $enc = \Config\Services::encrypter();

    $server_hostname = 'Torplesk-02.dynamichosting.biz';
    $api_user = 'admin';
    $api_pass = '';

    // $api = new PleskAPI;
    // $api->set_api_host('https://' . $server_hostname);

    $response = $this->create_api_key($api_user, $api_pass);

    if (!is_object($response) ||
        !property_exists($response, 'data') ||
        !is_object($response->data) ||
        !property_exists($response->data, 'key')) {
        // return $this->fail(['Bad API Response 1', $response]);
    }

    // // Encrypt the API key
    // $encrypted_key = base64_encode($enc->encrypt($response->data->key));

    // Save to JSON file
    $json_data = [
        'hostname' => $server_hostname,
        'api_user' => $api_user,
        'api_key' => $response->data->key,
        'created_at' => date('c'),
    ];

    $json_file = 'server.json';

    file_put_contents($json_file, json_encode($json_data, JSON_PRETTY_PRINT));
}

// function plesk_api_call($command, $params = [], $body = null)
// {
//     $url = 'https://' . $this->api->host . ':' . $this->api->port . '/api/v2/' . $command;

//     $headers = ['Accept: application/json'];

//     if (isset($params['basic_auth']) && $params['basic_auth']) {
//         $headers[] = 'Authorization: Basic ' . base64_encode($params['auth_user'] . ':' . $params['auth_password']);
//         unset($params['basic_auth'], $params['auth_user'], $params['auth_password']);
//     } else {
//         $headers[] = "X-API-Key: " . $this->api->key;
//     }

//     // Append any remaining $params as query string (if any)
//     if (count($params) > 0) {
//         $url .= '?' . http_build_query($params);
//     }

//     $ch = curl_init();
//     curl_setopt($ch, CURLOPT_URL, $url);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_TIMEOUT, 10);

//     if (!is_null($body)) {
//         curl_setopt($ch, CURLOPT_POST, true);
//         $jsonBody = json_encode($body);
//         curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
//         $headers[] = 'Content-Type: application/json';
//         // Update headers with Content-Type
//         curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//     }

//     $response = curl_exec($ch);

//     if ($response === false) {
//         $error = curl_error($ch);
//         curl_close($ch);
//         return (object)['result' => false, 'error' => $error];
//     }

//     curl_close($ch);

//     return (object)['result' => true, 'data' => json_decode($response)];
// }

function plesk_api_call($command,$params=[],$body=NULL)
	{
		$query = 'https://' . $this->api->host . ':' . $this->api->port . '/api/v2/' . $command; // Change this path to change the command - Everything else works

		if( array_key_exists('basic_auth',$params) && $params['basic_auth'] )
		{
			$auth_method = 'basic';
			$headers = [
				'Authorization: Basic '.base64_encode($params['auth_user'].':'.$params['auth_password']),
				'Accept: application/json',
			];
			unset($params['basic_auth']);
			unset($params['auth_user']);
			unset($params['auth_password']);
		}
		else
		{
			$auth_method = 'key';
			$headers = [
				"X-API-Key: ".$this->api->key,
				'Accept: application/json',
			];
		}

		$getp = [];
		foreach($params as $key => $value)
			$getp[] = sprintf("%s=%s",$key,$value);
		if( count($getp) )
			$query .= '?'.implode('&',$getp);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $query);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);

		if( !is_null($body) )
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			if( count($body) )
				curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($body));
			else
				curl_setopt($ch, CURLOPT_POSTFIELDS,'{}');
		}

		$response = curl_exec($ch);
		$status = curl_getinfo($ch,CURLINFO_RESPONSE_CODE);
        
        curl_close($ch); 
        
        if ($response === false) {
            return [
                'auth' => $auth_method,
                'error' => curl_error($ch),
                'result' => false,
            ];
        } else {
            return [
                'auth' => $auth_method,
                'data' => json_decode($response, true), // ✅ return associative array
                'status' => $status,
                'result' => true,
            ];
        }
        
	}


	public function clients()
	{
		return $this->plesk_api_call('clients');
	}

	public function client($client_id)
	{
		return $this->plesk_api_call("clients/{$client_id}");
	}

	public function client_domains($client_id)
	{
		return $this->plesk_api_call("clients/{$client_id}/domains");
	}

	public function domains()
	{
		return $this->plesk_api_call("domains");
	}

	public function domain($domain_id)
	{
		return $this->plesk_api_call("domains/{$domain_id}");
	}

	public function domain_status($domain_id)
	{
		return $this->plesk_api_call("domains/{$domain_id}/status");
	}

	public function mail()
	{
		return $this->plesk_api_call("customers");
	}


}

$plesk = new APILib('Torplesk-02.dynamichosting.biz', '9f39b0dd-1339-7d75-98e4-e3e7f9847208');
$response = $plesk->mail();

print_r($response);

// $plesk = new APILib('Torplesk-02.dynamichosting.biz');
// $result = $plesk->generate_plesk_api_key();


// For API Key creation

// function plesk_api_call($command, $params = [], $body = null)
// {
//     $url = 'https://' . $this->api->host . ':' . $this->api->port . '/api/v2/' . $command;

//     $headers = ['Accept: application/json'];

//     if (isset($params['basic_auth']) && $params['basic_auth']) {
//         $headers[] = 'Authorization: Basic ' . base64_encode($params['auth_user'] . ':' . $params['auth_password']);
//         unset($params['basic_auth'], $params['auth_user'], $params['auth_password']);
//     } else {
//         $headers[] = "X-API-Key: " . $this->api->key;
//     }

//     // Append any remaining $params as query string (if any)
//     if (count($params) > 0) {
//         $url .= '?' . http_build_query($params);
//     }

//     $ch = curl_init();
//     curl_setopt($ch, CURLOPT_URL, $url);
//     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//     curl_setopt($ch, CURLOPT_TIMEOUT, 10);

//     if (!is_null($body)) {
//         curl_setopt($ch, CURLOPT_POST, true);
//         $jsonBody = json_encode($body);
//         curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
//         $headers[] = 'Content-Type: application/json';
//         // Update headers with Content-Type
//         curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//     }

//     $response = curl_exec($ch);

//     if ($response === false) {
//         $error = curl_error($ch);
//         curl_close($ch);
//         return (object)['result' => false, 'error' => $error];
//     }

//     curl_close($ch);

//     return (object)['result' => true, 'data' => json_decode($response)];
// }
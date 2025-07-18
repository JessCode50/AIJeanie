<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
use App\Libraries\PleskAPI;
use App\Models\HostingServerModel;
use App\Models\ProjectDomainModel;
use App\Models\QueueMailModel;

class PleskHost extends BaseController
{
	use ResponseTrait;

	public function _remap($method,...$params)
	{
		// Prevents all but administrators from using this model
		if( !$this->check_admin_permission() )
			return $this->failForbidden();
		else
			return $this->{$method}(...$params);
	}

	/**
	 * Retrieves info about the target server from the db
	 * Decodes the API Key stored for the server
	 * Returns a usable record with a decoded API Key
	 */
	protected function get_secure_target_server_info($target_server_id)
	{
		$enc = \Config\Services::encrypter();

		$model = new HostingServerModel;
		$server = $model->asObject()->find($target_server_id);

		// Load the data if it works, otherwise fail
		try{
			$server->api_key = $enc->decrypt( base64_decode($server->api_key) );
		}catch(\CodeIgniter\Encryption\Exceptions\EncryptionException $e){
			$server->api_key = null;
		}

		return $server;
	}

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

	/** 
	 * Creates a new API Key registered on the server using a username and password
	 */
	public function generate_plesk_api_key()
	{
    // $enc = \Config\Services::encrypter();

    $server_hostname = 'raphael.easyonnet.io';
    $api_user = 'admin';
    $api_pass = '2Zjc960_w';

    // $api = new PleskAPI;
    // $api->set_api_host('https://' . $server_hostname);

    $response = $this->create_api_key($api_user, $api_pass);

    if (!is_object($response) ||
        !property_exists($response, 'data') ||
        !is_object($response->data) ||
        !property_exists($response->data, 'key')) {
        return $this->fail(['Bad API Response 1', $response]);
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

    $json_file = __DIR__ . '/../config/Plesk_server.json';

    file_put_contents($json_file, json_encode($json_data, JSON_PRETTY_PRINT));
	}

	/**
	 * Retrieves a list of client accounts on the target server
	 */
	public function clients()
	{
		$hosting_server_id = $this->request->getGet('hosting_server_id');
		if( !$hosting_server_id )
			return $this->failValidationError('You must specify a hosting server to retrieve accounts from.');

		// Retrieves hosting server record with decrypted token
		$server = $this->get_secure_target_server_info($hosting_server_id);

		$api = new PleskAPI;
		$api->set_api_host('https://'.$server->hostname);
		$api->set_api_key($server->api_key);

		$response = $api->clients();
		if( is_object($response) && property_exists($response,'data') )
		{
			$clients = $response->data;
			return $this->respond($clients);
		}
		else
			return $this->fail(['Bad response from Plesk API',$response]);
	}

	protected function randomPassword() {
		$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		$pass = array(); //remember to declare $pass as an array
		$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		for ($i = 0; $i < 8; $i++) {
			$n = rand(0, $alphaLength);
			$pass[] = $alphabet[$n];
		}
		return implode($pass); //turn the array into a string
	}
}

generate_plesk_api_key();
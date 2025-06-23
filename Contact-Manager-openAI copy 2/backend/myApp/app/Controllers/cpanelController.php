<?php

namespace Cpanel;

class APILib{
	protected $whm;

	/**
	 * Constructor and setup for the library
	 * @param host string			URL of the WHM / Cpanel server; ie. davinci.easyonnet.io
	 * @param port string			Port number used to access the WHM API: def 2087
	 * @param user string			WHM User, usually "root"
	 * @param api_token string		The WHM APIToken used to access the API
	 */
	public function __construct($host=null,$user=null,$api_token=null,$port=2087)
	{
		$this->whm = (object)[
			'host' => $host,
			'port' => $port,
			'user' => $user,
			'token' => $api_token,
		];

		if(!$host && defined('CPANEL_WHM_HOST'))
			$this->whm->host = CPANEL_WHM_HOST;
		if(!$port && defined('CPANEL_WHM_PORT'))
			$this->whm->port = CPANEL_WHM_PORT;
		if(!$user && defined('CPANEL_WHM_USER'))
			$this->whm->user = CPANEL_WHM_USER;
		if(!$api_token && defined('CPANEL_WHM_TOKEN'))
			$this->whm->token = CPANEL_WHM_TOKEN;
	}

	/**
	 * Base CPanel UAPI Call Method
	 * Implements all the basics necessary to run CPanel UAPI calls using whm parameters
	 */
	public function cpanel_api_call($cpanel_user,$module,$func,$params=[],$api_version=3)
	{
		$query = 'https://' . $this->whm->host . ':' . $this->whm->port . '/json-api/cpanel';
		if( !is_null($cpanel_user) )
			$query .= '?cpanel_jsonapi_user=' . $cpanel_user. '&';
		else
			$query .= '?';
		$query .= 'cpanel_jsonapi_module='.$module;
		$query .= '&cpanel_jsonapi_apiversion='.$api_version;
		$query .= '&cpanel_jsonapi_func='.$func;

		foreach($params as $key => $value)
			$query .= '&'.sprintf("%s=%s",$key,$value);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);

		// Set the API Token as a header
		curl_setopt($curl, CURLOPT_HTTPHEADER, [
			sprintf('Authorization: whm %s:%s',$this->whm->user,$this->whm->token),
		]);
		curl_setopt($curl, CURLOPT_URL, $query);

		$result = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		if($http_status != 200)
			return $http_status;

		return json_decode($result, true);
	}

	/**
	 * Base WHM/Cpanel API Call Method
	 * Implements all the basics necessary to run WHM API calls
	 */
	public function whm_api_call($command,$params=[],$api_version=1)
	{
		$query = 'https://'.$this->whm->host.':'.$this->whm->port.'/json-api/'.$command.'?api.version='.$api_version;

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);

		if( count($params) )
			$query = $query . '&'.http_build_query($params);

		// Set the API Token as a header
		curl_setopt($curl, CURLOPT_HTTPHEADER, [
			sprintf('Authorization: whm %s:%s',$this->whm->user,$this->whm->token),
		]);
		curl_setopt($curl, CURLOPT_URL, $query);

		$result = curl_exec($curl);
		$http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);

		if($http_status != 200)
			return $http_status;

		return json_decode($result, true);
	}

	/**
	 * Returns a list of accounts on the server
	 * Ref: https://api.docs.cpanel.net/openapi/whm/operation/listaccts/
	 */
	public function listaccts($want=NULL)
	{
		$params = [];
		if( $want )
			$params['want'] = $want;
		return $this->whm_api_call('listaccts',$params);
	}

	/**
	 * Lists available Packages/Plans on the server
	 * Ref: https://api.docs.cpanel.net/openapi/whm/operation/listpkgs/
	 */
	public function listpkgs($want=NULL)
	{
		$params = [];
		if( $want )
			$params['want'] = $want;
		return $this->whm_api_call('listpkgs',$params);
	}

	/**
	 * Creates a new CPanel Account on the server
	 * Ref: https://api.docs.cpanel.net/openapi/whm/operation/createacct/
	 */
	public function createacct($username,$domain,$contact_email,$other_options=NULL)
	{
		$params = [
			'username' => $username,
			'domain' => $domain,
			'contactemail' => $contact_email,
		];
		if( is_array($other_options) && count($other_options) > 0 )
		{
			$params = array_merge($params,$other_options);
		}
		return $this->whm_api_call('createacct',$params);
	}

	/**
	 * Return a List of all Domains on a Server
	 */
	public function get_domain_info()
	{
		$params = [];
		return $this->whm_api_call('get_domain_info',$params);
	}

	/**
	 * Returns a list of all email addresses on the account
	 * Ref: https://api.docs.cpanel.net/openapi/cpanel/operation/list_pops/
	 */
	public function list_pops($cpanel_user)
	{
		return $this->cpanel_api_call($cpanel_user,'Email','list_pops');
	}

	/**
	 * Returns a list of all email addresses on the account with disk quota information included
	 * Ref: https://api.docs.cpanel.net/openapi/cpanel/operation/list_pops_with_disk/
	 */
	public function list_pops_with_disk($cpanel_user)
	{
		return $this->cpanel_api_call($cpanel_user,'Email','list_pops_with_disk');
	}

	/**
	 * Returns a count of all email addresses on the account
	 * Ref: https://api.docs.cpanel.net/openapi/cpanel/operation/count_pops/
	 */
	public function count_pops($cpanel_user)
	{
		return $this->cpanel_api_call($cpanel_user,'Email','count_pops');
	}

	/**
	 * Seems to provide a means of verifying that an account name is valid, that is a single EMAIL ADDRESS
	 * ie. $account=user@domain.com - comes back with success IF the email exists
	 * Ref: https://api.docs.cpanel.net/openapi/cpanel/operation/account_name/
	 */
	public function account_name($cpanel_user,$account=null,$display='any_value')
	{
		$params = [];
		if( $account )
			$params['account'] = $account;
		if( $display )
			$params['display'] = $display;
		return $this->cpanel_api_call($cpanel_user,'Email','account_name',$params);
	}

	/**
	 * Adds a new Email Address to the Account
	 * Ref: https://api.docs.cpanel.net/openapi/cpanel/operation/add_pop/
	 * @param cpanel_user string				The account name in cpanel for the user account
	 * @param email_user string					The 1st part of the email address, the "user" part of "user@domain.com".
	 * @param password string					The password to set for the mailbox
	 * @param domain string (optional)			The domain is automatically set to the default for the cpanel account, but if there are sub-domains you have to specify
	 * @return object							api json results
	 */
	public function add_pop($cpanel_user,$email_user,$password,$domain=null,$quota=0,$send_welcome_email=0,$skip_update_db=0)
	{
		$params = [
			'email' => $email_user, // required
			'password' => $password, // required
			'quota' => $quota, // optional
			'send_welcome_email' => $send_welcome_email, //optional
			'skip_update_db' => $skip_update_db, //optional
		];
		if( $domain )
			$params['domain'] = $domain; // optional
		return $this->cpanel_api_call($cpanel_user,'Email','add_pop',$params);
	}

	/**
	 * Validates an email account password
	 * Ref: 
	 */
	public function verify_password($cpanel_user,$email,$password)
	{
		$params = [
			'email' => $email,
			'password' => $password,
		];
		return $this->cpanel_api_call($cpanel_user,'Email','verify_password',$params);
	}

	/**
	 * Removes an Email Address
	 * Ref: https://api.docs.cpanel.net/openapi/cpanel/operation/delete_pop/
	 * It should be notified that this function is a SIMPLIFIED version of the actual api call 
	 * There are options for controlling the removal of the mailbox folder, and for changing the account quota
	 * which are just ignored here because the default behaviour makes sense in general terms
	 */
	public function delete_pop($cpanel_user,$email)
	{
		[$user,$domain] = explode('@',$email);
		$params = [
			'email' => $user,
			'domain' => $domain,
			//'flags' => 'passwd', // would preserve the home directory after deleting the mail account - any other value deletes it
			'skip_quota' => 0, // whether or not to modify the account's quota, 0 is default, which means YES
		];
		return $this->cpanel_api_call($cpanel_user,'Email','delete_pop',$params);
	}

	/**
	 * Lists all forwarders on the server for the provided domain name
	 * Ref: https://api.docs.cpanel.net/openapi/cpanel/operation/list_forwarders/
	 */
	public function list_forwarders($cpanel_user, $domain)
	{
		$params = [
			'domain' => $domain
		];
		return $this->cpanel_api_call($cpanel_user,'Email','list_forwarders',$params);
	}

	/**
	 * Adds an Email Forwarder
	 * Ref: https://api.docs.cpanel.net/openapi/cpanel/operation/add_forwarder/
	 */
	public function add_forwarder($cpanel_user,$email,$forward_to_email)
	{
		[$user,$domain] = explode('@',$email);
		$params = [
			'domain' => $domain,
			'email' => $email,
			'fwdemail' => $forward_to_email,
			'fwdopt' => 'fwd',
		];
		return $this->cpanel_api_call($cpanel_user,'Email','add_forwarder',$params);
	}

	/**
	 * Retreives only the version number of the database MYSQL/MariaDB Server
	 */
	public function get_server_version($cpanel_user)
	{
		$result = $this->cpanel_api_call($cpanel_user,'Mysql','get_server_information');
		return $result['result']['data']['version'];
	}

	/**
	 * Lists the mysql databases on the server for the given account
	 */
	public function list_mysql_databases($cpanel_user)
	{
		return $this->cpanel_api_call($cpanel_user,'Mysql','list_databases');
	}

	/**
	 * Create a new MySQL Database Schema
	 * In the provided cpanel User account
	 */
	public function create_mysql_database($cpanel_user,$database_name,$prefix_size=16)
	{
		$params = [
			'name' => $database_name,
			'prefix-size' => $prefix_size, // Default 16 (the desired prefix size)
		];
		return $this->cpanel_api_call($cpanel_user,'Mysql','create_database',$params);
	}

	/**
	 * Create a new MySQL Database User
	 * In the provided cpanel User account
	 * NOTES:
	 *  - database user names are limited to 16 characters total for MySQL 5.6, 32 for MySQL 5.7+, 47 for MariaDB
	 *  - the first 9 characters are used for a prefix using the cpanel account username and an "_" character
	 *  - only the first 8 characters of the cpanel account username are used in this way. 
	 *    eg. mycpanelaccount ---> mycpanel_dbusername
	 *  - basically if you know the cpanel username, you can predict how many characters you will have available if you have a short name
	 *    eg. mycp --> mycp_dbusername (only 5 characters used, leaving 11 chars for a username if you have MySQL 5.6, or 42 if you have MariaDB)
	 */
	public function create_mysql_user($cpanel_user,$user,$password,$prefix_size=16)
	{
		$params = [
			'name' => $user,
			'password' => $password,
			'prefix-size' => $prefix_size, // Default 16 (the desired prefix size, assuming better than MySQL5.7)
		];
		return $this->cpanel_api_call($cpanel_user,'Mysql','create_user',$params);
	}

	/**
	 * Sets Privileges for a Database User on a provided Database
	 * In the provided cpanel User account
	 * 
	 * @param $cpanel_user string	the account username for the cpanel user
	 * @param $database	string		the name of the database schema, including prefix
	 * @param $user string			the complete db username including prefix
	 * @param $privileges string 	a comma separated list of privilege keywords (see below)
	 * 
	 * Available Privileges:
	 * ALTER, ALTER ROUTINE, CREATE, CREATE ROUTINE, CREATE TEMPORARY TABLES, CREATE VIEW, 
	 * DELETE, DROP, EVENT, EXECUTE, INDEX, INSERT, LOCK TABLES, REFERENCES, SELECT, 
	 * SHOW VIEW, TRIGGER, UPDATE
	 * 
	 * You may also set:
	 * ALL PRIVILEGES
	 */
	public function set_privileges_on_database($cpanel_user,$database,$user,$privileges)
	{
		$params = [
			'database' => $database,
			'user' => $user,
			'privileges' => $privileges,
		];
		return $this->cpanel_api_call($cpanel_user,'Mysql','set_privileges_on_database',$params);
	}

	/**
	 * Retrieves a list of User Privileges for hte given user on the given schema
	 */
	public function get_privileges_on_database($cpanel_user,$database,$user)
	{
		$params = [
			'database' => $database,
			'user' => $user,
		];
		return $this->cpanel_api_call($cpanel_user,'Mysql','get_privileges_on_database',$params);
	}


	/**
	 * Restores a database file from a backup archive
	 * that has already been uploaded to directory on the server
	 * 
	 * NOTES: 
	 * - backups uploaded MUST be GZIPPED SQL files
	 * - backups must contain a comment with Database: <database-name> so that the system knows where to restore the backup
	 * - backups must DROP TABLES and recreate
	 * 
	 */
	public function restore_mysql_database($cpanel_user,$backup)
	{
		$params = [
			'backup' => $backup, // Single File name, Path ON THE SERVER, like /home/user/mybackup.sql.gz	
			'timeout' => 7200, // 7200 is default
			'verbose' => 0, // 0 is default
		];
		return $this->cpanel_api_call($cpanel_user,'Backup','restore_databases',$params);
	}

	/**
	 * Add an FTP Account
	 */
	public function add_ftp($cpanel_user,$user,$domain,$password,$homedir=null,$quota=0)
	{
		$params = [
			'user' => $user,
			'domain' => $domain,
			'pass' => $password,
			'homedir' => !$homedir ? '/' : $homedir,
			'quota' => $quota,
		];
		return $this->cpanel_api_call($cpanel_user,'Ftp','add_ftp',$params);
	}

	/**
	 * Retrieves bandwidth usage statistics for reseller accounts
	 * Ref: https://api.docs.cpanel.net/openapi/whm/operation/showbw/
	 */
	public function showbw($year=null,$month=null,$start_date=null,$end_date=null)
	{
		$params = [];
		if ($year) $params['year'] = $year;
		if ($month) $params['month'] = $month;
		if ($start_date) $params['start'] = $start_date;
		if ($end_date) $params['end'] = $end_date;
		
		return $this->whm_api_call('showbw',$params);
	}
}
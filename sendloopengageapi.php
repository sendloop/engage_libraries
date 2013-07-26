<?php
/*
	Sendloop Engage API PHP Library
	(c)Copyright Sendloop. All rights reserved.
	
	This API library helps you to make connections to Sendloop Engage API
	quickly without digging into curl settings.	
	
	$api = new SendloopEngageAPI("your_sendloop_api_key_here");
	
	// To track a person
	$api->trackPerson("user02942", "test@test.com", array('name' => 'test', 'age' => 24, 'birth_date' => '1977-12-10 23:20:00'));
	
	// To get person profile
	$api->getPerson("user02942");
	
	// To delete person profile
	$api->deletePerson("user02942");
	
*/
class SendloopEngageAPI {
	protected $_errorMessage;
	protected $_apiKey;

	/**
	 * Constructor
	 * @param string $apiKey Your Sendloop Engage API key
	 */
	public function __construct($apiKey)
	{
		$this->_apiKey = $apiKey;
	}

	/**
	 * Get person data
	 * @param integer $userId Person identifier
	 * @return An object containing person information
	 */
	public function getPerson($userId)
	{
		$response = $this->_talkWithServer('person.json', 'get', array('user_id' => $userId));
		if (isset($response->Person)) return $response->Person;
		return false;
	}

	/**
	 * Track person data
	 * @param integer $userId Person identifier
	 * @param string $email Person email address
	 * @param array $params An associative array of person information to be tracked
	 * @return bool
	 */
	public function trackPerson($userId, $email, $params = array())
	{
		$params['user_id'] = $userId;
		$params['email'] = $email;
		$response = $this->_talkWithServer('person.json', 'post', $params);
		if (isset($response->Status) && $response->Status == 'Okay') return true;
		return false;
	}

	/**
	 * Delete person
	 * @param integer $userId Person identifier
	 * @return bool
	 */
	public function deletePerson($userId)
	{
		$params['user_id'] = $userId;
		$response = $this->_talkWithServer('person.json', 'delete', $params);
		if (isset($response->Status) && $response->Status == 'Okay') return true;
		return false;
	}

	/**
	 * Returns API error message
	 * @return string
	 */
	public function getError()
	{
		return $this->_errorMessage;
	}

	protected function _talkWithServer($resource, $httpMethod = 'get', $params = array())
	{
		$httpMethod = strtoupper($httpMethod);
		if (! in_array($httpMethod, array('GET', 'DELETE', 'POST'))) throw new Exception('Invalid HTTP method: '.$httpMethod);

		$this->_errorMessage = '';

		$data = http_build_query($params);

		$requestHeaders = array();
		$requestHeaders[] = "Authorization: Basic " . base64_encode("{$this->_apiKey}:");
		if ($httpMethod == 'POST') {
			$requestHeaders[] = 'Content-type: application/x-www-form-urlencoded';
		}

		$contextOptions = array();
		$contextOptions['header'] = $requestHeaders;
		$contextOptions['method'] = $httpMethod;
		$contextOptions['ignore_errors'] = true;
		if ($httpMethod == 'POST') {
			$contextOptions['content'] = $data;
		}

		$context = stream_context_create(array('http' => $contextOptions));

		$url = 'http://sendloop.com/api/v4/'.$resource.'/'.($httpMethod == 'GET' || $httpMethod == 'DELETE' ? '?'.$data : '');
		$response = @file_get_contents($url, false, $context);
		$response = json_decode($response);

		if ($response->Status !== 'Okay') {
			$this->_errorMessage = $response->Status;
			return false;
		}

		return $response;
	}
}